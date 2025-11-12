#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
import base64
from datetime import datetime

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
    headers = get_auth_header()
    response = requests.get(f"{ALEGRA_API_URL}/company", headers=headers)
    
    print(f"Respuesta del test de conexión: {response.status_code}")
    if response.status_code == 200:
        print("Conexión exitosa")
        return True
    else:
        print(f"Error en la conexión: {response.text}")
        return False

def create_invoice():
    """Crea una factura de prueba en Alegra."""
    headers = get_auth_header()
    
    # Datos de la factura
    invoice_data = {
        "date": datetime.now().strftime("%Y-%m-%d"),
        "dueDate": datetime.now().strftime("%Y-%m-%d"),
        "client": {"id": 23},  # ID de un cliente existente
        "items": [
            {
                "id": 45,  # ID de un producto existente
                "price": 26000,
                "quantity": 1
            }
        ],
        # Formato 1: Usar paymentForm y paymentMethod como strings
        "paymentForm": "CASH",
        "paymentMethod": "CASH"
    }
    
    print("Enviando datos de factura:")
    print(json.dumps(invoice_data, indent=2))
    
    # Enviar la solicitud
    response = requests.post(f"{ALEGRA_API_URL}/invoices", headers=headers, json=invoice_data)
    
    print(f"Respuesta de creación de factura: {response.status_code}")
    if response.status_code in [200, 201]:
        print("Factura creada exitosamente:")
        print(json.dumps(response.json(), indent=2))
        return True
    else:
        print(f"Error al crear factura: {response.text}")
        
        # Intentar con formato alternativo
        print("\nIntentando con formato alternativo...")
        
        # Formato 2: Usar payment con paymentMethod y account
        invoice_data.pop("paymentForm", None)
        invoice_data.pop("paymentMethod", None)
        invoice_data["payment"] = {
            "paymentMethod": {"id": 1},
            "account": {"id": 1}
        }
        
        print("Enviando datos de factura (formato alternativo):")
        print(json.dumps(invoice_data, indent=2))
        
        response = requests.post(f"{ALEGRA_API_URL}/invoices", headers=headers, json=invoice_data)
        
        print(f"Respuesta de creación de factura (formato alternativo): {response.status_code}")
        if response.status_code in [200, 201]:
            print("Factura creada exitosamente (formato alternativo):")
            print(json.dumps(response.json(), indent=2))
            return True
        else:
            print(f"Error al crear factura (formato alternativo): {response.text}")
            return False

if __name__ == "__main__":
    print("=== Probando conexión con Alegra ===")
    if test_connection():
        print("\n=== Creando factura de prueba ===")
        create_invoice()
    else:
        print("No se pudo establecer conexión con Alegra")
