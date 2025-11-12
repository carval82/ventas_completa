// Script para crear facturas en Alegra
const axios = require('axios');
const fs = require('fs');

// Configuración de Alegra
const ALEGRA_EMAIL = process.env.ALEGRA_EMAIL || 'carval82@gmail.com';
const ALEGRA_TOKEN = process.env.ALEGRA_TOKEN || 'a5e0f5c3c0b0a3e3b9c4';
const ALEGRA_API_URL = 'https://api.alegra.com/api/v1';

// Autenticación
const auth = Buffer.from(`${ALEGRA_EMAIL}:${ALEGRA_TOKEN}`).toString('base64');

// Cliente Axios para Alegra
const alegraClient = axios.create({
    baseURL: ALEGRA_API_URL,
    headers: {
        'Authorization': `Basic ${auth}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Función para crear una factura
async function createInvoice(invoiceData) {
    try {
        console.log('Datos de factura recibidos:', JSON.stringify(invoiceData, null, 2));
        
        // Asegurarse de que el cliente tenga el formato correcto
        if (invoiceData.client && invoiceData.client.id) {
            invoiceData.client.id = parseInt(invoiceData.client.id);
        }
        
        // Asegurarse de que los items tengan el formato correcto
        if (invoiceData.items && Array.isArray(invoiceData.items)) {
            invoiceData.items = invoiceData.items.map(item => ({
                ...item,
                id: parseInt(item.id),
                price: parseFloat(item.price),
                quantity: parseInt(item.quantity)
            }));
        }
        
        // Asegurarse de que el pago tenga el formato correcto
        if (!invoiceData.paymentForm) {
            invoiceData.paymentForm = 'CASH';
        }
        
        if (!invoiceData.paymentMethod) {
            invoiceData.paymentMethod = 'CASH';
        }
        
        // Formato correcto para el pago según la memoria
        if (!invoiceData.payment || !invoiceData.payment.paymentMethod || !invoiceData.payment.account) {
            invoiceData.payment = {
                paymentMethod: { id: 10 },
                account: { id: 1 }
            };
        }
        
        // Si es factura electrónica, asegurarse de que tenga la numeración correcta
        if (invoiceData.useElectronicInvoice) {
            // Obtener numeraciones disponibles
            const numberTemplatesResponse = await alegraClient.get('/number-templates');
            const numberTemplates = numberTemplatesResponse.data;
            
            // Buscar una numeración electrónica activa
            const electronicTemplate = numberTemplates.find(template => 
                template.isElectronic && template.status === 'active'
            );
            
            if (electronicTemplate) {
                invoiceData.numberTemplate = {
                    id: electronicTemplate.id
                };
                console.log(`Usando numeración electrónica: ${electronicTemplate.id}`);
            } else {
                console.log('No se encontró una numeración electrónica activa');
            }
        }
        
        console.log('Datos de factura procesados:', JSON.stringify(invoiceData, null, 2));
        
        // Crear la factura en Alegra
        const response = await alegraClient.post('/invoices', invoiceData);
        
        console.log('Factura creada exitosamente:', response.data.id);
        return {
            success: true,
            data: response.data
        };
    } catch (error) {
        console.error('Error al crear factura:', error.message);
        
        if (error.response) {
            console.error('Respuesta de error:', error.response.data);
            return {
                success: false,
                error: error.response.data.message || 'Error al crear factura',
                details: error.response.data
            };
        }
        
        return {
            success: false,
            error: error.message
        };
    }
}

// Función principal
async function main() {
    try {
        // Leer datos de factura desde un archivo JSON o argumentos
        let invoiceData;
        
        if (process.argv.length > 2) {
            // Leer desde archivo
            const filePath = process.argv[2];
            const fileContent = fs.readFileSync(filePath, 'utf8');
            invoiceData = JSON.parse(fileContent);
        } else {
            // Leer desde stdin
            const stdin = fs.readFileSync(0, 'utf8');
            invoiceData = JSON.parse(stdin);
        }
        
        const result = await createInvoice(invoiceData);
        console.log(JSON.stringify(result, null, 2));
        
        // Salir con código de estado según el resultado
        process.exit(result.success ? 0 : 1);
    } catch (error) {
        console.error('Error en el script:', error.message);
        process.exit(1);
    }
}

// Ejecutar la función principal
main();
