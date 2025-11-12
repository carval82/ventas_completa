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
async function makeRequest(options, data = null) {
    return new Promise((resolve, reject) => {
        log(`Realizando solicitud HTTP: ${options.method} ${options.hostname}${options.path}`);
        
        const req = https.request(options, (res) => {
            let responseData = '';
            
            res.on('data', (chunk) => {
                responseData += chunk;
            });
            
            res.on('end', () => {
                let parsedData;
                try {
                    parsedData = responseData ? JSON.parse(responseData) : {};
                    log(`Respuesta recibida (${res.statusCode}):`, parsedData);
                } catch (e) {
                    log(`Error al parsear respuesta JSON: ${e.message}`);
                    log(`Respuesta raw: ${responseData}`);
                    parsedData = { 
                        rawResponse: responseData,
                        parseError: e.message 
                    };
                }
                
                // Registrar errores específicos de Alegra
                if (res.statusCode >= 400) {
                    log(`Error en la API de Alegra (${res.statusCode}):`, parsedData);
                    
                    // Verificar si es el error de forma de pago obligatoria
                    if (parsedData.message && parsedData.message.includes("forma de pago es obligatoria")) {
                        log(`⚠️ Error de forma de pago obligatoria detectado`);
                    }
                    
                    // Verificar si es el error de numeración
                    if (parsedData.code === 3061 || (parsedData.message && parsedData.message.includes("numeración debe estar activa"))) {
                        log(`⚠️ Error de numeración inactiva detectado`);
                    }
                }
                
                resolve({
                    statusCode: res.statusCode,
                    headers: res.headers,
                    data: parsedData
                });
            });
        });
        
        req.on('error', (error) => {
            log(`Error en la solicitud HTTP: ${error.message}`);
            reject(error);
        });
        
        if (data) {
            log(`Enviando datos:`, data);
            req.write(data);
        }
        
        req.end();
    });
}

// Obtener detalles de la factura
async function getInvoiceDetails(invoiceId, auth) {
    const options = {
        hostname: 'api.alegra.com',
        port: 443,
        path: `/api/v1/invoices/${invoiceId}?expand=items,client`,
        method: 'GET',
        headers: {
            'Authorization': `Basic ${auth}`,
            'Accept': 'application/json'
        }
    };
    
    try {
        log(`Obteniendo detalles de la factura ${invoiceId}...`);
        const response = await makeRequest(options);
        
        if (response.statusCode >= 200 && response.statusCode < 300) {
            log(`Detalles de factura obtenidos correctamente`);
            return {
                success: true,
                data: response.data
            };
        } else {
            log(`Error al obtener detalles de la factura: ${response.statusCode}`, response.data);
            return {
                success: false,
                error: response.data.message || 'Error al obtener detalles de la factura',
                data: response.data
            };
        }
    } catch (error) {
        log(`Error al obtener detalles de la factura: ${error.message}`);
        return {
            success: false,
            error: error.message
        };
    }
}

// Verificar el estado de la factura
async function checkInvoiceStatus(invoiceId, auth) {
    try {
        log(`Verificando estado de la factura ${invoiceId}...`);
        
        const options = {
            hostname: 'api.alegra.com',
            port: 443,
            path: `/api/v1/invoices/${invoiceId}?fields=id,status,dueDate,date,observations,total,totalPaid,client,items,numberTemplate,number,cufe,qrCode,seller,warehouse,estimateId,paymentForm,paymentMethod,anotation,termsConditions,costCenter,currency,exchangeRate,metadata`,
            method: 'GET',
            headers: {
                'Authorization': `Basic ${auth}`,
                'Accept': 'application/json'
            }
        };
        
        const response = await makeRequest(options);
        
        if (response.statusCode >= 200 && response.statusCode < 300) {
            log(`Estado actual de la factura: ${response.data.status}`);
            log(`Detalles completos de la factura:`, JSON.stringify(response.data, null, 2));
            
            // Verificar si la factura ya está abierta
            if (response.data.status === 'open') {
                log(`La factura ya está abierta, no es necesario cambiar su estado`);
            } else if (response.data.status === 'draft') {
                log(`La factura está en estado borrador, se puede abrir`);
            } else {
                log(`La factura está en estado ${response.data.status}, no se puede cambiar a abierta`);
            }
            
            // Verificar si tiene número de factura electrónica
            if (response.data.numberTemplate && response.data.numberTemplate.isElectronic) {
                log(`La factura usa numeración electrónica: ${response.data.numberTemplate.name} (${response.data.numberTemplate.status})`);
                
                // Verificar si la numeración está activa
                if (response.data.numberTemplate.status !== 'active') {
                    log(`⚠️ ADVERTENCIA: La numeración electrónica NO está activa (${response.data.numberTemplate.status})`);
                }
            } else {
                log(`La factura NO usa numeración electrónica`);
            }
            
            return {
                success: true,
                data: response.data
            };
        }
        
        return {
            success: false,
            error: `Error al verificar estado: ${response.statusCode} - ${JSON.stringify(response.data)}`
        };
    } catch (error) {
        return {
            success: false,
            error: `Error al verificar estado: ${error.message}`
        };
    }
}

