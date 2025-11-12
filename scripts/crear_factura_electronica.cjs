const https = require('https');
const fs = require('fs');
const { execSync } = require('child_process');
const path = require('path');

// Configuración de logs
const LOG_FILE = path.join(__dirname, '../storage/logs/alegra_api.log');

// Obtener las credenciales de Alegra desde la base de datos
function getAlegraCredentials() {
    try {
        // Ejecutar el script PHP que obtiene las credenciales
        const output = execSync('php ' + __dirname + '/get_alegra_credentials.php', { encoding: 'utf8' });
        const credentials = JSON.parse(output);
        
        if (credentials.success && credentials.email && credentials.token) {
            return {
                email: credentials.email,
                token: credentials.token
            };
        } else {
            throw new Error('Credenciales de Alegra no válidas: ' + (credentials.error || 'Email o token vacíos'));
        }
    } catch (error) {
        console.error('Error al obtener credenciales de Alegra:', error.message);
        throw new Error('No se pudieron obtener las credenciales de Alegra. Verifique la configuración en la empresa o en el archivo .env');
    }
}

// Registrar en un archivo de log
function log(message, data = null) {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] ${message}`;
    
    console.log(logMessage);
    
    if (data) {
        console.log(JSON.stringify(data, null, 2));
    }
    
    // También guardar en archivo
    try {
        fs.appendFileSync(LOG_FILE, logMessage + '\n');
        if (data) {
            fs.appendFileSync(LOG_FILE, JSON.stringify(data, null, 2) + '\n');
        }
    } catch (err) {
        console.error('Error al escribir en el archivo de log:', err.message);
    }
}

// Función para hacer una solicitud HTTP
function makeRequest(options, data = null) {
    return new Promise((resolve, reject) => {
        const req = https.request(options, (res) => {
            let responseData = '';
            
            res.on('data', (chunk) => {
                responseData += chunk;
            });
            
            res.on('end', () => {
                try {
                    const parsedData = responseData ? JSON.parse(responseData) : {};
                    resolve({
                        statusCode: res.statusCode,
                        headers: res.headers,
                        data: parsedData
                    });
                } catch (e) {
                    reject(new Error(`Error al analizar la respuesta: ${e.message}`));
                }
            });
        });
        
        req.on('error', (e) => {
            reject(new Error(`Error en la solicitud: ${e.message}`));
        });
        
        if (data) {
            req.write(data);
        }
        
        req.end();
    });
}

// Obtener plantillas de numeración activas para facturación electrónica
async function getActiveNumberTemplates(auth) {
    try {
        log('Obteniendo plantillas de numeración activas');
        
        const options = {
            hostname: 'api.alegra.com',
            port: 443,
            path: '/api/v1/number-templates',
            method: 'GET',
            headers: {
                'Authorization': `Basic ${auth}`,
                'Content-Type': 'application/json'
            }
        };
        
        const response = await makeRequest(options);
        
        if (response.statusCode >= 200 && response.statusCode < 300) {
            // Filtrar plantillas electrónicas activas
            const activeElectronicTemplates = response.data.filter(template => 
                template.isElectronic === true && template.status === 'active'
            );
            
            if (activeElectronicTemplates.length > 0) {
                log('Plantilla de numeración electrónica encontrada', activeElectronicTemplates[0]);
                return {
                    success: true,
                    data: activeElectronicTemplates[0]
                };
            } else {
                log('No se encontraron plantillas de numeración electrónica activas');
                return {
                    success: false,
                    error: 'No se encontraron plantillas de numeración electrónica activas'
                };
            }
        } else {
            log('Error al obtener plantillas de numeración', response);
            return {
                success: false,
                error: `Error al obtener plantillas de numeración: ${response.data.message || 'Error desconocido'}`
            };
        }
    } catch (error) {
        log('Excepción al obtener plantillas de numeración', { error: error.message });
        return {
            success: false,
            error: `Excepción al obtener plantillas de numeración: ${error.message}`
        };
    }
}

// Crear una factura electrónica
async function createElectronicInvoice(invoiceData, auth) {
    try {
        log('Creando factura electrónica', invoiceData);
        
        // Obtener plantilla de numeración electrónica activa
        const templateResult = await getActiveNumberTemplates(auth);
        
        if (!templateResult.success) {
            return templateResult;
        }
        
        // Asegurar que los datos de la factura tengan el formato correcto
        const formattedData = {
            ...invoiceData,
            numberTemplate: {
                id: templateResult.data.id
            },
            stamp: {
                generateStamp: true,
                generateQrCode: true
            }
        };
        
        // Asegurar que el cliente tenga el formato correcto
        if (formattedData.client && formattedData.client.id) {
            formattedData.client.id = parseInt(formattedData.client.id);
        }
        
        // Asegurar que los items tengan el formato correcto
        if (formattedData.items && Array.isArray(formattedData.items)) {
            formattedData.items = formattedData.items.map(item => ({
                id: parseInt(item.id),
                price: parseFloat(item.price),
                quantity: parseFloat(item.quantity || 1)
            }));
        }
        
        // Asegurar que el método de pago tenga el formato correcto
        // Convertir 'payments' a 'payment' si es necesario
        if (formattedData.payments && Array.isArray(formattedData.payments) && formattedData.payments.length > 0) {
            const payment = formattedData.payments[0];
            formattedData.payment = {
                paymentMethod: payment.paymentMethod,
                account: payment.account
            };
            delete formattedData.payments;
        }
        
        // Si no hay payment, agregar uno por defecto
        if (!formattedData.payment) {
            formattedData.payment = {
                paymentMethod: { id: 10 },
                account: { id: 1 }
            };
        }
        
        const options = {
            hostname: 'api.alegra.com',
            port: 443,
            path: '/api/v1/invoices',
            method: 'POST',
            headers: {
                'Authorization': `Basic ${auth}`,
                'Content-Type': 'application/json'
            }
        };
        
        const response = await makeRequest(options, JSON.stringify(formattedData));
        
        if (response.statusCode >= 200 && response.statusCode < 300) {
            log('Factura creada exitosamente', response.data);
            return {
                success: true,
                data: response.data
            };
        } else {
            log('Error al crear factura', response);
            return {
                success: false,
                error: `Error al crear factura: ${response.data.message || 'Error desconocido'}`
            };
        }
    } catch (error) {
        log('Excepción al crear factura', { error: error.message });
        return {
            success: false,
            error: `Excepción al crear factura: ${error.message}`
        };
    }
}

// Función principal
async function main() {
    try {
        // Verificar argumentos
        if (process.argv.length < 3) {
            throw new Error('Debe proporcionar la ruta al archivo JSON con los datos de la factura');
        }
        
        // Obtener credenciales
        const credentials = getAlegraCredentials();
        const auth = Buffer.from(`${credentials.email}:${credentials.token}`).toString('base64');
        
        // Leer datos de la factura desde el archivo JSON
        const jsonFilePath = process.argv[2];
        const invoiceDataRaw = fs.readFileSync(jsonFilePath, 'utf8');
        const invoiceData = JSON.parse(invoiceDataRaw);
        
        // Crear la factura electrónica
        const result = await createElectronicInvoice(invoiceData, auth);
        
        // Imprimir el resultado como JSON para que PHP pueda procesarlo
        console.log(JSON.stringify(result));
    } catch (error) {
        console.error(`Error en el proceso: ${error.message}`);
        console.log(JSON.stringify({
            success: false,
            error: error.message
        }));
    }
}

// Ejecutar la función principal
main();
