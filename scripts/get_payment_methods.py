#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json

# URL del servicio de API de Alegra
API_URL = "http://localhost:8001"

def get_payment_methods():
    """Obtiene los métodos de pago disponibles en Alegra."""
    try:
        response = requests.get(f"{API_URL}/payment-methods")
        print(f"Respuesta: {response.status_code}")
        print(json.dumps(response.json(), indent=2))
        return response.json()
    except Exception as e:
        print(f"Error: {str(e)}")
        return None

if __name__ == "__main__":
    print("=== Obteniendo métodos de pago disponibles en Alegra ===")
    get_payment_methods()
