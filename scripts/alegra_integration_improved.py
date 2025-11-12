#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
import base64
import logging
import csv
import os
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

# Rutas de archivos para los mapeos
CLIENTS_MAP_FILE = "alegra_clients_map.csv"
PRODUCTS_MAP_FILE = "alegra_products_map.csv"

# Diccionarios para almacenar los mapeos
local_to_alegra_clients = {}
local_to_alegra_products = {}

def load_mappings():
    """Carga los mapeos de clientes y productos desde los archivos CSV."""
    global local_to_alegra_clients, local_to_alegra_products
    
    # Cargar mapeo de clientes
    if os.path.exists(CLIENTS_MAP_FILE):
        with open(CLIENTS_MAP_FILE, 'r', encoding='utf-8') as file:
            reader = csv.reader(file)
            next(reader)  # Saltar encabezados
            
            for row in reader:
                if len(row) >= 3:
                    alegra_id, name, identification = row[0], row[1], row[2]
                    # Usamos la identificación como clave para el mapeo
                    local_to_alegra_clients[identification] = alegra_id
        
        logger.info(f"Mapeo de clientes cargado: {len(local_to_alegra_clients)} registros")
    else:
        logger.warning(f"No se encontró el archivo de mapeo de clientes: {CLIENTS_MAP_FILE}")
    
    # Cargar mapeo de productos
    if os.path.exists(PRODUCTS_MAP_FILE):
        with open(PRODUCTS_MAP_FILE, 'r', encoding='utf-8') as file:
            reader = csv.reader(file)
            next(reader)  # Saltar encabezados
            
            for row in reader:
                if len(row) >= 2:
                    alegra_id, name = row[0], row[1]
                    # Usamos el nombre como clave para el mapeo
                    local_to_alegra_products[name] = alegra_id
        
        logger.info(f"Mapeo de productos cargado: {len(local_to_alegra_products)} registros")
    else:
        logger.warning(f"No se encontró el archivo de mapeo de productos: {PRODUCTS_MAP_FILE}")

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
    headers = get_auth_header()
    
    try:
        logger.info("Probando conexión con Alegra")
        response = requests.get(f"{ALEGRA_API_URL}/company", headers=headers)
        
        if response.status_code == 200:
            logger.info("Conexión exitosa con Alegra")
            return True, response.json()
        else:
            logger.error(f"Error en la conexión con Alegra: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al probar conexión con Alegra: {str(e)}")
        return False, str(e)

def get_clients():
    """Obtiene los clientes de Alegra."""
    headers = get_auth_header()
    
    try:
        logger.info("Obteniendo clientes de Alegra")
        response = requests.get(f"{ALEGRA_API_URL}/contacts?type=client", headers=headers)
        
        if response.status_code == 200:
            clients = response.json()
            logger.info(f"Se obtuvieron {len(clients)} clientes de Alegra")
            return True, clients
        else:
            logger.error(f"Error al obtener clientes de Alegra: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al obtener clientes de Alegra: {str(e)}")
        return False, str(e)

def get_products():
    """Obtiene los productos de Alegra."""
    headers = get_auth_header()
    
    try:
        logger.info("Obteniendo productos de Alegra")
        response = requests.get(f"{ALEGRA_API_URL}/items", headers=headers)
        
        if response.status_code == 200:
            products = response.json()
            logger.info(f"Se obtuvieron {len(products)} productos de Alegra")
            return True, products
        else:
            logger.error(f"Error al obtener productos de Alegra: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al obtener productos de Alegra: {str(e)}")
        return False, str(e)

def get_payment_methods():
    """Obtiene los métodos de pago de Alegra."""
    headers = get_auth_header()
    
    try:
        logger.info("Obteniendo métodos de pago de Alegra")
        response = requests.get(f"{ALEGRA_API_URL}/payment-methods", headers=headers)
        
        if response.status_code == 200:
            payment_methods = response.json()
            logger.info(f"Se obtuvieron {len(payment_methods)} métodos de pago de Alegra")
            return True, payment_methods
        else:
            logger.error(f"Error al obtener métodos de pago de Alegra: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al obtener métodos de pago de Alegra: {str(e)}")
        return False, str(e)

