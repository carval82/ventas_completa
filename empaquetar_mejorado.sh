#!/bin/bash

echo "==================================================="
echo "    EMPAQUETANDO SISTEMA DE VENTAS PARA DISTRIBUCION"
echo "==================================================="
echo ""

FECHA=$(date +"%Y%m%d")
NOMBRE_PAQUETE="sistema_ventas_$FECHA"
TEMP_DIR="$HOME/Documents/temp_$NOMBRE_PAQUETE"
DESTINO="$HOME/Documents/$NOMBRE_PAQUETE.zip"

echo "Limpiando directorios temporales anteriores..."
if [ -d "$TEMP_DIR" ]; then
    rm -rf "$TEMP_DIR"
fi
if [ -f "$DESTINO" ]; then
    rm "$DESTINO"
fi

echo "Creando directorio temporal en Documentos..."
mkdir -p "$TEMP_DIR"
if [ $? -ne 0 ]; then
    echo "Error al crear directorio temporal."
    exit 1
fi

echo "Copiando archivos del proyecto..."
cp -r app "$TEMP_DIR/"
cp -r bootstrap "$TEMP_DIR/"
cp -r config "$TEMP_DIR/"
cp -r database "$TEMP_DIR/"
cp -r lang "$TEMP_DIR/"
cp -r public "$TEMP_DIR/"
cp -r resources "$TEMP_DIR/"
cp -r routes "$TEMP_DIR/"
mkdir -p "$TEMP_DIR/storage/app"
mkdir -p "$TEMP_DIR/storage/framework"
cp -r storage/app "$TEMP_DIR/storage/"
cp -r storage/framework "$TEMP_DIR/storage/"
mkdir -p "$TEMP_DIR/storage/logs"
touch "$TEMP_DIR/storage/logs/.gitkeep"

echo "Copiando archivos adicionales..."
cp .env.example "$TEMP_DIR/"
cp artisan "$TEMP_DIR/"
cp composer.json "$TEMP_DIR/"
cp composer.lock "$TEMP_DIR/"
cp package.json "$TEMP_DIR/"
cp package-lock.json "$TEMP_DIR/"
cp vite.config.js "$TEMP_DIR/"
cp README.md "$TEMP_DIR/"
cp INSTALACION.md "$TEMP_DIR/"
cp instalar.bat "$TEMP_DIR/"
cp instalar.sh "$TEMP_DIR/"

echo "Creando directorios vacíos necesarios..."
mkdir -p "$TEMP_DIR/storage/framework/cache"
mkdir -p "$TEMP_DIR/storage/framework/sessions"
mkdir -p "$TEMP_DIR/storage/framework/views"
mkdir -p "$TEMP_DIR/bootstrap/cache"

echo "Empaquetando archivos..."
cd "$TEMP_DIR"
zip -r "$DESTINO" .
if [ $? -ne 0 ]; then
    echo "Error al empaquetar archivos."
    exit 1
fi

echo "Limpiando archivos temporales..."
rm -rf "$TEMP_DIR"

echo ""
echo "==================================================="
echo "    EMPAQUETADO COMPLETADO"
echo "==================================================="
echo ""
echo "El paquete ha sido creado como: $DESTINO"
echo ""
echo "Este paquete contiene todos los archivos necesarios para"
echo "instalar el sistema en un nuevo servidor."
echo ""
echo "Instrucciones para el cliente:"
echo "1. Descomprimir el archivo ZIP"
echo "2. Ejecutar instalar.bat (Windows) o instalar.sh (Linux/Mac)"
echo "3. Seguir las instrucciones en pantalla"
echo ""

read -p "Presiona Enter para continuar..."
