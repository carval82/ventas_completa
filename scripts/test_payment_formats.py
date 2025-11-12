#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
from datetime import datetime

# URL del servicio de API de Alegra
API_URL = "http://localhost:8001"

def test_payment_format(payment_format):
    """Prueba diferentes formatos de pago para la creación de facturas."""
    try:
        # Datos base para la factura
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
            "payment": payment_format
        }
        
        # Añadir encabezados necesarios
        headers = {
            "Content-Type": "application/json",
            "Accept": "application/json"
        }
        
        print(f"\n=== Probando formato de pago ===")
        print(f"Formato: {json.dumps(payment_format, indent=2)}")
        
        response = requests.post(
            f"{API_URL}/invoices",
            headers=headers,
            data=json.dumps(invoice_data)
        )
        
        print(f"Respuesta: {response.status_code}")
        print(json.dumps(response.json(), indent=2))
        
        return response.json()
    except Exception as e:
        print(f"Error: {str(e)}")
        return None

if __name__ == "__main__":
    # Probar diferentes formatos de pago
    payment_formats = [
        # Formato 1: Estructura básica
        {
            "paymentMethod": {"id": 10},
            "account": {"id": 1}
        },
        
        # Formato 2: Con valor
        {
            "paymentMethod": {"id": 10},
            "account": {"id": 1},
            "value": 26000
        },
        
        # Formato 3: Con método de pago como número
        {
            "paymentMethod": 10,
            "account": {"id": 1}
        },
        
        # Formato 4: Con cuenta como número
        {
            "paymentMethod": {"id": 10},
            "account": 1
        },
        
        # Formato 5: Ambos como números
        {
            "paymentMethod": 10,
            "account": 1
        },
        
        # Formato 6: Con diferentes IDs de método de pago
        {
            "paymentMethod": {"id": 1},
            "account": {"id": 1}
        },
        
        # Formato 7: Otro ID de método de pago
        {
            "paymentMethod": {"id": 2},
            "account": {"id": 1}
        }
    ]
    
    # Probar cada formato
    for payment_format in payment_formats:
        test_payment_format(payment_format)
