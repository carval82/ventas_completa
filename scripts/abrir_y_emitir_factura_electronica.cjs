const https = require('https');
const fs = require('fs');
const { execSync } = require('child_process');

// Obtener las credenciales de Alegra desde la base de datos
function getAlegraCredentials() {
    try {
        // Ejecutar el script PHP que obtiene las credenciales
        const output = execSync('php ' + __dirname + '/get_alegra_credentials.php', { encoding: 'utf8' });
        const credentials = JSON.parse(output);
        
        if (credentials.success && credentials.email && credentials.token) {
            return {
                email: credentials.email,
                token: credentials.token
            };
        } else {
            throw new Error('Credenciales de Alegra no válidas: ' + (credentials.error || 'Email o token vacíos'));
        }
    } catch (error) {
        console.error('Error al obtener credenciales de Alegra:', error.message);
        throw new Error('No se pudieron obtener las credenciales de Alegra. Verifique la configuración en la empresa o en el archivo .env');
    }
}

// Obtener credenciales
const credentials = getAlegraCredentials();
const email = credentials.email;
const token = credentials.token;
const auth = Buffer.from(`${email}:${token}`).toString('base64');

// Tomar el ID de la factura como argumento
const facturaId = process.argv[2];

if (!facturaId) {
  console.error('Debe proporcionar el ID de la factura como argumento');
  process.exit(1);
}

// Registrar en un archivo de log
function log(message, data = null) {
  const timestamp = new Date().toISOString();
  const logMessage = `[${timestamp}] ${message}`;
  
  console.log(logMessage);
  
  if (data) {
    console.log(JSON.stringify(data, null, 2));
  }
  
  // También guardar en archivo
  fs.appendFileSync(
    'alegra_factura_electronica.log', 
    logMessage + (data ? '\n' + JSON.stringify(data, null, 2) : '') + '\n',
    'utf8'
  );
}

// Registrar las credenciales que se están utilizando (sin mostrar el token completo)
log(`Usando credenciales: ${email} / ${token.substring(0, 3)}...${token.substring(token.length - 3)}`);

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
    log(`Obteniendo detalles de la factura ${invoiceId}...`);
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      log('Detalles de la factura obtenidos correctamente');
      return response.data;
    } else {
      log(`Error al obtener detalles de la factura: ${response.statusCode}`, response.data);
      throw new Error(`Error al obtener detalles de la factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    log(`Error al obtener detalles de la factura: ${error.message}`);
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
    log(`Verificando estado de la factura ${invoiceId}...`);
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      log(`Estado de la factura: ${response.data.status}`);
      return response.data.status;
    } else {
      log(`Error al verificar estado de la factura: ${response.statusCode}`, response.data);
      throw new Error(`Error al verificar estado de la factura: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    log(`Error al verificar estado de la factura: ${error.message}`);
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
    }
  ];
  
  // Intentar cada formato
  for (let i = 0; i < formats.length; i++) {
    try {
      log(`\nIntentando abrir factura con formato ${i + 1}...`, formats[i]);
      
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
      
      log(`Respuesta del formato ${i + 1}: ${response.statusCode}`, response.data);
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        log(`\n✅ Factura abierta exitosamente con formato ${i + 1}`);
        return {
          success: true,
          formatIndex: i + 1,
          data: response.data
        };
      }
    } catch (error) {
      log(`Error al intentar formato ${i + 1}: ${error.message}`);
    }
  }
  
  // Si llegamos aquí, ningún formato funcionó
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
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  };
  
  try {
    log(`Enviando factura ${invoiceId} a la DIAN...`);
    
    // Enviamos un objeto vacío como cuerpo de la solicitud
    const response = await makeRequest(options, JSON.stringify({
      generateStamp: true,
      generateQrCode: true
    }));
    
    log(`Respuesta al enviar a DIAN: ${response.statusCode}`, response.data);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      log('✅ Factura enviada a la DIAN correctamente');
      return {
        success: true,
        data: response.data
      };
    } else {
      log(`❌ Error al enviar factura a la DIAN: ${response.statusCode}`, response.data);
      return {
        success: false,
        error: response.data.message || 'Error desconocido en la integración con Alegra',
        data: response.data
      };
    }
  } catch (error) {
    log(`❌ Error al enviar factura a la DIAN: ${error.message}`);
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
    const currentStatus = await checkInvoiceStatus(facturaId);
    log(`Estado actual de la factura: ${currentStatus}`);
    
    // 3. Si la factura está en estado borrador, intentar abrirla
    let openResult = { success: true };
    if (currentStatus === 'draft') {
      log('La factura está en estado borrador, intentando abrirla...');
      openResult = await openInvoice(facturaId, invoiceDetails);
      
      if (!openResult.success) {
        log('❌ ERROR AL ABRIR LA FACTURA', { error: openResult.error });
        console.log(JSON.stringify({
          success: false,
          error: openResult.error
        }));
        return;
      }
      
      // Verificar nuevamente el estado después de abrirla
      const newStatus = await checkInvoiceStatus(facturaId);
      log(`\nNuevo estado de la factura: ${newStatus}`);
      
      if (newStatus !== 'open') {
        log('❌ ERROR: La factura no cambió a estado "open"');
        console.log(JSON.stringify({
          success: false,
          error: `La factura sigue en estado ${newStatus} a pesar de la respuesta exitosa`
        }));
        return;
      }
    } else if (currentStatus !== 'open') {
      log(`❌ ERROR: La factura está en estado ${currentStatus}, no se puede enviar a la DIAN`);
      console.log(JSON.stringify({
        success: false,
        error: `La factura está en estado ${currentStatus}, debe estar en estado "open" para enviarla a la DIAN`
      }));
      return;
    }
    
    // 4. Si la factura está abierta, enviarla a la DIAN
    log('Factura en estado "open", enviando a la DIAN...');
    const stampResult = await stampInvoice(facturaId);
    
    // 5. Verificar el resultado final
    if (stampResult.success) {
      log('\n✅ PROCESO COMPLETADO EXITOSAMENTE');
      log(`Factura ${facturaId} enviada correctamente a la DIAN`);
      console.log(JSON.stringify({
        success: true,
        data: stampResult.data
      }));
    } else {
      log('\n❌ ERROR AL ENVIAR LA FACTURA A LA DIAN');
      log(`Mensaje: ${stampResult.error}`);
      console.log(JSON.stringify({
        success: false,
        error: stampResult.error
      }));
    }
  } catch (error) {
    log(`\n❌ Error en el proceso: ${error.message}`);
    console.log(JSON.stringify({
      success: false,
      error: error.message
    }));
  }
}

// Ejecutar la función principal
main();
