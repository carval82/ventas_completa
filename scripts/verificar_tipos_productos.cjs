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

// Obtener todos los productos y servicios de Alegra
async function getItems() {
  const options = {
    hostname: 'api.alegra.com',
    port: 443,
    path: '/api/v1/items?start=0&limit=30',
    method: 'GET',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Accept': 'application/json'
    }
  };
  
  try {
    console.log('Obteniendo productos y servicios de Alegra...');
    const response = await makeRequest(options);
    
    if (response.statusCode === 200) {
      console.log(`Se encontraron ${response.data.length} elementos`);
      return response.data;
    } else {
      console.error(`Error al obtener elementos: ${response.statusCode}`);
      console.error(JSON.stringify(response.data, null, 2));
      throw new Error(`Error al obtener elementos: ${response.data.message || 'Error desconocido'}`);
    }
  } catch (error) {
    console.error(`Error al obtener elementos: ${error.message}`);
    throw error;
  }
}

// Verificar los tipos de elementos
function checkItemTypes(items) {
  console.log('\n=== DIAGNÓSTICO DE TIPOS DE ELEMENTOS ===\n');
  
  const products = [];
  const services = [];
  
  items.forEach(item => {
    if (item.type === 'product') {
      products.push({
        id: item.id,
        name: item.name,
        hasInventory: item.inventory !== undefined && item.inventory !== null,
        unit: item.inventory?.unit || 'No definida'
      });
    } else if (item.type === 'service') {
      services.push({
        id: item.id,
        name: item.name
      });
    }
  });
  
  console.log(`Productos físicos: ${products.length}`);
  products.forEach(p => {
    console.log(`- ID: ${p.id}, Nombre: ${p.name}, Unidad: ${p.unit}`);
  });
  
  console.log(`\nServicios: ${services.length}`);
  services.forEach(s => {
    console.log(`- ID: ${s.id}, Nombre: ${s.name}`);
  });
  
  return {
    products,
    services
  };
}

// Función principal
async function main() {
  try {
    const items = await getItems();
    checkItemTypes(items);
  } catch (error) {
    console.error(`Error en el proceso: ${error.message}`);
  }
}

// Ejecutar la función principal
main();
