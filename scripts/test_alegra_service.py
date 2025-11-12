#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json

# URL base del servicio
BASE_URL = "http://localhost:8001"

def print_json(data):
    """Imprime datos en formato JSON legible."""
    print(json.dumps(data, indent=2))

def test_connection():
    """Prueba la conexión con Alegra a través del servicio."""
    print("=== Probando conexión con Alegra ===")
    response = requests.get(f"{BASE_URL}/test")
    print(f"Respuesta: {response.status_code}")
    print_json(response.json())
    return response.json()

def get_clients():
    """Obtiene los clientes de Alegra a través del servicio."""
    print("\n=== Obteniendo clientes ===")
    response = requests.get(f"{BASE_URL}/clients")
    print(f"Respuesta: {response.status_code}")
    data = response.json()
    
    if data.get("success", False):
        print(f"Se obtuvieron {len(data['data'])} clientes")
        print("Primer cliente:")
        print_json(data["data"][0])
    else:
        print(f"Error: {data.get('error')}")
    
    return data

def get_products():
    """Obtiene los productos de Alegra a través del servicio."""
    print("\n=== Obteniendo productos ===")
    response = requests.get(f"{BASE_URL}/products")
    print(f"Respuesta: {response.status_code}")
    data = response.json()
    
    if data.get("success", False):
        print(f"Se obtuvieron {len(data['data'])} productos")
        print("Primer producto:")
        print_json(data["data"][0])
    else:
        print(f"Error: {data.get('error')}")
    
    return data

def get_templates():
    """Obtiene las plantillas de factura de Alegra a través del servicio."""
    print("\n=== Obteniendo plantillas de factura ===")
    response = requests.get(f"{BASE_URL}/templates")
    print(f"Respuesta: {response.status_code}")
    print_json(response.json())
    return response.json()

def create_invoice():
    """Crea una factura en Alegra a través del servicio."""
    print("\n=== Creando factura ===")
    
    # Primero obtenemos un cliente y un producto válidos
    clients_response = requests.get(f"{BASE_URL}/clients")
    clients_data = clients_response.json()
    
    products_response = requests.get(f"{BASE_URL}/products")
    products_data = products_response.json()
    
    if not clients_data.get("success") or not products_data.get("success"):
        print("No se pudieron obtener clientes o productos para la prueba")
        return
    
    client_id = clients_data["data"][0]["id"]
    product_id = products_data["data"][0]["id"]
    
    # Datos de la factura
    invoice_data = {
        "client_id": client_id,
        "date": "2025-03-10",
        "dueDate": "2025-03-10",
        "items": [
            {
                "id": product_id,
                "price": 26000,
                "quantity": 1
            }
        ]
    }
    
    print("Datos de factura a enviar:")
    print_json(invoice_data)
    
    # Enviar solicitud para crear factura
    response = requests.post(f"{BASE_URL}/invoices", json=invoice_data)
    print(f"Respuesta: {response.status_code}")
    print_json(response.json())
    return response.json()

if __name__ == "__main__":
    test_connection()
    get_clients()
    get_products()
    get_templates()
    create_invoice()
