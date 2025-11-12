// Script para abrir una factura en Alegra usando JavaScript
const https = require('https');
const fs = require('fs');

// Configuración
const ALEGRA_EMAIL = process.env.ALEGRA_EMAIL || 'pcapacho24@hotmail.com';
const ALEGRA_TOKEN = process.env.ALEGRA_TOKEN || '4398994d2a44f8153123'; // Token obtenido

// ID de la factura a abrir (pasado como argumento)
const invoiceId = process.argv[2];

if (!invoiceId) {
    console.error('Error: Debe proporcionar el ID de la factura como argumento');
    console.error('Uso: node abrir_factura.js <id_factura>');
    process.exit(1);
}

console.log(`Intentando abrir factura con ID: ${invoiceId}`);

// Datos para abrir la factura
const data = JSON.stringify({
    payment: {
        paymentMethod: {
            id: 10  // Efectivo
        },
        account: {
            id: 1   // Cuenta por defecto
        }
    }
});

// Opciones para la solicitud HTTP
const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: `/api/v1/invoices/${invoiceId}/open`,
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length,
        'Authorization': 'Basic ' + Buffer.from(`${ALEGRA_EMAIL}:${ALEGRA_TOKEN}`).toString('base64')
    }
};

// Realizar la solicitud
const req = https.request(options, (res) => {
    console.log(`Código de estado: ${res.statusCode}`);
    
    let responseData = '';
    
    res.on('data', (chunk) => {
        responseData += chunk;
    });
    
    res.on('end', () => {
        try {
            const parsedData = JSON.parse(responseData);
            console.log('Respuesta:');
            console.log(JSON.stringify(parsedData, null, 2));
            
            if (res.statusCode >= 200 && res.statusCode < 300) {
                console.log('\n✅ Factura abierta correctamente');
                process.exit(0);
            } else {
                console.log('\n❌ Error al abrir la factura');
                process.exit(1);
            }
        } catch (e) {
            console.log('Respuesta (no es JSON válido):');
            console.log(responseData);
            console.log('\n❌ Error al procesar la respuesta');
            process.exit(1);
        }
    });
});

req.on('error', (e) => {
    console.error(`Error en la solicitud: ${e.message}`);
    process.exit(1);
});

// Enviar los datos
req.write(data);
req.end();
