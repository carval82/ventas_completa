const https = require('https');

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const facturaId = process.argv[2]; // Tomar el ID de la factura como argumento

if (!facturaId) {
  console.error('Debe proporcionar el ID de la factura como argumento');
  process.exit(1);
}

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

// Obtener detalles de la factura
async function getInvoiceDetails(invoiceId) {
  const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: `/api/v1/invoices/${invoiceId}`,
    method: 'GET',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Accept': 'application/json'
    }
  };
  
  try {
    console.log(`Obteniendo detalles de la factura ${invoiceId}...`);
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      console.log('Detalles de la factura obtenidos correctamente');
      return response.data;
    } else {
      console.error(`Error al obtener detalles de la factura: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al obtener detalles de la factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al obtener detalles de la factura: ${error.message}`);
    throw error;
  }
}

// Enviar factura a la DIAN directamente
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
    console.log(`Enviando factura ${invoiceId} a la DIAN directamente...`);
    const response = await makeRequest(options);
    
    console.log(`Respuesta al enviar a DIAN:`, response.statusCode);
    console.log(JSON.stringify(response.data, null, 2));
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      console.log('Factura enviada a la DIAN correctamente');
      return {
        success: true,
        data: response.data
      };
    } else {
      console.error(`Error al enviar factura a la DIAN: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      return {
        success: false,
        error: response.data.message || 'Error desconocido en la integración con Alegra',
        data: response.data
      };
    }
  } catch (error) {
    console.error(`Error al enviar factura a la DIAN: ${error.message}`);
    return {
      success: false,
      error: error.message
    };
  }
}

// Función principal
async function main() {
  try {
    // Obtener detalles de la factura
    const invoiceDetails = await getInvoiceDetails(facturaId);
    
    // Verificar el estado actual de la factura
    console.log(`Estado actual de la factura: ${invoiceDetails.status}`);
    
    // Intentar enviar la factura a la DIAN directamente
    const stampResult = await stampInvoice(facturaId);
    
    // Imprimir el resultado final
    console.log(JSON.stringify({
      success: stampResult.success,
      data: stampResult.data,
      error: stampResult.error
    }));
  } catch (error) {
    console.error(`Error en el proceso: ${error.message}`);
    console.log(JSON.stringify({
      success: false,
      error: error.message
    }));
  }
}

// Ejecutar la función principal
main();
