#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import sys
import os
from alegra_integration import create_invoice, get_invoice_templates

def main():
    """
    Crea una factura en Alegra leyendo los datos desde un archivo JSON.
    
    Uso:
        python create_invoice_from_file.py <ruta_archivo_json>
    """
    try:
        if len(sys.argv) < 2:
            print(json.dumps({
                "success": False,
                "error": "Se requiere la ruta del archivo JSON con los datos de la factura"
            }))
            return
        
        json_file = sys.argv[1]
        
        # Verificar que el archivo existe
        if not os.path.exists(json_file):
            print(json.dumps({
                "success": False,
                "error": f"El archivo {json_file} no existe"
            }))
            return
        
        # Leer datos del archivo JSON
        with open(json_file, 'r', encoding='utf-8') as f:
            invoice_data = json.load(f)
        
        # Verificar si se especificó una plantilla de factura
        if "numberTemplate" not in invoice_data:
            # Obtener una plantilla de factura electrónica activa
            success, template = get_invoice_templates()
            if success:
                invoice_data["numberTemplate"] = {"id": template["id"]}
        
        # Crear la factura en Alegra
        success, response = create_invoice(invoice_data)
        
        # Devolver el resultado
        print(json.dumps({
            "success": success,
            "data" if success else "error": response
        }))
        
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e)
        }))

if __name__ == "__main__":
    main()
