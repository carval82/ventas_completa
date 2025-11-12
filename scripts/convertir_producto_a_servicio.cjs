const https = require('https');

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

// Obtener detalles de un elemento específico
async function getItemDetails(itemId) {
  const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: `/api/v1/items/${itemId}`,
    method: 'GET',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Accept': 'application/json'
    }
  };
  
  try {
    console.log(`Obteniendo detalles del elemento ID: ${itemId}...`);
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      console.log('Detalles del elemento obtenidos correctamente');
      return response.data;
    } else {
      console.error(`Error al obtener detalles del elemento: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al obtener detalles del elemento: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al obtener detalles del elemento: ${error.message}`);
    throw error;
  }
}

// Convertir un producto a servicio
async function convertProductToService(itemId) {
  try {
    // Primero obtenemos los detalles actuales del elemento
    const itemDetails = await getItemDetails(itemId);
    
    // Preparamos los datos para la actualización
    const updateData = {
      name: itemDetails.name,
      description: itemDetails.description || itemDetails.name,
      reference: itemDetails.reference || '',
      price: itemDetails.price,
      type: 'service' // Cambiar a servicio
    };
    
    // Eliminamos el inventario para servicios
    if (updateData.inventory) {
      delete updateData.inventory;
    }
    
    console.log(`Convirtiendo elemento ID: ${itemId} de producto a servicio`);
    console.log('Datos de actualización:', JSON.stringify(updateData, null, 2));
    
    const options = {
      hostname: 'api.alegra.com',
      port: 443,
      path: `/api/v1/items/${itemId}`,
      method: 'PUT',
      headers: {
        'Authorization': `Basic ${auth}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    };
    
    const response = await makeRequest(options, JSON.stringify(updateData));
    
    if (response.statusCode === 200) {
      console.log(`Elemento ID: ${itemId} convertido correctamente a servicio`);
      return {
        success: true,
        data: response.data
      };
    } else {
      console.error(`Error al convertir elemento: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      return {
        success: false,
        error: response.data.message || 'Error desconocido',
        data: response.data
      };
    }
  } catch (error) {
    console.error(`Error al convertir elemento: ${error.message}`);
    return {
      success: false,
      error: error.message
    };
  }
}

// Función principal
async function main() {
  try {
    // ID del elemento a convertir (de nuestro diagnóstico previo)
    const itemId = process.argv[2] || 45;
    
    // Convertir el elemento de producto a servicio
    const result = await convertProductToService(itemId);
    
    if (result.success) {
      console.log(`\n✅ Elemento ID: ${itemId} convertido exitosamente a servicio`);
    } else {
      console.error(`\n❌ Error al convertir elemento ID: ${itemId}`);
      console.error(`   Mensaje: ${result.error}`);
    }
  } catch (error) {
    console.error(`\n❌ Error en el proceso: ${error.message}`);
  }
}

// Ejecutar la función principal
main();
