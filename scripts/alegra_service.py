#!/usr/bin/env python
# -*- coding: utf-8 -*-

from flask import Flask, request, jsonify
import logging
from alegra_integration import (
    test_connection,
    get_clients,
    get_products,
    get_invoice_templates,
    prepare_invoice,
    create_invoice
)

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('alegra_service.log')
    ]
)
logger = logging.getLogger('alegra_service')

app = Flask(__name__)

@app.route('/test', methods=['GET'])
def test():
    """Endpoint para probar la conexión con Alegra."""
    success, response = test_connection()
    if success:
        return jsonify({
            "success": True,
            "message": "Conexión exitosa con Alegra",
            "data": response
        })
    else:
        return jsonify({
            "success": False,
            "message": "Error en la conexión con Alegra",
            "error": response
        }), 500

@app.route('/clients', methods=['GET'])
def clients():
    """Endpoint para obtener los clientes de Alegra."""
    success, response = get_clients()
    if success:
        return jsonify({
            "success": True,
            "message": f"Se obtuvieron {len(response)} clientes",
            "data": response
        })
    else:
        return jsonify({
            "success": False,
            "message": "Error al obtener clientes",
            "error": response
        }), 500

@app.route('/products', methods=['GET'])
def products():
    """Endpoint para obtener los productos de Alegra."""
    success, response = get_products()
    if success:
        return jsonify({
            "success": True,
            "message": f"Se obtuvieron {len(response)} productos",
            "data": response
        })
    else:
        return jsonify({
            "success": False,
            "message": "Error al obtener productos",
            "error": response
        }), 500

@app.route('/templates', methods=['GET'])
def templates():
    """Endpoint para obtener las plantillas de factura de Alegra."""
    success, response = get_invoice_templates()
    if success:
        return jsonify({
            "success": True,
            "message": "Plantilla de factura obtenida",
            "data": response
        })
    else:
        return jsonify({
            "success": False,
            "message": "Error al obtener plantillas de factura",
            "error": response
        }), 500

@app.route('/invoices', methods=['POST'])
def invoices():
    """Endpoint para crear una factura en Alegra."""
    try:
        data = request.json
        logger.info(f"Datos recibidos para crear factura: {data}")
        
        # Validar datos mínimos requeridos
        if not data:
            return jsonify({
                "success": False,
                "message": "No se proporcionaron datos para la factura"
            }), 400
        
        # Validar cliente
        client = data.get("client", {})
        client_id = client.get("id")
        if not client_id:
            return jsonify({
                "success": False,
                "message": "No se proporcionó un ID de cliente válido"
            }), 400
        
        # Validar items
        items = data.get("items", [])
        if not items:
            return jsonify({
                "success": False,
                "message": "No se proporcionaron productos para la factura"
            }), 400
        
        # Usar directamente los datos recibidos si ya están en el formato correcto
        if "client" in data and "paymentForm" in data and "paymentMethod" in data:
            logger.info(f"Usando datos en formato directo: {json.dumps(data)}")
            invoice_data = data
        else:
            # Preparar datos de la factura
            invoice_data = prepare_invoice(
                client_id=client_id,
                items=items,
                date=data.get("date"),
                due_date=data.get("dueDate"),
                payment_method=data.get("payment", {}).get("method", "efectivo")
            )
        
        logger.info(f"Datos de factura preparados: {json.dumps(invoice_data)}")
        
        # Crear factura
        success, response = create_invoice(invoice_data)
        
        if success:
            return jsonify({
                "success": True,
                "message": "Factura creada exitosamente",
                "data": response
            })
        else:
            return jsonify({
                "success": False,
                "message": "Error al crear factura",
                "error": response
            }), 400
    except Exception as e:
        logger.error(f"Error al procesar la solicitud: {str(e)}")
        return jsonify({
            "success": False,
            "message": "Error al procesar la solicitud",
            "error": str(e)
        }), 500

if __name__ == "__main__":
    app.run(host='0.0.0.0', port=8001, debug=True)
