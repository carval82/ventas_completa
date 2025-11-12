// Script para emitir una factura electrónica en Alegra
const https = require('https');

// Parámetros recibidos como argumentos
const idFactura = process.argv[2];
const email = process.argv[3];
const token = process.argv[4];

if (!idFactura || !email || !token) {
    console.error('Error: Se requieren los parámetros idFactura, email y token');
    process.exit(1);
}

// Datos para la solicitud
const data = JSON.stringify({
    generateStamp: true,
    generateQrCode: true
});

// Opciones para la solicitud
const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: `/api/v1/invoices/${idFactura}/stamp`,
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Basic ' + Buffer.from(email + ':' + token).toString('base64'),
        'Content-Length': data.length
    }
};

// Realizar la solicitud
const req = https.request(options, (res) => {
    let responseData = '';

    // Recopilar los datos de la respuesta
    res.on('data', (chunk) => {
        responseData += chunk;
    });

    // Procesar la respuesta completa
    res.on('end', () => {
        console.log(JSON.stringify({
            status: res.statusCode,
            headers: res.headers,
            body: responseData ? JSON.parse(responseData) : {}
        }));
    });
});

// Manejar errores
req.on('error', (error) => {
    console.error(JSON.stringify({
        error: error.message
    }));
    process.exit(1);
});

// Enviar los datos
req.write(data);
req.end();
