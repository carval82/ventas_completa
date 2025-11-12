#!/usr/bin/env python
# -*- coding: utf-8 -*-

import requests
import json
import base64
import os
import csv

# Configuración de la API de Alegra
ALEGRA_API_URL = "https://api.alegra.com/api/v1"
ALEGRA_EMAIL = "pcapacho24@hotmail.com"
ALEGRA_TOKEN = "4398994d2a44f8153123"

# Rutas de archivos para guardar los mapeos
CLIENTS_MAP_FILE = "alegra_clients_map.csv"
PRODUCTS_MAP_FILE = "alegra_products_map.csv"

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

def get_alegra_clients():
    """Obtiene todos los clientes de Alegra."""
    headers = get_auth_header()
    response = requests.get(f"{ALEGRA_API_URL}/contacts?type=client", headers=headers)
    
    if response.status_code == 200:
        clients = response.json()
        print(f"Se obtuvieron {len(clients)} clientes de Alegra")
        return clients
    else:
        print(f"Error al obtener clientes: {response.text}")
        return []

def get_alegra_products():
    """Obtiene todos los productos de Alegra."""
    headers = get_auth_header()
    response = requests.get(f"{ALEGRA_API_URL}/items", headers=headers)
    
    if response.status_code == 200:
        products = response.json()
        print(f"Se obtuvieron {len(products)} productos de Alegra")
        return products
    else:
        print(f"Error al obtener productos: {response.text}")
        return []

def save_clients_map(clients):
    """Guarda el mapeo de clientes en un archivo CSV."""
    with open(CLIENTS_MAP_FILE, 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        writer.writerow(['ID Alegra', 'Nombre', 'Identificación', 'Email'])
        
        for client in clients:
            writer.writerow([
                client.get('id', ''),
                client.get('name', ''),
                client.get('identification', ''),
                client.get('email', '')
            ])
    
    print(f"Mapeo de clientes guardado en {CLIENTS_MAP_FILE}")

def save_products_map(products):
    """Guarda el mapeo de productos en un archivo CSV."""
    with open(PRODUCTS_MAP_FILE, 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        writer.writerow(['ID Alegra', 'Nombre', 'Referencia', 'Precio'])
        
        for product in products:
            writer.writerow([
                product.get('id', ''),
                product.get('name', ''),
                product.get('reference', ''),
                product.get('price', '')
            ])
    
    print(f"Mapeo de productos guardado en {PRODUCTS_MAP_FILE}")

def create_database_queries():
    """Crea consultas SQL para actualizar la base de datos con los IDs de Alegra."""
    clients_queries = []
    products_queries = []
    
    # Leer mapeo de clientes
    if os.path.exists(CLIENTS_MAP_FILE):
        with open(CLIENTS_MAP_FILE, 'r', encoding='utf-8') as file:
            reader = csv.reader(file)
            next(reader)  # Saltar encabezados
            
            for row in reader:
                if len(row) >= 3:
                    alegra_id, name, identification = row[0], row[1], row[2]
                    query = f"UPDATE clientes SET alegra_id = {alegra_id} WHERE identificacion = '{identification}';"
                    clients_queries.append(query)
    
    # Leer mapeo de productos
    if os.path.exists(PRODUCTS_MAP_FILE):
        with open(PRODUCTS_MAP_FILE, 'r', encoding='utf-8') as file:
            reader = csv.reader(file)
            next(reader)  # Saltar encabezados
            
            for row in reader:
                if len(row) >= 3:
                    alegra_id, name, reference = row[0], row[1], row[2]
                    # Aquí asumimos que podemos buscar por nombre, pero sería mejor buscar por un código único
                    query = f"UPDATE productos SET alegra_id = {alegra_id} WHERE nombre = '{name}';"
                    products_queries.append(query)
    
    # Guardar consultas en archivos
    with open('update_clients_alegra_ids.sql', 'w', encoding='utf-8') as file:
        file.write('\n'.join(clients_queries))
    
    with open('update_products_alegra_ids.sql', 'w', encoding='utf-8') as file:
        file.write('\n'.join(products_queries))
    
    print("Consultas SQL generadas en los archivos update_clients_alegra_ids.sql y update_products_alegra_ids.sql")

def main():
    """Función principal."""
    print("=== Iniciando mapeo de IDs de Alegra ===")
    
    if test_connection():
        # Obtener y guardar mapeo de clientes
        clients = get_alegra_clients()
        if clients:
            save_clients_map(clients)
        
        # Obtener y guardar mapeo de productos
        products = get_alegra_products()
        if products:
            save_products_map(products)
        
        # Crear consultas SQL
        create_database_queries()
    else:
        print("No se pudo establecer conexión con Alegra")

if __name__ == "__main__":
    main()
