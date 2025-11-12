const https = require('https');
const fs = require('fs');

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const auth = Buffer.from(`${email}:${token}`).toString('base64');

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

// Función principal que maneja todo el proceso
async function main() {
  try {
    // Leer el archivo JSON con los datos de la factura
    const invoiceDataPath = process.argv[2];
    if (!invoiceDataPath) {
      throw new Error('Debe proporcionar la ruta al archivo JSON con los datos de la factura');
    }
    
    const invoiceDataRaw = fs.readFileSync(invoiceDataPath, 'utf8');
    const invoiceData = JSON.parse(invoiceDataRaw);
    
    // Asegurarnos de que los datos tengan el formato correcto
    validateInvoiceData(invoiceData);
    
    // 1. Crear la factura
    console.log('Creando factura en Alegra...');
    const createdInvoice = await createInvoice(invoiceData);
    console.log(`Factura creada con ID: ${createdInvoice.id}`);
    
    // 2. Abrir la factura
    console.log('Abriendo factura...');
    await openInvoice(createdInvoice.id);
    
    // Esperar un momento para que se procese el cambio de estado
    console.log('Esperando 3 segundos para que se procese el cambio de estado...');
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    // 3. Enviar la factura a la DIAN
    console.log('Enviando factura a la DIAN...');
    const stampResult = await stampInvoice(createdInvoice.id);
    
    // Devolver el resultado completo
    console.log(JSON.stringify({
      success: true,
      invoice: createdInvoice,
      stamp: stampResult
    }));
    
  } catch (error) {
    console.error(`Error en el proceso: ${error.message}`);
    console.log(JSON.stringify({
      success: false,
      error: error.message
    }));
  }
}

// Validar los datos de la factura
function validateInvoiceData(data) {
  // Verificar campos obligatorios
  const requiredFields = ['client', 'items', 'date', 'dueDate', 'payment', 'numberTemplate'];
  for (const field of requiredFields) {
    if (!data[field]) {
      throw new Error(`Campo obligatorio faltante: ${field}`);
    }
  }
  
  // Verificar formato del cliente
  if (!data.client.id) {
    throw new Error('El cliente debe tener un ID');
  }
  
  // Verificar formato de los items
  if (!Array.isArray(data.items) || data.items.length === 0) {
    throw new Error('La factura debe tener al menos un item');
  }
  
  // Verificar formato del pago
  if (!data.payment.paymentMethod || !data.payment.paymentMethod.id || 
      !data.payment.account || !data.payment.account.id) {
    throw new Error('El formato del pago es incorrecto');
  }
  
  // Verificar formato de la plantilla de numeración
  if (!data.numberTemplate.id) {
    throw new Error('La plantilla de numeración debe tener un ID');
  }
}

// Crear una factura en Alegra
async function createInvoice(invoiceData) {
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
  
  try {
    const response = await makeRequest(options, JSON.stringify(invoiceData));
    
    if (response.statusCode === 201) {
      return response.data;
    } else {
      console.error(`Error al crear factura: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al crear factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al crear factura: ${error.message}`);
    throw error;
  }
}

// Abrir una factura en Alegra
async function openInvoice(invoiceId) {
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
  
  // Datos mínimos para abrir la factura
  const openData = {
    payment: {
      paymentMethod: { id: 10 },
      account: { id: 1 }
    }
  };
  
  try {
    const response = await makeRequest(options, JSON.stringify(openData));
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return response.data;
    } else {
      console.error(`Error al abrir factura: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      
      // Si el error es porque la factura ya está abierta, continuamos
      if (response.data && response.data.code === 3061) {
        console.log('La factura ya está abierta, continuando...');
        return { status: 'open' };
      }
      
      throw new Error(`Error al abrir factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al abrir factura: ${error.message}`);
    throw error;
  }
}

// Enviar una factura a la DIAN
async function stampInvoice(invoiceId) {
  const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: `/api/v1/invoices/${invoiceId}/stamp`,
    method: 'POST',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Accept': 'application/json'
    }
  };
  
  try {
    const response = await makeRequest(options);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return response.data;
    } else {
      console.error(`Error al enviar factura a la DIAN: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al enviar factura a la DIAN: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al enviar factura a la DIAN: ${error.message}`);
    throw error;
  }
}

// Ejecutar la función principal
main();
