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
      return response.data;
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

// Abrir factura (cambiar de draft a open)
async function openInvoice(invoiceId) {
  // Formatos a intentar para abrir la factura
  const formats = [
    // Formato 1: Mínimo (vacío)
    {},
    
    // Formato 2: Solo payment
    {
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 3: Con fechas, cliente y payment
    {
      date: new Date().toISOString().split('T')[0],
      dueDate: new Date().toISOString().split('T')[0],
      client: { id: "38" }, // ID del cliente por defecto
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    },
    
    // Formato 4: Con paymentForm y paymentMethod adicionales
    {
      date: new Date().toISOString().split('T')[0],
      dueDate: new Date().toISOString().split('T')[0],
      client: { id: "38" },
      paymentForm: "CASH",
      paymentMethod: "CASH",
      payment: {
        paymentMethod: { id: 10 },
        account: { id: 1 }
      }
    }
  ];
  
  // Intentar cada formato
  for (let i = 0; i < formats.length; i++) {
    try {
      console.log(`Intentando abrir factura con formato ${i + 1}...`);
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
        console.log(`Factura abierta exitosamente con formato ${i + 1}`);
        return {
          success: true,
          data: response.data
        };
      }
    } catch (error) {
      console.error(`Error al intentar formato ${i + 1}:`, error.message);
    }
  }
  
  // Si llegamos aquí, ningún formato funcionó
  throw new Error('No se pudo abrir la factura con ninguno de los formatos intentados');
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
    // 1. Obtener detalles de la factura
    const invoiceDetails = await getInvoiceDetails(facturaId);
    
    // 2. Verificar el estado actual de la factura
    const statusData = await checkInvoiceStatus(facturaId);
    console.log(`Estado actual de la factura: ${statusData.status}`);
    
    // 3. Si la factura está en borrador, intentar abrirla
    if (statusData.status === 'draft') {
      console.log('La factura está en estado borrador, intentando abrirla...');
      await openInvoice(facturaId);
      
      // Verificar nuevamente el estado después de intentar abrirla
      const newStatus = await checkInvoiceStatus(facturaId);
      console.log(`Nuevo estado de la factura: ${newStatus.status}`);
      
      if (newStatus.status !== 'open') {
        throw new Error('No se pudo abrir la factura correctamente');
      }
    } else if (statusData.status !== 'open') {
      throw new Error(`La factura está en estado ${statusData.status} y no puede ser enviada a la DIAN`);
    }
    
    // 4. Enviar la factura a la DIAN
    console.log('Enviando factura a la DIAN...');
    const stampResult = await stampInvoice(facturaId);
    
    // 5. Imprimir el resultado final
    if (stampResult.success) {
      console.log('\n✅ PROCESO COMPLETADO EXITOSAMENTE');
      console.log(`Factura ${facturaId} enviada a la DIAN correctamente`);
    } else {
      console.error('\n❌ ERROR AL ENVIAR FACTURA A LA DIAN');
      console.error(`Mensaje: ${stampResult.error}`);
    }
    
    console.log(JSON.stringify({
      success: stampResult.success,
      data: stampResult.data,
      error: stampResult.error
    }));
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
