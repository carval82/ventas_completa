#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import sys
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
ALEGRA_TOKEN = "4398994d2a44f8153123"  # Reemplazar con tu token real

app = Flask(__name__)

def get_auth_header():
    """Genera el encabezado de autenticación para la API de Alegra."""
    import base64
    auth_string = f"{ALEGRA_EMAIL}:{ALEGRA_TOKEN}"
    encoded_auth = base64.b64encode(auth_string.encode()).decode()
    return {"Authorization": f"Basic {encoded_auth}"}

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

def get_electronic_invoice_template():
    """Obtiene la plantilla de factura electrónica activa."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/number-templates", headers=headers)
        if response.status_code == 200:
            templates = response.json()
            for template in templates:
                if template.get("isElectronic") and template.get("status") == "active":
                    logger.info(f"Plantilla de factura electrónica encontrada: {template}")
                    return template
            logger.warning("No se encontró ninguna plantilla de factura electrónica activa")
            return None
        else:
            logger.error(f"Error al obtener plantillas: {response.status_code} - {response.text}")
            return None
    except Exception as e:
        logger.error(f"Excepción al obtener plantillas: {str(e)}")
        return None

def create_invoice(invoice_data):
    """
    Crea una factura en Alegra.
    
    Parámetros:
    - invoice_data: Diccionario con los datos de la factura
    
    Retorna:
    - Tuple (success, response_data)
    """
    try:
        # Asegurarse de que los datos tengan el formato correcto
        if "client_id" in invoice_data:
            # Convertir el formato del cliente
            client_id = invoice_data.pop("client_id")
            invoice_data["client"] = {"id": int(client_id)}
        
        # Asegurarse de que el método de pago tenga el formato correcto
        if "payment" not in invoice_data:
            invoice_data["payment"] = {
                "paymentMethod": {"id": 10},  # ID del método de pago (efectivo)
                "account": {"id": 1}          # ID de la cuenta
            }
        
        # Asegurarse de que la fecha tenga el formato correcto
        if "date" in invoice_data and not isinstance(invoice_data["date"], str):
            invoice_data["date"] = invoice_data["date"].strftime("%Y-%m-%d")
        
        if "dueDate" in invoice_data and not isinstance(invoice_data["dueDate"], str):
            invoice_data["dueDate"] = invoice_data["dueDate"].strftime("%Y-%m-%d")
        
        # Obtener la plantilla de factura electrónica
        template = get_electronic_invoice_template()
        if template:
            invoice_data["numberTemplate"] = {"id": template["id"]}
        
        logger.info(f"Datos de factura a enviar: {json.dumps(invoice_data, indent=2)}")
        
        headers = get_auth_header()
        headers["Content-Type"] = "application/json"
        
        response = requests.post(
            f"{ALEGRA_API_URL}/invoices", 
            headers=headers,
            data=json.dumps(invoice_data)
        )
        
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
    return jsonify({
        "success": success,
        "data": data
    })

@app.route('/invoices', methods=['POST'])
def create_invoice_endpoint():
    """Endpoint para crear una factura en Alegra."""
    try:
        invoice_data = request.json
        logger.info(f"Datos recibidos para crear factura: {invoice_data}")
        
        success, response_data = create_invoice(invoice_data)
        
        if success:
            return jsonify({
                "success": True,
                "message": "Factura creada exitosamente",
                "data": response_data
            })
        else:
            return jsonify({
                "success": False,
                "message": "Error al crear factura",
                "error": response_data
            }), 400
    except Exception as e:
        logger.error(f"Error en endpoint de creación de factura: {str(e)}")
        return jsonify({
            "success": False,
            "message": "Error interno del servidor",
            "error": str(e)
        }), 500

if __name__ == "__main__":
    # Si se ejecuta directamente, iniciar el servidor Flask
    app.run(host='0.0.0.0', port=8001, debug=True)
