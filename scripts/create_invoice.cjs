const fs = require('fs');
const https = require('https');

// Leer datos de la factura
const invoiceDataPath = process.argv[2];
const invoiceData = JSON.parse(fs.readFileSync(invoiceDataPath, 'utf8'));

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const auth = Buffer.from(`${email}:${token}`).toString('base64');

// Opciones de la solicitud
const options = {
  hostname: 'api.alegra.com',
  port: 443,
  path: '/api/v1/invoices',
  method: 'POST',
  headers: {
    'Authorization': `Basic ${auth}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

console.log('Enviando datos a Alegra...');
console.log(JSON.stringify(invoiceData, null, 2));

// Realizar la solicitud
const req = https.request(options, (res) => {
  console.log(`Código de estado: ${res.statusCode}`);
  
  let data = '';
  
  res.on('data', (chunk) => {
    data += chunk;
  });
  
  res.on('end', () => {
    try {
      const responseData = JSON.parse(data);
      console.log('Respuesta de Alegra:');
      console.log(JSON.stringify(responseData, null, 2));
      
      if (res.statusCode >= 200 && res.statusCode < 300) {
        console.log('Factura creada exitosamente');
        process.exit(0);
      } else {
        console.error('Error al crear factura:');
        console.error(JSON.stringify(responseData, null, 2));
        process.exit(1);
      }
    } catch (e) {
      console.error('Error al procesar la respuesta:', e.message);
      console.error('Datos recibidos:', data);
      process.exit(1);
    }
  });
});

req.on('error', (e) => {
  console.error('Error en la solicitud:', e.message);
  process.exit(1);
});

// Enviar los datos
req.write(JSON.stringify(invoiceData));
req.end();