def get_invoice_templates():
    """Obtiene las plantillas de factura de Alegra."""
    headers = get_auth_header()
    
    try:
        logger.info("Obteniendo plantillas de factura de Alegra")
        response = requests.get(f"{ALEGRA_API_URL}/number-templates", headers=headers)
        
        if response.status_code == 200:
            templates = response.json()
            logger.info(f"Se obtuvieron {len(templates)} plantillas de factura de Alegra")
            return True, templates
        else:
            logger.error(f"Error al obtener plantillas de factura de Alegra: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al obtener plantillas de factura de Alegra: {str(e)}")
        return False, str(e)

def get_alegra_client_id(local_client_id=None, identification=None, name=None):
    """
    Obtiene el ID de Alegra para un cliente.
    
    Args:
        local_client_id: ID local del cliente
        identification: Número de identificación del cliente
        name: Nombre del cliente
    
    Returns:
        ID de Alegra para el cliente o None si no se encuentra
    """
    # Si tenemos la identificación, buscamos en el mapeo
    if identification and identification in local_to_alegra_clients:
        return local_to_alegra_clients[identification]
    
    # Si tenemos el nombre, buscamos en los clientes de Alegra
    if name:
        success, clients = get_clients()
        if success:
            for client in clients:
                if client.get('name', '').lower() == name.lower():
                    return client.get('id')
    
    # Si no encontramos el cliente, retornamos None
    logger.warning(f"No se encontró ID de Alegra para el cliente: local_id={local_client_id}, identification={identification}, name={name}")
    return None

def get_alegra_product_id(local_product_id=None, name=None, reference=None):
    """
    Obtiene el ID de Alegra para un producto.
    
    Args:
        local_product_id: ID local del producto
        name: Nombre del producto
        reference: Referencia del producto
    
    Returns:
        ID de Alegra para el producto o None si no se encuentra
    """
    # Si tenemos el nombre, buscamos en el mapeo
    if name and name in local_to_alegra_products:
        return local_to_alegra_products[name]
    
    # Si tenemos la referencia, buscamos en los productos de Alegra
    if reference:
        success, products = get_products()
        if success:
            for product in products:
                if product.get('reference', '') == reference:
                    return product.get('id')
    
    # Si no encontramos el producto, retornamos None
    logger.warning(f"No se encontró ID de Alegra para el producto: local_id={local_product_id}, name={name}, reference={reference}")
    return None

def prepare_invoice(client_data, items_data, date=None, due_date=None, payment_method="efectivo"):
    """
    Prepara los datos de una factura para enviar a Alegra.
    
    Args:
        client_data: Datos del cliente (puede ser un diccionario con id, identification, name)
        items_data: Lista de datos de productos (cada uno puede ser un diccionario con id, name, price, quantity)
        date: Fecha de la factura (formato YYYY-MM-DD)
        due_date: Fecha de vencimiento de la factura (formato YYYY-MM-DD)
        payment_method: Método de pago (efectivo, tarjeta, etc.)
    
    Returns:
        Diccionario con los datos de la factura preparados para Alegra
    """
    logger.info(f"Preparando factura para cliente: {client_data}, items: {items_data}, método de pago: {payment_method}")
    
    # Obtener ID de Alegra para el cliente
    client_id = None
    if isinstance(client_data, dict):
        client_id = get_alegra_client_id(
            local_client_id=client_data.get('id'),
            identification=client_data.get('identification'),
            name=client_data.get('name')
        )
    else:
        # Asumimos que es un ID local
        client_id = get_alegra_client_id(local_client_id=client_data)
    
    if not client_id:
        logger.error(f"No se pudo obtener ID de Alegra para el cliente: {client_data}")
        return None
    
    # Preparar items de la factura
    invoice_items = []
    for item_data in items_data:
        item_id = None
        if isinstance(item_data, dict):
            item_id = get_alegra_product_id(
                local_product_id=item_data.get('id'),
                name=item_data.get('name'),
                reference=item_data.get('reference')
            )
            
            if item_id:
                invoice_items.append({
                    "id": int(item_id),
                    "price": float(item_data.get('price', 0)),
                    "quantity": float(item_data.get('quantity', 1))
                })
        else:
            # Asumimos que es un ID local
            logger.warning(f"Se proporcionó solo el ID local del producto: {item_data}")
    
    if not invoice_items:
        logger.error(f"No se pudieron preparar los items de la factura: {items_data}")
        return None
    
    # Preparar fecha y fecha de vencimiento
    if not date:
        date = datetime.now().strftime("%Y-%m-%d")
    
    if not due_date:
        due_date = date
    
    # Mapear método de pago
    payment_form = "CASH"
    if payment_method.lower() in ["tarjeta", "credito", "credit"]:
        payment_form = "CREDIT"
    
    # Preparar factura
    invoice = {
        "date": date,
        "dueDate": due_date,
        "client": {"id": int(client_id)},
        "items": invoice_items,
        "paymentForm": payment_form,
        "paymentMethod": payment_form
    }
    
    logger.info(f"Factura preparada: {json.dumps(invoice)}")
    return invoice

