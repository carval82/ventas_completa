/**
 * Cliente JavaScript para consumir el servicio de integración con Alegra
 */

// URL base del servicio
const BASE_URL = 'http://localhost:8001';

/**
 * Prueba la conexión con Alegra
 * @returns {Promise} Promesa con la respuesta
 */
async function testConnection() {
    try {
        const response = await fetch(`${BASE_URL}/test`);
        return await response.json();
    } catch (error) {
        console.error('Error al probar la conexión:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Obtiene los clientes de Alegra
 * @returns {Promise} Promesa con la respuesta
 */
async function getClients() {
    try {
        const response = await fetch(`${BASE_URL}/clients`);
        return await response.json();
    } catch (error) {
        console.error('Error al obtener clientes:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Obtiene los productos de Alegra
 * @returns {Promise} Promesa con la respuesta
 */
async function getProducts() {
    try {
        const response = await fetch(`${BASE_URL}/products`);
        return await response.json();
    } catch (error) {
        console.error('Error al obtener productos:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Obtiene las plantillas de factura de Alegra
 * @returns {Promise} Promesa con la respuesta
 */
async function getTemplates() {
    try {
        const response = await fetch(`${BASE_URL}/templates`);
        return await response.json();
    } catch (error) {
        console.error('Error al obtener plantillas:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Crea una factura en Alegra
 * @param {Object} invoiceData Datos de la factura
 * @returns {Promise} Promesa con la respuesta
 */
async function createInvoice(invoiceData) {
    try {
        const response = await fetch(`${BASE_URL}/invoices`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(invoiceData)
        });
        return await response.json();
    } catch (error) {
        console.error('Error al crear factura:', error);
        return { success: false, error: error.message };
    }
}

// Exportar funciones para uso en Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        testConnection,
        getClients,
        getProducts,
        getTemplates,
        createInvoice
    };
}
