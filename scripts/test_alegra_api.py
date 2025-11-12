#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
from datetime import datetime

# URL del servicio de API de Alegra (el que acabamos de crear)
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
    """Crea una factura de prueba."""
    try:
        # Datos de ejemplo para la factura
        invoice_data = {
            "date": datetime.now().strftime("%Y-%m-%d"),
            "dueDate": datetime.now().strftime("%Y-%m-%d"),
            "client_id": "1",  # Reemplazar con un ID de cliente válido en tu cuenta de Alegra
            "items": [
                {
                    "id": "1",  # Reemplazar con un ID de producto válido en tu cuenta de Alegra
                    "price": 26000,
                    "quantity": 1
                }
            ],
            # Formato correcto del método de pago según las memorias
            "payment": {
                "paymentMethod": {"id": 10},
                "account": {"id": 1}
            }
        }
        
        print(f"Enviando datos de factura: {json.dumps(invoice_data, indent=2)}")
        
        response = requests.post(
            f"{API_URL}/invoices",
            headers={"Content-Type": "application/json"},
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
    
    print("\n=== Creando factura de prueba ===")
    create_invoice()
