const https = require('https');
const fs = require('fs');
const { execSync } = require('child_process');
const path = require('path');

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
                    const jsonData = responseData ? JSON.parse(responseData) : {};
                    
                    if (res.statusCode >= 200 && res.statusCode < 300) {
                        resolve({ statusCode: res.statusCode, data: jsonData });
                    } else {
                        console.error(`Error en la API de Alegra (${res.statusCode}):`);
                        console.error(jsonData);
                        reject({ statusCode: res.statusCode, data: jsonData });
                    }
                } catch (error) {
                    console.error('Error al procesar la respuesta:', error.message);
                    reject({ statusCode: res.statusCode, error: error.message, rawData: responseData });
                }
            });
        });
        
        req.on('error', (error) => {
            console.error('Error en la solicitud HTTP:', error.message);
            reject({ error: error.message });
        });
        
        if (data) {
            console.log('Enviando datos:');
            console.log(data);
            req.write(data);
        }
        
        req.end();
    });
}

// Verificar las plantillas de numeración
async function checkNumberTemplates(auth) {
    console.log('Verificando plantillas de numeración...');
    
    const options = {
        hostname: 'api.alegra.com',
        port: 443,
        path: '/api/v1/number-templates',
        method: 'GET',
        headers: {
            'Authorization': `Basic ${auth}`,
            'Accept': 'application/json'
        }
    };
    
    try {
        const response = await makeRequest(options);
        console.log('Plantillas de numeración obtenidas correctamente');
        
        // Filtrar las plantillas electrónicas
        const electronicTemplates = response.data.filter(template => template.isElectronic === true);
        
        console.log(`\nTotal de plantillas: ${response.data.length}`);
        console.log(`Plantillas electrónicas: ${electronicTemplates.length}`);
        
        console.log('\nDetalle de plantillas electrónicas:');
        electronicTemplates.forEach(template => {
            console.log(`- ID: ${template.id}, Prefijo: ${template.prefix}, Estado: ${template.status || 'No especificado'}, Tipo: ${template.documentType}`);
        });
        
        return {
            success: true,
            data: response.data,
            electronicTemplates: electronicTemplates
        };
    } catch (error) {
        console.error('Error al verificar plantillas de numeración:', error);
        return {
            success: false,
            error: error
        };
    }
}

// Función principal
async function main() {
    try {
        // Obtener credenciales
        const credentials = getAlegraCredentials();
        console.log(`Usando credenciales: ${credentials.email} / ${credentials.token.substring(0, 3)}...${credentials.token.substring(credentials.token.length - 3)}`);
        
        // Crear token de autenticación
        const auth = Buffer.from(`${credentials.email}:${credentials.token}`).toString('base64');
        
        // Verificar plantillas de numeración
        await checkNumberTemplates(auth);
        
    } catch (error) {
        console.error('Error:', error.message);
        process.exit(1);
    }
}

// Ejecutar la función principal
main().catch(error => {
    console.error('Error en el script:', error.message);
    process.exit(1);
});