// Abrir factura (cambiar de borrador a abierta)
async function openInvoice(invoiceId, invoiceDetails, auth) {
    // Obtener fecha actual en formato YYYY-MM-DD
    const today = new Date().toISOString().split('T')[0];
    
    // Verificar si la factura ya está abierta
    if (invoiceDetails.status === 'open') {
        log(`La factura ya está abierta, no es necesario cambiar su estado`);
        return {
            success: true,
            data: invoiceDetails
        };
    }
    
    // Verificar si la factura no está en borrador
    if (invoiceDetails.status !== 'draft') {
        log(`La factura está en estado ${invoiceDetails.status}, no se puede cambiar a abierta`);
        return {
            success: false,
            error: `La factura está en estado ${invoiceDetails.status}, no se puede cambiar a abierta`
        };
    }
    
    // Verificar si la numeración electrónica está activa
    if (invoiceDetails.numberTemplate && 
        invoiceDetails.numberTemplate.isElectronic && 
        invoiceDetails.numberTemplate.status !== 'active') {
        log(`⚠️ ADVERTENCIA: La numeración electrónica NO está activa (${invoiceDetails.numberTemplate.status})`);
    }
    
    // Enfoque minimalista: enviar el mínimo de datos necesarios
    // Según la documentación, para abrir una factura solo se necesita enviar un objeto vacío
    const formats = [
        // Formato 1: Objeto vacío (minimalista)
        {},
        
        // Formato 2: Solo con date y dueDate actualizados
        {
            date: today,
            dueDate: today
        },
        
        // Formato 3: Con client id
        {
            client: { 
                id: parseInt(invoiceDetails.client.id) 
            }
        },
        
        // Formato 4: Con todos los campos que podrían ser necesarios
        {
            date: today,
            dueDate: today,
            client: { 
                id: parseInt(invoiceDetails.client.id) 
            },
            payment: { 
                paymentMethod: { id: 10 }, 
                account: { id: 1 } 
            },
            paymentForm: invoiceDetails.paymentForm || "CASH",
            paymentMethod: invoiceDetails.paymentMethod || "CASH"
        },
        
        // Formato 5: Solo con payment
        {
            payment: { 
                paymentMethod: { id: 10 }, 
                account: { id: 1 } 
            }
        },
        
        // Formato 6: Con payment y paymentForm/paymentMethod
        {
            payment: { 
                paymentMethod: { id: 10 }, 
                account: { id: 1 } 
            },
            paymentForm: "CASH",
            paymentMethod: "CASH"
        }
    ];
    
    // Intentar cada formato
    for (let i = 0; i < formats.length; i++) {
        try {
            log(`\nIntentando abrir factura con formato ${i + 1}...`, JSON.stringify(formats[i], null, 2));
            
            const options = {
                hostname: 'api.alegra.com',
                port: 443,
                path: `/api/v1/invoices/${invoiceId}/open`,
                method: 'POST',
                headers: {
                    'Authorization': `Basic ${auth}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            };
            
            const response = await makeRequest(options, JSON.stringify(formats[i]));
            
            log(`Respuesta del formato ${i + 1}: ${response.statusCode}`, response.data);
            
            if (response.statusCode >= 200 && response.statusCode < 300) {
                log(`\n✅ Factura abierta exitosamente con formato ${i + 1}`);
                return {
                    success: true,
                    formatIndex: i + 1,
                    data: response.data
                };
            }
        } catch (error) {
            log(`Error al intentar formato ${i + 1}: ${error.message}`);
        }
    }
    
    // Si llegamos aquí, ningún formato funcionó
    // Intentar un enfoque alternativo: primero actualizar la factura y luego abrirla
    try {
        log(`\nIntentando enfoque alternativo: actualizar la factura y luego abrirla...`);
        
        // 1. Actualizar la factura con los datos mínimos
        const updateOptions = {
            hostname: 'api.alegra.com',
            port: 443,
            path: `/api/v1/invoices/${invoiceId}`,
            method: 'PUT',
            headers: {
                'Authorization': `Basic ${auth}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        
        const updateData = {
            date: today,
            dueDate: today,
            client: { 
                id: parseInt(invoiceDetails.client.id) 
            },
            paymentForm: "CASH",
            paymentMethod: "CASH",
            payment: { 
                paymentMethod: { id: 10 }, 
                account: { id: 1 } 
            }
        };
        
        log(`Actualizando factura...`, JSON.stringify(updateData, null, 2));
        
        const updateResponse = await makeRequest(updateOptions, JSON.stringify(updateData));
        
        log(`Respuesta de actualización: ${updateResponse.statusCode}`, updateResponse.data);
        
        if (updateResponse.statusCode >= 200 && updateResponse.statusCode < 300) {
            // 2. Intentar abrir la factura con objeto vacío
            const openOptions = {
                hostname: 'api.alegra.com',
                port: 443,
                path: `/api/v1/invoices/${invoiceId}/open`,
                method: 'POST',
                headers: {
                    'Authorization': `Basic ${auth}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            };
            
            const openResponse = await makeRequest(openOptions, JSON.stringify({}));
            
            log(`Respuesta de apertura después de actualización: ${openResponse.statusCode}`, openResponse.data);
            
            if (openResponse.statusCode >= 200 && openResponse.statusCode < 300) {
                log(`\n✅ Factura abierta exitosamente después de actualización`);
                return {
                    success: true,
                    formatIndex: 'update-then-open',
                    data: openResponse.data
                };
            }
        }
    } catch (error) {
        log(`Error en enfoque alternativo: ${error.message}`);
    }
    
    return {
        success: false,
        error: 'No se pudo abrir la factura con ninguno de los formatos intentados'
    };
}

// Enviar factura a la DIAN
async function stampInvoice(invoiceId, auth) {
    const options = {
        hostname: 'api.alegra.com',
        port: 443,
        path: `/api/v1/invoices/${invoiceId}/stamp`,
        method: 'POST',
        headers: {
            'Authorization': `Basic ${auth}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    };
    
    try {
        log(`Enviando factura ${invoiceId} a la DIAN...`);
        
        // Enviamos un objeto vacío como cuerpo de la solicitud
        const response = await makeRequest(options, JSON.stringify({
            generateStamp: true,
            generateQrCode: true
        }));
        
        log(`Respuesta al enviar a DIAN: ${response.statusCode}`, response.data);
        
        if (response.statusCode >= 200 && response.statusCode < 300) {
            log('✅ Factura enviada a la DIAN correctamente');
            return {
                success: true,
                data: response.data
            };
        } else {
            log(`❌ Error al enviar factura a la DIAN: ${response.statusCode}`, response.data);
            return {
                success: false,
                error: response.data.message || 'Error desconocido en la integración con Alegra',
                data: response.data
            };
        }
    } catch (error) {
        log(`❌ Error al enviar factura a la DIAN: ${error.message}`);
        return {
            success: false,
            error: error.message
        };
    }
}

// Descargar PDF de la factura
async function downloadInvoicePDF(invoiceId, auth) {
    const options = {
        hostname: 'api.alegra.com',
        port: 443,
        path: `/api/v1/invoices/${invoiceId}/pdf`,
        method: 'GET',
        headers: {
            'Authorization': `Basic ${auth}`,
            'Accept': 'application/json'
        }
    };
    
    try {
        log(`Descargando PDF de la factura ${invoiceId}...`);
        const response = await makeRequest(options);
        
        if (response.statusCode >= 200 && response.statusCode < 300) {
            log(`PDF de factura descargado correctamente`);
            return {
                success: true,
                data: response.data
            };
        } else {
            log(`Error al descargar PDF de la factura: ${response.statusCode}`, response.data);
            return {
                success: false,
                error: response.data.message || 'Error al descargar PDF de la factura',
                data: response.data
            };
        }
    } catch (error) {
        log(`Error al descargar PDF de la factura: ${error.message}`);
        return {
            success: false,
            error: error.message
        };
    }
}

// Procesar el comando según los argumentos
async function processCommand() {
    // Obtener credenciales
    const credentials = getAlegraCredentials();
    const email = credentials.email;
    const token = credentials.token;
    const auth = Buffer.from(`${email}:${token}`).toString('base64');
    
    // Registrar las credenciales que se están utilizando (sin mostrar el token completo)
    log(`Usando credenciales: ${email} / ${token.substring(0, 3)}...${token.substring(token.length - 3)}`);
    
    // Verificar argumentos
    if (process.argv.length < 4) {
        console.error('Uso: node alegra_invoice_manager.cjs <comando> <id_factura>');
        console.error('Comandos disponibles: status, open, stamp, open_and_stamp, download_pdf');
        process.exit(1);
    }
    
    const command = process.argv[2];
    const invoiceId = process.argv[3];
    
    log(`Ejecutando comando: ${command} para factura: ${invoiceId}`);
    
    let result = { success: false, error: 'Comando no reconocido' };
    
    try {
        switch (command) {
            case 'status':
                result = await checkInvoiceStatus(invoiceId, auth);
                break;
                
            case 'open':
                // Primero obtener detalles de la factura
                const detailsResponse = await getInvoiceDetails(invoiceId, auth);
                if (!detailsResponse.success) {
                    result = detailsResponse;
                    break;
                }
                
                result = await openInvoice(invoiceId, detailsResponse.data, auth);
                break;
                
            case 'stamp':
                result = await stampInvoice(invoiceId, auth);
                break;
                
            case 'open_and_stamp':
                // Primero obtener detalles de la factura
                const details = await getInvoiceDetails(invoiceId, auth);
                if (!details.success) {
                    result = details;
                    break;
                }
                
                // Verificar el estado actual
                const status = await checkInvoiceStatus(invoiceId, auth);
                
                // Si ya está abierta, solo enviar a DIAN
                if (status.success && status.data.status === 'open') {
                    log('La factura ya está abierta, procediendo a enviar a DIAN...');
                    result = await stampInvoice(invoiceId, auth);
                } else {
                    // Abrir la factura
                    const openResult = await openInvoice(invoiceId, details.data, auth);
                    if (!openResult.success) {
                        result = openResult;
                        break;
                    }
                    
                    // Enviar a DIAN
                    result = await stampInvoice(invoiceId, auth);
                }
                break;
                
            case 'download_pdf':
                result = await downloadInvoicePDF(invoiceId, auth);
                break;
                
            default:
                result = { 
                    success: false, 
                    error: `Comando no reconocido: ${command}. Comandos disponibles: status, open, stamp, open_and_stamp, download_pdf` 
                };
        }
    } catch (error) {
        result = { 
            success: false, 
            error: `Error al ejecutar el comando ${command}: ${error.message}` 
        };
    }
    
    // Imprimir el resultado como JSON para que PHP pueda procesarlo
    console.log(JSON.stringify(result));
}

// Ejecutar el procesamiento de comandos
processCommand().catch(error => {
    console.error('Error en el procesamiento del comando:', error.message);
    console.log(JSON.stringify({ 
        success: false, 
        error: `Error en el procesamiento del comando: ${error.message}` 
    }));
    process.exit(1);
});