def create_invoice(invoice_data):
    """
    Crea una factura en Alegra.
    
    Args:
        invoice_data: Datos de la factura preparados con la función prepare_invoice
    
    Returns:
        (success, response) donde success es un booleano que indica si la operación fue exitosa
        y response es la respuesta de la API o un mensaje de error
    """
    headers = get_auth_header()
    
    try:
        logger.info(f"Creando factura en Alegra: {json.dumps(invoice_data)}")
        response = requests.post(f"{ALEGRA_API_URL}/invoices", headers=headers, json=invoice_data)
        
        if response.status_code in [200, 201]:
            invoice = response.json()
            logger.info(f"Factura creada exitosamente en Alegra: ID={invoice.get('id')}")
            return True, invoice
        else:
            logger.error(f"Error al crear factura en Alegra: {response.status_code} - {response.text}")
            
            # Intentar con formato alternativo si falla
            if "payment" in response.text or "forma de pago" in response.text.lower():
                logger.info("Intentando con formato alternativo para el pago")
                
                # Eliminar campos de pago actuales
                invoice_data.pop("paymentForm", None)
                invoice_data.pop("paymentMethod", None)
                
                # Agregar campo payment con formato alternativo
                invoice_data["payment"] = {
                    "paymentMethod": {"id": 1},  # 1 = Efectivo
                    "account": {"id": 1}  # 1 = Cuenta por defecto
                }
                
                logger.info(f"Creando factura con formato alternativo: {json.dumps(invoice_data)}")
                alt_response = requests.post(f"{ALEGRA_API_URL}/invoices", headers=headers, json=invoice_data)
                
                if alt_response.status_code in [200, 201]:
                    invoice = alt_response.json()
                    logger.info(f"Factura creada exitosamente con formato alternativo: ID={invoice.get('id')}")
                    return True, invoice
                else:
                    logger.error(f"Error al crear factura con formato alternativo: {alt_response.status_code} - {alt_response.text}")
                    return False, alt_response.text
            
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al crear factura en Alegra: {str(e)}")
        return False, str(e)

def create_invoice_from_local_data(venta_id, cliente_id, productos, total, metodo_pago="efectivo"):
    """
    Crea una factura en Alegra a partir de datos locales.
    
    Args:
        venta_id: ID de la venta local
        cliente_id: ID del cliente local
        productos: Lista de productos locales (cada uno debe tener id, cantidad, precio)
        total: Total de la venta
        metodo_pago: Método de pago (efectivo, tarjeta, etc.)
    
    Returns:
        (success, response) donde success es un booleano que indica si la operación fue exitosa
        y response es la respuesta de la API o un mensaje de error
    """
    logger.info(f"Creando factura en Alegra para venta local: venta_id={venta_id}, cliente_id={cliente_id}, productos={productos}, total={total}, metodo_pago={metodo_pago}")
    
    # Preparar datos de la factura
    client_data = {"id": cliente_id}
    items_data = []
    
    for producto in productos:
        items_data.append({
            "id": producto.get("id"),
            "price": producto.get("precio"),
            "quantity": producto.get("cantidad")
        })
    
    # Preparar factura
    invoice_data = prepare_invoice(client_data, items_data, payment_method=metodo_pago)
    
    if not invoice_data:
        return False, "No se pudo preparar la factura con los datos proporcionados"
    
    # Crear factura
    return create_invoice(invoice_data)

# Cargar mapeos al iniciar el módulo
load_mappings()

# Función principal para pruebas
if __name__ == "__main__":
    # Probar conexión
    test_connection()
    
    # Probar creación de factura
    client_data = {"id": 1, "identification": "9999999", "name": "CLIENTE  FRECUENTE"}
    items_data = [
        {"id": 1, "name": "desinstalacion sistema CCTV o camaras de seguridad", "price": 26000, "quantity": 1}
    ]
    
    invoice_data = prepare_invoice(client_data, items_data, payment_method="efectivo")
    
    if invoice_data:
        success, response = create_invoice(invoice_data)
        if success:
            print(f"Factura creada exitosamente: {json.dumps(response, indent=2)}")
        else:
            print(f"Error al crear factura: {response}")
