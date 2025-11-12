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

// Obtener datos de la factura existente
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

// Crear una nueva factura con el formato correcto
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
    console.log('Creando nueva factura con el formato correcto...');
    console.log('Datos:', JSON.stringify(invoiceData, null, 2));
    
    const response = await makeRequest(options, JSON.stringify(invoiceData));
    
    if (response.statusCode === 201) {
      console.log('Factura creada exitosamente');
      return {
        success: true,
        data: response.data
      };
    } else {
      console.error(`Error al crear factura: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      return {
        success: false,
        error: response.data.message || 'Error desconocido',
        data: response.data
      };
    }
  } catch (error) {
    console.error(`Error al crear factura: ${error.message}`);
    return {
      success: false,
      error: error.message
    };
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
        error: response.data.message || 'Error desconocido',
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
    // Obtener ID de la factura existente como argumento
    const facturaId = process.argv[2];
    
    if (!facturaId) {
      throw new Error('Debe proporcionar el ID de la factura como argumento');
    }
    
    // Obtener detalles de la factura existente
    const invoiceDetails = await getInvoiceDetails(facturaId);
    
    // Preparar datos para la nueva factura con el formato correcto
    const newInvoiceData = {
      date: invoiceDetails.date,
      dueDate: invoiceDetails.dueDate,
      client: { id: parseInt(invoiceDetails.client.id) },
      items: invoiceDetails.items.map(item => ({
        id: parseInt(item.id),
        price: parseFloat(item.price),
        quantity: parseFloat(item.quantity)
      })),
      paymentForm: "CASH",
      paymentMethod: "CASH",
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      },
      numberTemplate: { id: 19 } // Usar la misma plantilla de numeración
    };
    
    // Crear la nueva factura
    const createResult = await createInvoice(newInvoiceData);
    
    if (!createResult.success) {
      throw new Error(`Error al crear la factura: ${createResult.error}`);
    }
    
    const newInvoiceId = createResult.data.id;
    console.log(`Nueva factura creada con ID: ${newInvoiceId}`);
    
    // Esperar un momento para que se procese la factura
    console.log('Esperando 3 segundos antes de enviar a la DIAN...');
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    // Enviar la nueva factura a la DIAN
    const stampResult = await stampInvoice(newInvoiceId);
    
    // Imprimir el resultado final
    console.log(JSON.stringify({
      success: stampResult.success,
      invoiceId: newInvoiceId,
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
