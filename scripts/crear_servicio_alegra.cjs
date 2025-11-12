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

// Crear un nuevo servicio en Alegra
async function createService(name, description, price) {
  try {
    // Preparamos los datos para el nuevo servicio
    const serviceData = {
      name: name,
      description: description || name,
      reference: '',
      price: price,
      type: 'service' // Especificar que es un servicio
    };
    
    console.log('Creando nuevo servicio en Alegra');
    console.log('Datos del servicio:', JSON.stringify(serviceData, null, 2));
    
    const options = {
      hostname: 'api.alegra.com',
      port: 443,
      path: '/api/v1/items',
      method: 'POST',
      headers: {
        'Authorization': `Basic ${auth}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    };
    
    const response = await makeRequest(options, JSON.stringify(serviceData));
    
    if (response.statusCode === 201) {
      console.log('Servicio creado exitosamente');
      return {
        success: true,
        data: response.data
      };
    } else {
      console.error(`Error al crear servicio: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      return {
        success: false,
        error: response.data.message || 'Error desconocido',
        data: response.data
      };
    }
  } catch (error) {
    console.error(`Error al crear servicio: ${error.message}`);
    return {
      success: false,
      error: error.message
    };
  }
}

// Actualizar un producto existente con unidad de medida
async function updateProductUnit(productId, unitName = 'unit') {
  try {
    // Primero obtenemos los detalles actuales del producto
    const productDetails = await getItemDetails(productId);
    
    // Preparamos los datos para la actualización
    let updateData = {
      name: productDetails.name,
      description: productDetails.description || productDetails.name,
      reference: productDetails.reference || '',
      price: productDetails.price
    };
    
    // Si ya tiene inventario, actualizamos solo la unidad
    if (productDetails.inventory) {
      updateData.inventory = {
        ...productDetails.inventory,
        unit: unitName
      };
    } else {
      // Si no tiene inventario, creamos uno básico
      updateData.inventory = {
        unit: unitName,
        availableQuantity: 0,
        unitCost: 0,
        initialQuantity: 0
      };
    }
    
    console.log(`Actualizando producto ID: ${productId} con unidad: ${unitName}`);
    console.log('Datos de actualización:', JSON.stringify(updateData, null, 2));
    
    const options = {
      hostname: 'api.alegra.com',
      port: 443,
      path: `/api/v1/items/${productId}`,
      method: 'PUT',
      headers: {
        'Authorization': `Basic ${auth}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    };
    
    const response = await makeRequest(options, JSON.stringify(updateData));
    
    if (response.statusCode === 200) {
      console.log(`Producto ID: ${productId} actualizado correctamente`);
      return {
        success: true,
        data: response.data
      };
    } else {
      console.error(`Error al actualizar producto: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      return {
        success: false,
        error: response.data.message || 'Error desconocido',
        data: response.data
      };
    }
  } catch (error) {
    console.error(`Error al actualizar producto: ${error.message}`);
    return {
      success: false,
      error: error.message
    };
  }
}

// Función principal
async function main() {
  try {
    // ID del elemento existente (de nuestro diagnóstico previo)
    const productId = process.argv[2] || 45;
    
    // Obtener detalles del producto existente
    const productDetails = await getItemDetails(productId);
    
    // 1. Crear un nuevo servicio con los mismos datos
    const createResult = await createService(
      productDetails.name,
      productDetails.description,
      productDetails.price
    );
    
    if (createResult.success) {
      console.log(`\n✅ Nuevo servicio creado exitosamente con ID: ${createResult.data.id}`);
      
      // 2. Actualizar el producto existente con unidad de medida
      const updateResult = await updateProductUnit(productId, 'unit');
      
      if (updateResult.success) {
        console.log(`\n✅ Producto ID: ${productId} actualizado exitosamente con unidad de medida 'unit'`);
      } else {
        console.error(`\n❌ Error al actualizar producto ID: ${productId}`);
        console.error(`   Mensaje: ${updateResult.error}`);
      }
    } else {
      console.error(`\n❌ Error al crear nuevo servicio`);
      console.error(`   Mensaje: ${createResult.error}`);
    }
  } catch (error) {
    console.error(`\n❌ Error en el proceso: ${error.message}`);
  }
}

// Ejecutar la función principal
main();
