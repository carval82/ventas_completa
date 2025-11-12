#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
import base64
import logging
from datetime import datetime
from flask import Flask, request, jsonify

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("alegra_simple.log"),
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
        
        if response.status_code == 200:
            logger.info(f"Conexión exitosa con Alegra: {response.json()}")
            return True, response.json()
        else:
            logger.error(f"Error al conectar con Alegra: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al conectar con Alegra: {str(e)}")
        return False, str(e)

def get_invoice_templates():
    """Obtiene las plantillas de factura disponibles en Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/number-templates", headers=headers)
        
        if response.status_code == 200:
            templates = response.json()
            # Filtrar plantillas electrónicas activas
            electronic_templates = [t for t in templates if t.get("isElectronic", False) and t.get("status") == "active"]
            
            if electronic_templates:
                logger.info(f"Plantilla de factura electrónica encontrada: {electronic_templates[0]}")
                return True, electronic_templates[0]
            else:
                logger.warning("No se encontró una plantilla de factura electrónica activa.")
                return False, "No se encontró una plantilla de factura electrónica activa."
        else:
            logger.error(f"Error al obtener plantillas de factura: {response.status_code} - {response.text}")
            return False, response.text
    except Exception as e:
        logger.error(f"Excepción al obtener plantillas de factura: {str(e)}")
        return False, str(e)

def create_invoice(data):
    """
    Crea una factura en Alegra.
    
    Args:
        data (dict): Datos de la factura
    
    Returns:
        tuple: (success, response_data)
    """
    try:
        # Preparar los datos para Alegra
        invoice_data = {}
        
        # Fechas
        invoice_data["date"] = data.get("date", datetime.now().strftime("%Y-%m-%d"))
        invoice_data["dueDate"] = data.get("dueDate", invoice_data["date"])
        
        # Cliente - Formato según la memoria: client: { id: intval(id_alegra) }
        client_id = data.get("client_id")
        if client_id:
            invoice_data["client"] = {"id": int(client_id)}
        else:
            logger.error("No se proporcionó un ID de cliente válido")
            return False, "No se proporcionó un ID de cliente válido"
        
        # Método de pago - Formato según la memoria: payment: { paymentMethod: { id: 10 }, account: { id: 1 } }
        invoice_data["payment"] = {
            "paymentMethod": {"id": 10},
            "account": {"id": 1}
        }
        
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
        # Según la memoria: Se debe buscar en /number-templates una numeración con isElectronic: true y status: active
        success, template = get_invoice_templates()
        if success:
            invoice_data["numberTemplate"] = {"id": template["id"]}
        
        # Registrar los datos que se enviarán
        logger.info(f"Datos a enviar a Alegra: {json.dumps(invoice_data, indent=2)}")
        
        # Enviar la solicitud a Alegra
        headers = get_auth_header()
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

@app.route('/test', methods=['GET'])
def test_api():
    """Endpoint para probar la conexión con Alegra."""
    success, data = test_connection()
    
    if success:
        return jsonify({"success": True, "data": data}), 200
    else:
        return jsonify({"success": False, "message": "Error al conectar con Alegra", "error": data}), 400

@app.route('/templates', methods=['GET'])
def get_templates_api():
    """Endpoint para obtener las plantillas de factura disponibles en Alegra."""
    success, data = get_invoice_templates()
    
    if success:
        return jsonify({"success": True, "data": data}), 200
    else:
        return jsonify({"success": False, "message": "Error al obtener plantillas", "error": data}), 400

@app.route('/invoices', methods=['POST'])
def create_invoice_api():
    """Endpoint para crear una factura en Alegra."""
    try:
        data = request.json
        success, response = create_invoice(data)
        
        if success:
            return jsonify({"success": True, "data": response}), 201
        else:
            return jsonify({"success": False, "message": "Error al crear factura", "error": response}), 400
    except Exception as e:
        logger.error(f"Excepción en endpoint de creación de factura: {str(e)}")
        return jsonify({
            "success": False,
            "message": "Error interno del servidor",
            "error": str(e)
        }), 500

if __name__ == "__main__":
    # Si se ejecuta directamente, iniciar el servidor Flask
    app.run(host='0.0.0.0', port=8001, debug=True)
