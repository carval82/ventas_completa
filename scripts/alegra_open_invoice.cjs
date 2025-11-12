// Script para abrir una factura en Alegra usando JavaScript
const https = require('https');

// Obtener argumentos
const invoiceId = process.argv[2];
const email = process.argv[3];
const token = process.argv[4];

if (!invoiceId || !email || !token) {
    console.error('Error: Faltan argumentos');
    console.error('Uso: node alegra_open_invoice.cjs <id_factura> <email> <token>');
    process.exit(1);
}

// Datos para abrir la factura (basado en la estructura de una factura abierta)
const data = JSON.stringify({
    paymentForm: "CASH",
    paymentMethod: "CASH"
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
        'Authorization': 'Basic ' + Buffer.from(`${email}:${token}`).toString('base64')
    }
};

// Realizar la solicitud
const req = https.request(options, (res) => {
    let responseData = '';
    
    res.on('data', (chunk) => {
        responseData += chunk;
    });
    
    res.on('end', () => {
        try {
            // Intentar parsear la respuesta como JSON
            const parsedData = JSON.parse(responseData);
            
            // Crear un objeto de respuesta para PHP
            const response = {
                success: res.statusCode >= 200 && res.statusCode < 300,
                status_code: res.statusCode,
                data: parsedData
            };
            
            // Devolver la respuesta como JSON para que PHP pueda procesarla
            console.log(JSON.stringify(response));
            
            // Salir con código 0 si fue exitoso, 1 si hubo error
            process.exit(response.success ? 0 : 1);
        } catch (e) {
            // Si no es JSON válido, devolver la respuesta como texto
            console.log(JSON.stringify({
                success: false,
                status_code: res.statusCode,
                error: 'Error al procesar la respuesta',
                raw_response: responseData
            }));
            process.exit(1);
        }
    });
});

req.on('error', (e) => {
    console.log(JSON.stringify({
        success: false,
        error: e.message
    }));
    process.exit(1);
});

// Enviar los datos
req.write(data);
req.end();
