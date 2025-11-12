// Script para abrir una factura en Alegra usando Node.js
const https = require('https');

// Obtener el ID de la factura desde los argumentos de la línea de comandos
const idFactura = process.argv[2];

if (!idFactura) {
    console.error('Error: Debe proporcionar el ID de la factura como argumento');
    console.error('Uso: node abrir_factura.js ID_FACTURA');
    process.exit(1);
}

// Credenciales de Alegra (debes reemplazar estos valores con tus credenciales reales)
const email = 'TU_EMAIL'; // Reemplazar con el email real
const token = 'TU_TOKEN'; // Reemplazar con el token real

// Función para verificar el estado de una factura
function verificarEstadoFactura() {
    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'api.alegra.com',
            port: 443,
            path: `/api/v1/invoices/${idFactura}`,
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Basic ' + Buffer.from(`${email}:${token}`).toString('base64')
            }
        };

        const req = https.request(options, (res) => {
            let data = '';

            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                if (res.statusCode >= 200 && res.statusCode < 300) {
                    const factura = JSON.parse(data);
                    console.log(`Estado actual: ${factura.status}`);
                    resolve(factura);
                } else {
                    console.error(`Error al verificar estado: HTTP ${res.statusCode}`);
                    console.error(`Respuesta: ${data}`);
                    reject(new Error(`HTTP ${res.statusCode}`));
                }
            });
        });

        req.on('error', (e) => {
            console.error(`Error en la solicitud: ${e.message}`);
            reject(e);
        });

        req.end();
    });
}

// Función para abrir una factura
function abrirFactura() {
    return new Promise((resolve, reject) => {
        const datos = JSON.stringify({
            paymentForm: 'CASH',
            paymentMethod: 'CASH'
        });

        const options = {
            hostname: 'api.alegra.com',
            port: 443,
            path: `/api/v1/invoices/${idFactura}/open`,
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Basic ' + Buffer.from(`${email}:${token}`).toString('base64'),
                'Content-Length': datos.length
            }
        };

        console.log(`Intentando abrir la factura ${idFactura}...`);
        console.log(`Datos enviados: ${datos}`);

        const req = https.request(options, (res) => {
            let data = '';

            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                console.log(`Respuesta HTTP: ${res.statusCode}`);
                console.log(`Respuesta: ${data}`);

                if (res.statusCode >= 200 && res.statusCode < 300) {
                    resolve(JSON.parse(data));
                } else {
                    reject(new Error(`HTTP ${res.statusCode}: ${data}`));
                }
            });
        });

        req.on('error', (e) => {
            console.error(`Error en la solicitud: ${e.message}`);
            reject(e);
        });

        req.write(datos);
        req.end();
    });
}

// Función para enviar una factura a la DIAN
function enviarFacturaADian() {
    return new Promise((resolve, reject) => {
        const datos = JSON.stringify({
            generateStamp: true,
            generateQrCode: true
        });

        const options = {
            hostname: 'api.alegra.com',
            port: 443,
            path: `/api/v1/invoices/${idFactura}/stamp`,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Basic ' + Buffer.from(`${email}:${token}`).toString('base64'),
                'Content-Length': datos.length
            }
        };

        console.log(`\nIntentando enviar la factura ${idFactura} a la DIAN...`);

        const req = https.request(options, (res) => {
            let data = '';

            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                console.log(`Respuesta HTTP: ${res.statusCode}`);
                console.log(`Respuesta: ${data}`);

                if (res.statusCode >= 200 && res.statusCode < 300) {
                    const responseData = JSON.parse(data);
                    console.log('✅ La factura se envió correctamente a la DIAN.');
                    
                    if (responseData.stamp && responseData.stamp.cufe) {
                        console.log(`CUFE: ${responseData.stamp.cufe}`);
                    }
                    
                    resolve(responseData);
                } else {
                    console.error('❌ Error al enviar la factura a la DIAN.');
                    
                    try {
                        const errorData = JSON.parse(data);
                        if (errorData.message) {
                            console.error(`Mensaje de error: ${errorData.message}`);
                        }
                    } catch (e) {
                        // Ignorar errores de parsing
                    }
                    
                    reject(new Error(`HTTP ${res.statusCode}: ${data}`));
                }
            });
        });

        req.on('error', (e) => {
            console.error(`Error en la solicitud: ${e.message}`);
            reject(e);
        });

        req.write(datos);
        req.end();
    });
}

// Proceso principal
async function main() {
    try {
        console.log(`=== Proceso de envío de factura a la DIAN (Node.js) ===`);
        
        // Verificar estado actual
        const factura = await verificarEstadoFactura();
        
        // Verificar si ya tiene CUFE (ya está emitida)
        if (factura.stamp && factura.stamp.cufe) {
            console.log(`La factura ya está emitida electrónicamente con CUFE: ${factura.stamp.cufe}`);
            return;
        }
        
        // Si la factura está en estado borrador, intentar abrirla
        if (factura.status === 'draft') {
            console.log('La factura está en estado borrador, intentando abrirla...');
            
            await abrirFactura();
            
            // Esperar un momento
            console.log('Esperando 3 segundos...');
            await new Promise(resolve => setTimeout(resolve, 3000));
            
            // Verificar estado nuevamente
            console.log('Verificando estado después de intentar abrir...');
            const facturaActualizada = await verificarEstadoFactura();
            
            if (facturaActualizada.status !== 'open') {
                console.error('❌ La factura no cambió a estado abierto. No se puede enviar a la DIAN.');
                return;
            }
            
            console.log('✅ La factura se abrió correctamente.');
        } else if (factura.status !== 'open') {
            console.error(`La factura está en estado ${factura.status}, no se puede procesar.`);
            return;
        }
        
        // Enviar a la DIAN
        await enviarFacturaADian();
        
    } catch (error) {
        console.error(`Error en el proceso: ${error.message}`);
    }
}

main();
