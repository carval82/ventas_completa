#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
import base64

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

def get_payment_methods():
    """Obtiene los métodos de pago disponibles en Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/payment-methods", headers=headers)
        
        print(f"Respuesta de métodos de pago: {response.status_code}")
        if response.status_code == 200:
            print(json.dumps(response.json(), indent=2))
            return response.json()
        else:
            print(f"Error: {response.text}")
            return None
    except Exception as e:
        print(f"Error: {str(e)}")
        return None

def get_invoice_example():
    """Obtiene un ejemplo de factura existente en Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/invoices?limit=1", headers=headers)
        
        print(f"Respuesta de ejemplo de factura: {response.status_code}")
        if response.status_code == 200:
            invoices = response.json()
            if invoices:
                invoice_id = invoices[0]["id"]
                # Obtener detalles de la factura
                response = requests.get(f"{ALEGRA_API_URL}/invoices/{invoice_id}", headers=headers)
                if response.status_code == 200:
                    print(json.dumps(response.json(), indent=2))
                    return response.json()
                else:
                    print(f"Error al obtener detalles de la factura: {response.text}")
            else:
                print("No se encontraron facturas")
            return None
        else:
            print(f"Error: {response.text}")
            return None
    except Exception as e:
        print(f"Error: {str(e)}")
        return None

def get_invoice_templates():
    """Obtiene las plantillas de factura disponibles en Alegra."""
    try:
        headers = get_auth_header()
        response = requests.get(f"{ALEGRA_API_URL}/number-templates", headers=headers)
        
        print(f"Respuesta de plantillas de factura: {response.status_code}")
        if response.status_code == 200:
            templates = response.json()
            # Filtrar plantillas electrónicas activas
            electronic_templates = [t for t in templates if t.get("isElectronic", False) and t.get("status") == "active"]
            
            if electronic_templates:
                print(f"Plantilla de factura electrónica encontrada:")
                print(json.dumps(electronic_templates[0], indent=2))
                return electronic_templates[0]
            else:
                print("No se encontró una plantilla de factura electrónica activa.")
                # Mostrar todas las plantillas
                print("Todas las plantillas disponibles:")
                print(json.dumps(templates, indent=2))
            return None
        else:
            print(f"Error: {response.text}")
            return None
    except Exception as e:
        print(f"Error: {str(e)}")
        return None

if __name__ == "__main__":
    print("=== Obteniendo métodos de pago disponibles en Alegra ===")
    get_payment_methods()
    
    print("\n=== Obteniendo plantillas de factura ===")
    get_invoice_templates()
    
    print("\n=== Obteniendo ejemplo de factura ===")
    get_invoice_example()
