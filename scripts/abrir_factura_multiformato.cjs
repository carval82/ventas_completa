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

// Intentar abrir la factura con diferentes formatos
async function tryOpenInvoice(invoiceId, invoiceDetails) {
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
  
  // Diferentes formatos para intentar
  const formats = [
    // Formato 1: Vacío
    {},
    
    // Formato 2: Solo payment
    {
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 3: Con datos de la factura original
    {
      date: invoiceDetails.date,
      dueDate: invoiceDetails.dueDate,
      client: { id: invoiceDetails.client.id },
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 4: Con datos de la factura original y forma de pago
    {
      date: invoiceDetails.date,
      dueDate: invoiceDetails.dueDate,
      client: { id: invoiceDetails.client.id },
      paymentForm: "CASH",
      paymentMethod: "CASH",
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 5: Con datos de la factura original, forma de pago y items
    {
      date: invoiceDetails.date,
      dueDate: invoiceDetails.dueDate,
      client: { id: invoiceDetails.client.id },
      paymentForm: "CASH",
      paymentMethod: "CASH",
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      },
      items: invoiceDetails.items
    }
  ];
  
  // Probar cada formato
  for (let i = 0; i < formats.length; i++) {
    const format = formats[i];
    
    try {
      console.log(`Intentando abrir factura con formato ${i + 1}...`);
      console.log('Datos:', JSON.stringify(format, null, 2));
      
      const response = await makeRequest(options, JSON.stringify(format));
      
      console.log(`Respuesta del formato ${i + 1}:`, response.statusCode);
      console.log(JSON.stringify(response.data, null, 2));
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        console.log(`Factura abierta correctamente con formato ${i + 1}`);
        return {
          success: true,
          format: i + 1,
          data: response.data
        };
      }
      
      // Si el error es porque la factura ya está abierta, terminamos
      if (response.data && response.data.code === 3061) {
        console.log('La factura ya está abierta, continuando...');
        return {
          success: true,
          format: 'already_open',
          data: response.data
        };
      }
    } catch (error) {
      console.error(`Error al intentar formato ${i + 1}:`, error.message);
    }
  }
  
  return {
    success: false,
    error: 'No se pudo abrir la factura con ninguno de los formatos intentados'
  };
}

// Enviar factura a la DIAN
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
    console.log(`Enviando factura ${invoiceId} a la DIAN...`);
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
    
    // Si la factura está en estado borrador, intentar abrirla
    let openResult = { success: true };
    if (invoiceDetails.status === 'draft') {
      openResult = await tryOpenInvoice(facturaId, invoiceDetails);
      
      if (!openResult.success) {
        throw new Error(openResult.error);
      }
      
      // Esperar un momento para que se procese el cambio de estado
      console.log('Esperando 3 segundos para que se procese el cambio de estado...');
      await new Promise(resolve => setTimeout(resolve, 3000));
      
      // Verificar nuevamente el estado de la factura
      const updatedInvoiceDetails = await getInvoiceDetails(facturaId);
      console.log(`Estado actualizado de la factura: ${updatedInvoiceDetails.status}`);
      
      if (updatedInvoiceDetails.status !== 'open') {
        throw new Error(`No se pudo abrir la factura. Estado actual: ${updatedInvoiceDetails.status}`);
      }
    }
    
    // Enviar la factura a la DIAN
    const stampResult = await stampInvoice(facturaId);
    
    // Imprimir el resultado final
    console.log(JSON.stringify({
      success: stampResult.success,
      openFormat: openResult.format,
      openData: openResult.data,
      stampData: stampResult.data,
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
