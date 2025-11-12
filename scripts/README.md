# Servicio de Integración con Alegra API

Este servicio permite la integración con la API de Alegra para la generación de facturas electrónicas.

## Requisitos

- Python 3.8 o superior
- pip (gestor de paquetes de Python)

## Instalación

1. Instalar las dependencias:

```bash
pip install -r requirements.txt
```

## Configuración

Antes de ejecutar el servicio, asegúrate de configurar correctamente las credenciales de Alegra en el archivo `alegra_api.py`:

```python
# Configuración de la API de Alegra
ALEGRA_API_URL = "https://api.alegra.com/api/v1"
ALEGRA_EMAIL = "tu_email@ejemplo.com"  # Reemplazar con tu email de Alegra
ALEGRA_TOKEN = "tu_token_de_alegra"    # Reemplazar con tu token de Alegra
```

## Ejecución del servicio

Para iniciar el servicio:

```bash
python alegra_api.py
```

El servicio se ejecutará en `http://localhost:8001`.

## Endpoints disponibles

### Probar conexión con Alegra

```
GET /test
```

### Crear factura electrónica

```
POST /invoices
```

Ejemplo de cuerpo de la solicitud:

```json
{
  "date": "2025-03-10",
  "dueDate": "2025-03-10",
  "client_id": "1",
  "items": [
    {
      "id": "1",
      "price": 26000,
      "quantity": 1
    }
  ],
  "payment": {
    "method": "efectivo",
    "amount": 26000
  }
}
```

## Pruebas

Para probar el servicio:

```bash
python test_alegra_api.py
```

## Integración con Laravel

El servicio está configurado para recibir solicitudes desde Laravel. En el controlador `VentaController.php`, se utiliza el método `generarFacturaElectronica` para enviar los datos de la venta al servicio Python.
