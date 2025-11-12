#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import sys
from alegra_integration import (
    test_connection,
    get_clients,
    get_products,
    get_invoice_templates,
    prepare_invoice,
    create_invoice
)

def main():
    """Prueba directa de integración con Alegra."""
    print("=== Probando conexión con Alegra ===")
    success, response = test_connection()
    if not success:
        print(f"Error al conectar con Alegra: {response}")
        return
    
    print("Conexión exitosa con Alegra")
    
    # Obtener clientes de Alegra
    print("\n=== Obteniendo clientes de Alegra ===")
    success, clients = get_clients()
    if not success:
        print(f"Error al obtener clientes: {clients}")
        return
    
    print(f"Se encontraron {len(clients)} clientes en Alegra:")
    for i, client in enumerate(clients[:5]):  # Mostrar solo los primeros 5 clientes
        print(f"{i}. ID: {client['id']} - Nombre: {client['name']} - Identificación: {client.get('identification', 'N/A')}")
    
    # Seleccionar el primer cliente para la prueba
    if len(clients) > 0:
        selected_client = clients[0]
        print(f"\nSeleccionando cliente para prueba: ID {selected_client['id']} - {selected_client['name']}")
        
        # Obtener productos de Alegra
        print("\n=== Obteniendo productos de Alegra ===")
        success, products = get_products()
        if not success:
            print(f"Error al obtener productos: {products}")
            return
        
        print(f"Se encontraron {len(products)} productos en Alegra:")
        for i, product in enumerate(products[:5]):  # Mostrar solo los primeros 5 productos
            print(f"{i}. ID: {product['id']} - Nombre: {product['name']} - Precio: {product.get('price', 'N/A')}")
        
        # Seleccionar el primer producto para la prueba
        if len(products) > 0:
            selected_product = products[0]
            print(f"\nSeleccionando producto para prueba: ID {selected_product['id']} - {selected_product['name']}")
            
            # Obtener plantillas de factura
            print("\n=== Obteniendo plantillas de factura ===")
            success, template = get_invoice_templates()
            if not success:
                print(f"Error al obtener plantillas de factura: {template}")
                return
            
            if isinstance(template, dict):
                print(f"Plantilla de factura electrónica encontrada:")
                is_electronic = template.get('isElectronic', False)
                status = template.get('status', 'Desconocido')
                print(f"ID: {template['id']} - Nombre: {template['name']} - Electrónica: {'Sí' if is_electronic else 'No'} - Estado: {status}")
                
                electronic_template = template
            else:
                print(f"Formato de plantilla no válido: {template}")
                electronic_template = None
            
            if electronic_template:
                print(f"\nSeleccionando plantilla electrónica: ID {electronic_template['id']} - {electronic_template['name']}")
                
                # Preparar datos de la factura
                client_id = int(selected_client['id'])
                
                # Extraer el precio del producto correctamente
                product_price = 1000  # Valor por defecto
                if isinstance(selected_product.get('price'), list) and len(selected_product.get('price')) > 0:
                    price_obj = selected_product.get('price')[0]
                    if isinstance(price_obj, dict) and 'price' in price_obj:
                        product_price = float(price_obj['price'])
                
                items = [
                    {
                        'id': int(selected_product['id']),
                        'price': product_price,
                        'quantity': 1
                    }
                ]
                
                # Preparar factura con el formato correcto
                invoice_data = prepare_invoice(client_id, items)
                
                # Asegurarse de que la plantilla de numeración esté configurada
                invoice_data['numberTemplate'] = {'id': int(electronic_template['id'])}
                
                # Asegurarse de que el método de pago esté configurado correctamente
                invoice_data['payment'] = {
                    'paymentMethod': {'id': 10},  # 10 = Efectivo según DIAN
                    'account': {'id': 1}          # Cuenta por defecto
                }
                
                print("\n=== Datos de factura preparados ===")
                print(json.dumps(invoice_data, indent=2))
                
                # Crear factura en Alegra
                print("\n=== Creando factura en Alegra ===")
                success, response = create_invoice(invoice_data)
                
                if success:
                    print("¡Éxito! Factura creada correctamente:")
                    print(json.dumps(response, indent=2))
                else:
                    print(f"Error al crear factura: {response}")
            else:
                print("No se encontró una plantilla electrónica activa. No se puede crear la factura.")
        else:
            print("No se encontraron productos en Alegra. No se puede crear la factura.")
    else:
        print("No se encontraron clientes en Alegra. No se puede crear la factura.")

if __name__ == "__main__":
    main()
