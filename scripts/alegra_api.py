#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import requests
import logging
from datetime import datetime
from flask import Flask, request, jsonify

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("alegra_api.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Configuración de la API de Alegra
ALEGRA_API_URL = "https://api.alegra.com/api/v1"
ALEGRA_EMAIL = "pcapacho24@hotmail.com"
ALEGRA_TOKEN = "4398994d2a44f8153123"

app = Flask(__name__)

def get_auth_header():
    """Genera el encabezado de autenticación para la API de Alegra."""
    import base64
    auth_string = f"{ALEGRA_EMAIL}:{ALEGRA_TOKEN}"
    encoded_auth = base64.b64encode(auth_string.encode()).decode()
    return {
        "Authorization": f"Basic {encoded_auth}",
        "Content-Type": "application/json",
        "Accept": "application/json"
    }

def test_connection():
    """Prueba la conexión con la API de Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/company", headers=headers)
        response.raise_for_status()
        logger.info(f"Conexión exitosa con Alegra: {response.json()}")
        return True, response.json()
    except Exception as e:
        logger.error(f"Error al conectar con Alegra: {str(e)}")
        return False, str(e)

def get_electronic_invoice_template():
    """Obtiene la plantilla de factura electrónica activa."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/number-templates", headers=headers)
        response.raise_for_status()
        templates = response.json()
        
        # Buscar una plantilla de factura electrónica activa
        for template in templates:
            if template.get("isElectronic") and template.get("status") == "active":
                logger.info(f"Plantilla de factura electrónica encontrada: {template}")
                return template
        
        logger.warning("No se encontró ninguna plantilla de factura electrónica activa")
        return None
    except Exception as e:
        logger.error(f"Error al obtener plantillas: {str(e)}")
        return None

def create_invoice(data):
    """
    Crea una factura electrónica en Alegra.
    
    Args:
        data (dict): Datos de la factura con el formato:
            {
                "date": "2025-03-10",
                "dueDate": "2025-03-10",
                "client_id": "123",
                "items": [
                    {
                        "id": "1",
                        "price": 26000,
                        "quantity": 1
                    }
                ],
                "payment": {
                    "paymentMethod": {"id": 10},
                    "account": {"id": 1}
                }
            }
    
    Returns:
        tuple: (success, response_data)
    """
    try:
        # Preparar los datos para Alegra
        invoice_data = {}
        
        # Fechas
        invoice_data["date"] = data.get("date", datetime.now().strftime("%Y-%m-%d"))
        invoice_data["dueDate"] = data.get("dueDate", invoice_data["date"])
        
        # Cliente
        client_id = data.get("client_id")
        if client_id:
            invoice_data["client"] = {"id": int(client_id)}
        else:
            logger.error("No se proporcionó un ID de cliente válido")
            return False, "No se proporcionó un ID de cliente válido"
        
        # Método de pago - Formato según la memoria compartida
        invoice_data["payments"] = [
            {
                "paymentMethod": {"id": 10},
                "account": {"id": 1},
                "value": data.get("total", 26000)
            }
        ]
        
        # Items/Productos
        items = data.get("items", [])
        invoice_data["items"] = []
        
        for item in items:
            item_id = item.get("id")
            price = float(item.get("price", 0))
            quantity = float(item.get("quantity", 1))
            
            if item_id:
                invoice_data["items"].append({
                    "id": int(item_id),
                    "price": price,
                    "quantity": quantity
                })
        
        if not invoice_data["items"]:
            logger.error("No se proporcionaron productos válidos")
            return False, "No se proporcionaron productos válidos"
        
        # Plantilla de numeración (factura electrónica)
        template = get_electronic_invoice_template()
        if template:
            invoice_data["numberTemplate"] = {"id": template["id"]}
        else:
            logger.warning("No se encontró una plantilla de factura electrónica. Se usará la plantilla por defecto.")
        
        # Registrar los datos que se enviarán
        logger.info(f"Datos a enviar a Alegra: {json.dumps(invoice_data, indent=2)}")
        
        # Enviar la solicitud a Alegra
        headers = get_auth_header()
        headers["Content-Type"] = "application/json"
        response = requests.post(
            f"{ALEGRA_API_URL}/invoices",
            headers=headers,
            data=json.dumps(invoice_data)
        )
        
        # Verificar la respuesta
        if response.status_code in [200, 201]:
            logger.info(f"Factura creada exitosamente: {response.json()}")
            return True, response.json()
        else:
            logger.error(f"Error al crear factura: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al crear factura: {str(e)}")
        return False, str(e)

def get_payment_methods():
    """
    Obtiene los métodos de pago disponibles en Alegra.
    
    Returns:
        tuple: (success, response_data)
    """
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/payment-methods", headers=headers)
        
        if response.status_code == 200:
            logger.info(f"Métodos de pago obtenidos exitosamente: {response.json()}")
            return True, response.json()
        else:
            logger.error(f"Error al obtener métodos de pago: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al obtener métodos de pago: {str(e)}")
        return False, str(e)

@app.route('/test', methods=['GET'])
def test_api():
    """Endpoint para probar la conexión con Alegra."""
    success, data = test_connection()
    return jsonify({
        "success": success,
        "data": data
    })

@app.route('/invoices', methods=['POST'])
def create_invoice_endpoint():
    """Endpoint para crear una factura en Alegra."""
    try:
        # Obtener los datos de la solicitud
        data = request.json
        logger.info(f"Datos recibidos: {data}")
        
        # Crear la factura
        success, response_data = create_invoice(data)
        
        # Devolver la respuesta
        if success:
            return jsonify({
                "success": True,
                "message": "Factura creada exitosamente",
                "id": response_data.get("id"),
                "data": response_data
            })
        else:
            return jsonify({
                "success": False,
                "message": "Error al crear factura",
                "error": response_data
            }), 400
    except Exception as e:
        logger.error(f"Error en el endpoint: {str(e)}")
        return jsonify({
            "success": False,
            "message": "Error interno del servidor",
            "error": str(e)
        }), 500

@app.route('/payment-methods', methods=['GET'])
def get_payment_methods_endpoint():
    """Endpoint para obtener los métodos de pago disponibles en Alegra."""
    success, data = get_payment_methods()
    
    if success:
        return jsonify({"success": True, "data": data}), 200
    else:
        return jsonify({"success": False, "message": "Error al obtener métodos de pago", "error": data}), 400

if __name__ == "__main__":
    # Si se ejecuta directamente, iniciar el servidor Flask
    app.run(host='0.0.0.0', port=8001, debug=True)
