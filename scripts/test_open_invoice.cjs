const https = require('https');

// Credenciales de Alegra - Usar las mismas que vimos en los logs
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const facturaId = process.argv[2] || '112'; // Tomar el ID de la factura como argumento o usar 112 por defecto

const auth = Buffer.from(`${email}:${token}`).toString('base64');

// Opciones de la solicitud
const options = {
  hostname: 'api.alegra.com',
  port: 443,
  path: `/api/v1/invoices/${facturaId}/open`,
  method: 'POST',
  headers: {
    'Authorization': `Basic ${auth}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

console.log(`Intentando abrir factura con ID: ${facturaId}`);

// Realizar la solicitud
const req = https.request(options, (res) => {
  let data = '';
  
  console.log(`Código de estado: ${res.statusCode}`);
  console.log(`Encabezados: ${JSON.stringify(res.headers)}`);
  
  res.on('data', (chunk) => {
    data += chunk;
  });
  
  res.on('end', () => {
    try {
      const responseData = data ? JSON.parse(data) : {};
      console.log('Respuesta:');
      console.log(JSON.stringify(responseData, null, 2));
      
      console.log(JSON.stringify({
        success: res.statusCode >= 200 && res.statusCode < 300,
        data: responseData
      }));
    } catch (e) {
      console.error('Error al procesar la respuesta:', e.message);
      console.log('Datos recibidos:', data);
      
      console.log(JSON.stringify({
        success: false,
        error: e.message,
        data: data
      }));
    }
  });
});

req.on('error', (e) => {
  console.error(`Error en la solicitud: ${e.message}`);
  
  console.log(JSON.stringify({
    success: false,
    error: e.message
  }));
});

// Enviar la solicitud con datos para resolver el error de formato
const data = JSON.stringify({
  payment: {
    paymentMethod: { id: 10 },
    account: { id: 1 }
  }
});

req.write(data);
req.end();
