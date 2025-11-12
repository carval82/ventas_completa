import https from 'https';

// Credenciales de Alegra (reemplazar con las correctas)
const email = process.env.ALEGRA_EMAIL || 'tu_email@ejemplo.com';
const token = process.env.ALEGRA_TOKEN || 'tu_token';
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

// Enviar la solicitud
req.end();
