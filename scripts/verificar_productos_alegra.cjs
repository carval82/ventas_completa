const https = require('https');

// Credenciales de Alegra
const email = 'pcapacho24@hotmail.com';
const token = '4398994d2a44f8153123';
const auth = Buffer.from(`${email}:${token}`).toString('base64');

// Función para hacer una solicitud HTTP
function makeRequest(options) {
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
    
    req.end();
  });
}

// Obtener todos los productos de Alegra
async function getProducts() {
  const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: '/api/v1/items?start=0&limit=30&type=product',
    method: 'GET',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Accept': 'application/json'
    }
  };
  
  try {
    console.log('Obteniendo productos de Alegra...');
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      console.log(`Se encontraron ${response.data.length} productos`);
      return response.data;
    } else {
      console.error(`Error al obtener productos: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al obtener productos: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al obtener productos: ${error.message}`);
    throw error;
  }
}

// Verificar la unidad de medida de los productos
function checkProductUnits(products) {
  console.log('\n=== DIAGNÓSTICO DE UNIDADES DE MEDIDA ===\n');
  
  const problemProducts = [];
  const validProducts = [];
  
  products.forEach(product => {
    const hasInventory = product.inventory !== undefined && product.inventory !== null;
    const hasUnit = hasInventory && product.inventory.unit !== undefined && product.inventory.unit !== null && product.inventory.unit !== '';
    
    if (!hasInventory || !hasUnit || product.inventory.unit === 'N/A') {
      problemProducts.push({
        id: product.id,
        name: product.name,
        unit: hasInventory ? (product.inventory.unit || 'No definida') : 'No tiene inventario'
      });
    } else {
      validProducts.push({
        id: product.id,
        name: product.name,
        unit: product.inventory.unit
      });
    }
  });
  
  console.log(`Productos con unidad de medida válida: ${validProducts.length}`);
  validProducts.forEach(p => {
    console.log(`- ID: ${p.id}, Nombre: ${p.name}, Unidad: ${p.unit}`);
  });
  
  console.log(`\nProductos con problemas de unidad de medida: ${problemProducts.length}`);
  problemProducts.forEach(p => {
    console.log(`- ID: ${p.id}, Nombre: ${p.name}, Unidad: ${p.unit}`);
  });
  
  return {
    validProducts,
    problemProducts
  };
}

// Función principal
async function main() {
  try {
    const products = await getProducts();
    checkProductUnits(products);
  } catch (error) {
    console.error(`Error en el proceso: ${error.message}`);
  }
}

// Ejecutar la función principal
main();
