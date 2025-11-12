#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
from alegra_integration import (
    test_connection,
    get_clients,
    get_products,
    get_payment_methods,
    get_invoice_templates,
    prepare_invoice,
    create_invoice
)

def print_json(data):
    """Imprime datos en formato JSON legible."""
    print(json.dumps(data, indent=2))

def test_create_invoice():
    """Prueba la creación de una factura."""
    # Primero obtenemos un cliente válido
    success, clients = get_clients()
    if not success or not clients:
        print("No se pudieron obtener clientes para la prueba")
        return
    
    client_id = clients[0]["id"]
    print(f"Usando cliente: {clients[0]['name']} (ID: {client_id})")
    
    # Luego obtenemos un producto válido
    success, products = get_products()
    if not success or not products:
        print("No se pudieron obtener productos para la prueba")
        return
    
    product_id = products[0]["id"]
    print(f"Usando producto: {products[0]['name']} (ID: {product_id})")
    
    # Preparamos los datos de la factura
    items = [
        {
            "id": product_id,
            "price": 26000,
            "quantity": 1
        }
    ]
    
    invoice_data = prepare_invoice(client_id, items)
    print("\nDatos de factura preparados:")
    print_json(invoice_data)
    
    # Intentamos crear la factura
    print("\nCreando factura...")
    success, response = create_invoice(invoice_data)
    
    if success:
        print("¡Factura creada exitosamente!")
        print_json(response)
    else:
        print(f"Error al crear factura: {response}")
        
        # Intentamos con un formato alternativo para el campo de pago
        print("\nIntentando con formato alternativo para el campo de pago...")
        
        # Formato alternativo 1: paymentForm y paymentMethod como strings
        invoice_data["paymentForm"] = "CASH"
        invoice_data["paymentMethod"] = "CASH"
        if "payment" in invoice_data:
            del invoice_data["payment"]
        
        print("Nuevos datos de factura:")
        print_json(invoice_data)
        
        success, response = create_invoice(invoice_data)
        if success:
            print("¡Factura creada exitosamente con formato alternativo 1!")
            print_json(response)
        else:
            print(f"Error con formato alternativo 1: {response}")
            
            # Formato alternativo 2: payments como array
            if "paymentForm" in invoice_data:
                del invoice_data["paymentForm"]
            if "paymentMethod" in invoice_data:
                del invoice_data["paymentMethod"]
            
            invoice_data["payments"] = [
                {
                    "id": 1,
                    "value": 26000
                }
            ]
            
            print("\nIntentando con formato alternativo 2...")
            print_json(invoice_data)
            
            success, response = create_invoice(invoice_data)
            if success:
                print("¡Factura creada exitosamente con formato alternativo 2!")
                print_json(response)
            else:
                print(f"Error con formato alternativo 2: {response}")

if __name__ == "__main__":
    print("=== Probando conexión con Alegra ===")
    success, response = test_connection()
    if success:
        print("Conexión exitosa")
    else:
        print(f"Error en la conexión: {response}")
        exit(1)
    
    print("\n=== Obteniendo plantillas de factura ===")
    success, template = get_invoice_templates()
    if success:
        print(f"Plantilla obtenida: {template['id']} - {template['name']}")
    else:
        print(f"Error al obtener plantillas: {template}")
    
    print("\n=== Probando creación de factura ===")
    test_create_invoice()
