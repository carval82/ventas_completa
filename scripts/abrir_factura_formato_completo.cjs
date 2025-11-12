const https = require('https');

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const auth = Buffer.from(`${email}:${token}`).toString('base64');

// Tomar el ID de la factura como argumento
const facturaId = process.argv[2];

if (!facturaId) {
  console.error('Debe proporcionar el ID de la factura como argumento');
  process.exit(1);
}

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

// Verificar el estado de la factura
async function checkInvoiceStatus(invoiceId) {
  const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: `/api/v1/invoices/${invoiceId}/status`,
    method: 'GET',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Accept': 'application/json'
    }
  };
  
  try {
    console.log(`Verificando estado de la factura ${invoiceId}...`);
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      console.log(`Estado de la factura: ${response.data.status}`);
      return response.data.status;
    } else {
      console.error(`Error al verificar estado de la factura: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al verificar estado de la factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al verificar estado de la factura: ${error.message}`);
    throw error;
  }
}

// Abrir factura con múltiples formatos
async function openInvoice(invoiceId, invoiceDetails) {
  // Obtener fecha actual en formato YYYY-MM-DD
  const today = new Date().toISOString().split('T')[0];
  
  // Formatos a intentar para abrir la factura
  const formats = [
    // Formato 1: Completo con todos los datos de la factura original
    {
      date: invoiceDetails.date || today,
      dueDate: invoiceDetails.dueDate || today,
      client: { 
        id: parseInt(invoiceDetails.client.id) 
      },
      items: invoiceDetails.items.map(item => ({
        id: parseInt(item.id),
        price: parseFloat(item.price),
        quantity: parseFloat(item.quantity)
      })),
      payment: {
        paymentMethod: { id: 10 }, // Efectivo
        account: { id: 1 }         // Cuenta por defecto
      },
      paymentForm: "CASH",
      paymentMethod: "CASH"
    },
    
    // Formato 2: Solo con cliente y payment
    {
      client: { 
        id: parseInt(invoiceDetails.client.id) 
      },
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 3: Solo payment
    {
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 4: Con payment y paymentForm/paymentMethod
    {
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      },
      paymentForm: "CASH",
      paymentMethod: "CASH"
    },
    
    // Formato 5: Con payment y otras formas de pago
    {
      payment: {
        paymentMethod: { id: 1 }, // Transferencia bancaria
        account: { id: 1 }
      }
    },
    
    // Formato 6: Con payment y otras formas de pago
    {
      payment: {
        paymentMethod: { id: 3 }, // Tarjeta de crédito
        account: { id: 1 }
      }
    },
    
    // Formato 7: Con payment y otras formas de pago
    {
      payment: {
        paymentMethod: { id: 4 }, // Tarjeta de débito
        account: { id: 1 }
      }
    },
    
    // Formato 8: Con payment y paymentForm/paymentMethod diferentes
    {
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      },
      paymentForm: "CREDIT",
      paymentMethod: "CASH"
    },
    
    // Formato 9: Con payment y paymentForm/paymentMethod diferentes
    {
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      },
      paymentForm: "CASH",
      paymentMethod: "CREDIT_CARD"
    },
    
    // Formato 10: Vacío (último recurso)
    {}
  ];
  
  // Intentar cada formato
  for (let i = 0; i < formats.length; i++) {
    try {
      console.log(`\nIntentando abrir factura con formato ${i + 1}...`);
      console.log('Datos:', JSON.stringify(formats[i], null, 2));
      
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
      
      const response = await makeRequest(options, JSON.stringify(formats[i]));
      
      console.log(`Respuesta del formato ${i + 1}:`, response.statusCode);
      console.log(JSON.stringify(response.data, null, 2));
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        console.log(`\n✅ Factura abierta exitosamente con formato ${i + 1}`);
        return {
          success: true,
          formatIndex: i + 1,
          data: response.data
        };
      }
    } catch (error) {
      console.error(`Error al intentar formato ${i + 1}:`, error.message);
    }
  }
  
  // Si llegamos aquí, ningún formato funcionó
  return {
    success: false,
    error: 'No se pudo abrir la factura con ninguno de los formatos intentados'
  };
}

// Función principal
async function main() {
  try {
    // 1. Obtener detalles de la factura
    const invoiceDetails = await getInvoiceDetails(facturaId);
    
    // 2. Verificar el estado actual de la factura
    const currentStatus = await checkInvoiceStatus(facturaId);
    console.log(`Estado actual de la factura: ${currentStatus}`);
    
    // 3. Si la factura no está en borrador, no es necesario abrirla
    if (currentStatus !== 'draft') {
      console.log(`La factura ya está en estado ${currentStatus}, no es necesario abrirla`);
      console.log(JSON.stringify({
        success: true,
        message: `La factura ya está en estado ${currentStatus}`
      }));
      return;
    }
    
    // 4. Intentar abrir la factura con diferentes formatos
    console.log('La factura está en estado borrador, intentando abrirla...');
    const openResult = await openInvoice(facturaId, invoiceDetails);
    
    // 5. Verificar el resultado
    if (openResult.success) {
      // Verificar nuevamente el estado después de abrirla
      const newStatus = await checkInvoiceStatus(facturaId);
      console.log(`\nNuevo estado de la factura: ${newStatus}`);
      
      if (newStatus === 'open') {
        console.log('\n✅ PROCESO COMPLETADO EXITOSAMENTE');
        console.log(`Factura ${facturaId} abierta correctamente con formato ${openResult.formatIndex}`);
        console.log(JSON.stringify({
          success: true,
          formatIndex: openResult.formatIndex,
          data: openResult.data
        }));
      } else {
        console.error('\n❌ ERROR: La factura no cambió a estado "open"');
        console.log(JSON.stringify({
          success: false,
          error: `La factura sigue en estado ${newStatus} a pesar de la respuesta exitosa`
        }));
      }
    } else {
      console.error('\n❌ ERROR AL ABRIR LA FACTURA');
      console.error(`Mensaje: ${openResult.error}`);
      console.log(JSON.stringify({
        success: false,
        error: openResult.error
      }));
    }
  } catch (error) {
    console.error(`\n❌ Error en el proceso: ${error.message}`);
    console.log(JSON.stringify({
      success: false,
      error: error.message
    }));
  }
}

// Ejecutar la función principal
main();
