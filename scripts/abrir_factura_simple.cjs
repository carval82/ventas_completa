const https = require('https');

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const facturaId = process.argv[2]; // Tomar el ID de la factura como argumento

if (!facturaId) {
  console.error('Debe proporcionar el ID de la factura como argumento');
  process.exit(1);
}

// Configuración de la solicitud HTTP
const options = {
  hostname: 'api.alegra.com',
  port: 443,
  path: `/api/v1/invoices/${facturaId}/open`,
  method: 'POST',
  headers: {
    'Authorization': `Basic ${Buffer.from(`${email}:${token}`).toString('base64')}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

// Datos para abrir la factura - probamos un formato más simple
const data = JSON.stringify({});

// Realizar la solicitud HTTP
const req = https.request(options, (res) => {
  let responseData = '';
  
  // Mostrar el código de estado HTTP
  console.log(`Código de estado: ${res.statusCode}`);
  
  res.on('data', (chunk) => {
    responseData += chunk;
  });
  
  res.on('end', () => {
    try {
      // Intentar parsear la respuesta como JSON
      const parsedData = responseData ? JSON.parse(responseData) : {};
      console.log('Respuesta:', JSON.stringify(parsedData, null, 2));
      
      if (res.statusCode >= 200 && res.statusCode < 300) {
        console.log('Factura abierta correctamente');
      } else {
        console.error('Error al abrir la factura');
      }
    } catch (e) {
      console.error('Error al parsear la respuesta:', e.message);
      console.log('Respuesta cruda:', responseData);
    }
  });
});

req.on('error', (e) => {
  console.error(`Error en la solicitud: ${e.message}`);
});

// Enviar los datos
req.write(data);
req.end();

console.log(`Intentando abrir la factura ${facturaId}...`);
console.log('Datos enviados:', data);
