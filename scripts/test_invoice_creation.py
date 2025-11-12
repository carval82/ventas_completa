#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json

# URL del servicio
url = "http://localhost:8001/invoices"

# Datos de la factura según el formato correcto de la memoria
invoice_data = {
    "date": "2025-03-10",
    "dueDate": "2025-03-10",
    "client": {
        "id": 24
    },
    "items": [
        {
            "id": 45,
            "price": 26000,
            "quantity": 1
        }
    ],
    "payment": {
        "paymentMethod": {"id": 10},
        "account": {"id": 1}
    },
    "paymentForm": "CASH",
    "paymentMethod": "CASH"
}

# Realizar la solicitud
print("Enviando solicitud a:", url)
print("Datos:", json.dumps(invoice_data, indent=2))

response = requests.post(url, json=invoice_data)

# Mostrar respuesta
print("\nCódigo de respuesta:", response.status_code)
print("Respuesta:", json.dumps(response.json(), indent=2))
