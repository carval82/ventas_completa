#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
from datetime import datetime

# URL del servicio de API de Alegra
API_URL = "http://localhost:8001"

def test_connection():
    """Prueba la conexión con el servicio de API de Alegra."""
    try:
        response = requests.get(f"{API_URL}/test")
        print(f"Respuesta del test de conexión: {response.status_code}")
        print(json.dumps(response.json(), indent=2))
        return response.json()
    except Exception as e:
        print(f"Error al conectar con el servicio: {str(e)}")
        return None

def create_invoice():
    """Crea una factura de prueba con el nuevo formato de pago."""
    try:
        # Datos de ejemplo para la factura
        invoice_data = {
            "date": datetime.now().strftime("%Y-%m-%d"),
            "dueDate": datetime.now().strftime("%Y-%m-%d"),
            "client_id": "1",  # ID de cliente en Alegra
            "items": [
                {
                    "id": "1",  # ID de producto en Alegra
                    "price": 26000,
                    "quantity": 1
                }
            ],
            "total": 26000  # Total de la factura para el campo payment
        }
        
        # Añadir encabezados necesarios
        headers = {
            "Content-Type": "application/json",
            "Accept": "application/json"
        }
        
        print(f"Enviando datos de factura: {json.dumps(invoice_data, indent=2)}")
        
        response = requests.post(
            f"{API_URL}/invoices",
            headers=headers,
            data=json.dumps(invoice_data)
        )
        
        print(f"Respuesta de creación de factura: {response.status_code}")
        print(json.dumps(response.json(), indent=2))
        
        return response.json()
    except Exception as e:
        print(f"Error al crear factura: {str(e)}")
        return None

if __name__ == "__main__":
    print("=== Probando conexión con Alegra ===")
    test_connection()
    
    print("\n=== Creando factura de prueba con nuevo formato de pago ===")
    create_invoice()
