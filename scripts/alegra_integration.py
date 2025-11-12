#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
import base64
import logging
from datetime import datetime

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('alegra_integration.log')
    ]
)
logger = logging.getLogger('alegra_integration')

# Configuración de la API de Alegra
ALEGRA_API_URL = "https://api.alegra.com/api/v1"
ALEGRA_EMAIL = "pcapacho24@hotmail.com"
ALEGRA_TOKEN = "4398994d2a44f8153123"

def get_auth_header():
    """Genera el encabezado de autenticación para la API de Alegra."""
    auth_string = f"{ALEGRA_EMAIL}:{ALEGRA_TOKEN}"
    encoded_auth = base64.b64encode(auth_string.encode()).decode()
    return {
        "Authorization": f"Basic {encoded_auth}",
        "Content-Type": "application/json"
    }

def test_connection():
    """Prueba la conexión con la API de Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/company", headers=headers)
        
        logger.info(f"Respuesta del test de conexión: {response.status_code}")
        if response.status_code == 200:
            logger.info(f"Conexión exitosa: {json.dumps(response.json(), indent=2)}")
            return True, response.json()
        else:
            logger.error(f"Error en la conexión: {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Error en la conexión: {str(e)}")
        return False, str(e)

def get_clients():
    """Obtiene la lista de clientes de Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/contacts?type=client", headers=headers)
        
        logger.info(f"Respuesta de obtención de clientes: {response.status_code}")
        if response.status_code == 200:
            clients = response.json()
            logger.info(f"Clientes obtenidos: {len(clients)}")
            return True, clients
        else:
            logger.error(f"Error al obtener clientes: {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Error al obtener clientes: {str(e)}")
        return False, str(e)

def get_products():
    """Obtiene la lista de productos de Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/items", headers=headers)
        
        logger.info(f"Respuesta de obtención de productos: {response.status_code}")
        if response.status_code == 200:
            products = response.json()
            logger.info(f"Productos obtenidos: {len(products)}")
            return True, products
        else:
            logger.error(f"Error al obtener productos: {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Error al obtener productos: {str(e)}")
        return False, str(e)

def get_payment_methods():
    """Obtiene los métodos de pago disponibles en Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/payment-methods", headers=headers)
        
        logger.info(f"Respuesta de obtención de métodos de pago: {response.status_code}")
        if response.status_code == 200:
            payment_methods = response.json()
            logger.info(f"Métodos de pago obtenidos: {len(payment_methods)}")
            return True, payment_methods
        else:
            logger.error(f"Error al obtener métodos de pago: {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Error al obtener métodos de pago: {str(e)}")
        return False, str(e)

def get_invoice_templates():
    """Obtiene las plantillas de factura disponibles en Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/number-templates", headers=headers)
        
        logger.info(f"Respuesta de obtención de plantillas: {response.status_code}")
        if response.status_code == 200:
            templates = response.json()
            # Filtrar plantillas electrónicas activas
            electronic_templates = [t for t in templates if t.get("isElectronic", False) and t.get("status") == "active"]
            
            if electronic_templates:
                logger.info(f"Plantilla de factura electrónica encontrada: {electronic_templates[0]['id']}")
                return True, electronic_templates[0]
            else:
                logger.warning("No se encontró una plantilla de factura electrónica activa")
                if templates:
                    logger.info(f"Usando primera plantilla disponible: {templates[0]['id']}")
                    return True, templates[0]
                else:
                    logger.error("No se encontraron plantillas de factura")
                    return False, "No se encontraron plantillas de factura"
        else:
            logger.error(f"Error al obtener plantillas: {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Error al obtener plantillas: {str(e)}")
        return False, str(e)

def create_invoice(invoice_data):
    """
    Crea una factura en Alegra.
    
    Args:
        invoice_data (dict): Datos completos de la factura
    
    Returns:
        tuple: (success, response_data)
    """
    try:
        headers = get_auth_header()
        
        # Convertir el objeto a JSON
        json_data = json.dumps(invoice_data)
        logger.info(f"Datos de factura a enviar: {json_data}")
        
        # Enviar la solicitud
        response = requests.post(f"{ALEGRA_API_URL}/invoices", headers=headers, data=json_data)
        
        logger.info(f"Respuesta de creación de factura: {response.status_code}")
        if response.status_code in [200, 201]:
            logger.info("Factura creada exitosamente")
            return True, response.json()
        else:
            logger.error(f"Error al crear factura: {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Error al crear factura: {str(e)}")
        return False, str(e)

def prepare_invoice(client_id, items, date=None, due_date=None):
    """
    Prepara los datos de una factura para ser enviada a Alegra.
    
    Args:
        client_id (str): ID del cliente en Alegra
        items (list): Lista de items (productos) con formato [{"id": "1", "price": 100, "quantity": 1}, ...]
        date (str, optional): Fecha de la factura en formato YYYY-MM-DD. Por defecto es la fecha actual.
        due_date (str, optional): Fecha de vencimiento de la factura. Por defecto es igual a date.
    
    Returns:
        dict: Datos de la factura listos para enviar a Alegra
    """
    # Fecha actual si no se proporciona
    if not date:
        date = datetime.now().strftime("%Y-%m-%d")
    
    # Fecha de vencimiento igual a la fecha si no se proporciona
    if not due_date:
        due_date = date
    
    # Estructura básica de la factura
    invoice = {
        "date": date,
        "dueDate": due_date
    }
    
    # Cliente
    if client_id:
        invoice["client"] = {"id": int(client_id)}
    
    # Items/Productos
    invoice_items = []
    for item in items:
        item_id = item.get("id")
        price = float(item.get("price", 0))
        quantity = float(item.get("quantity", 1))
        
        if item_id:
            invoice_items.append({
                "id": int(item_id),
                "price": price,
                "quantity": quantity
            })
    
    invoice["items"] = invoice_items
    
    # Obtener plantilla de factura electrónica
    success, template = get_invoice_templates()
    if success:
        invoice["numberTemplate"] = {"id": template["id"]}
    
    # Formato de pago correcto verificado
    invoice["paymentForm"] = "CASH"
    invoice["paymentMethod"] = "CASH"
    
    return invoice

if __name__ == "__main__":
    # Probar la conexión
    print("=== Probando conexión con Alegra ===")
    test_connection()
    
    # Obtener clientes
    print("\n=== Obteniendo clientes ===")
    success, clients = get_clients()
    if success and clients:
        print(f"Primer cliente: {clients[0]['name']} (ID: {clients[0]['id']})")
    
    # Obtener productos
    print("\n=== Obteniendo productos ===")
    success, products = get_products()
    if success and products:
        print(f"Primer producto: {products[0]['name']} (ID: {products[0]['id']})")
    
    # Obtener métodos de pago
    print("\n=== Obteniendo métodos de pago ===")
    get_payment_methods()
    
    # Obtener plantillas de factura
    print("\n=== Obteniendo plantillas de factura ===")
    get_invoice_templates()
