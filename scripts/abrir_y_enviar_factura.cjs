const https = require('https');

// Credenciales de Alegra - Usar las mismas que vimos en los logs
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

// Abrir la factura
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
  
  // Formato exacto según las memorias del usuario
  const openData = {
    payment: {
      paymentMethod: { id: 10 },
      account: { id: 1 }
    }
  };
  
  try {
    console.log(`Abriendo factura ${invoiceId}...`);
    console.log('Datos para abrir factura:', JSON.stringify(openData, null, 2));
    
    const response = await makeRequest(options, JSON.stringify(openData));
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      console.log('Factura abierta correctamente');
      return true;
    } else {
      console.error(`Error al abrir factura: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      
      // Si el error es porque la factura ya está abierta, continuamos
      if (response.data && response.data.code === 3061) {
        console.log('La factura ya está abierta, continuando...');
        return true;
      }
      
      throw new Error(`Error al abrir factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al abrir factura: ${error.message}`);
    throw error;
  }
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
    
    // Si la factura está en estado borrador, abrirla
    if (invoiceDetails.status === 'draft') {
      await openInvoice(facturaId);
      
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
    const result = await stampInvoice(facturaId);
    
    // Imprimir el resultado en formato JSON para que Laravel pueda procesarlo
    console.log(JSON.stringify(result));
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
