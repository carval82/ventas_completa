#!/bin/bash

echo "==================================================="
echo "    EMPAQUETANDO SISTEMA DE VENTAS PARA DISTRIBUCION"
echo "==================================================="
echo ""

FECHA=$(date +"%Y%m%d")
NOMBRE_PAQUETE="sistema_ventas_$FECHA"

echo "Creando directorio temporal..."
mkdir temp_$NOMBRE_PAQUETE
if [ $? -ne 0 ]; then
    echo "Error al crear directorio temporal."
    exit 1
fi

echo "Copiando archivos del proyecto..."
rsync -av --exclude=vendor --exclude=.git --exclude=node_modules --exclude="storage/logs/*.log" --exclude="*.zip" --exclude="temp_*" . temp_$NOMBRE_PAQUETE/
if [ $? -ne 0 ]; then
    echo "Error al copiar archivos."
    exit 1
fi

echo "Eliminando archivos y directorios innecesarios..."
rm -rf temp_$NOMBRE_PAQUETE/.git 2>/dev/null
rm -rf temp_$NOMBRE_PAQUETE/node_modules 2>/dev/null
rm -rf temp_$NOMBRE_PAQUETE/vendor 2>/dev/null
rm -f temp_$NOMBRE_PAQUETE/.env 2>/dev/null
rm -f temp_$NOMBRE_PAQUETE/*.zip 2>/dev/null
rm -f temp_$NOMBRE_PAQUETE/storage/logs/*.log 2>/dev/null

echo "Creando directorios necesarios..."
mkdir -p temp_$NOMBRE_PAQUETE/storage/logs
mkdir -p temp_$NOMBRE_PAQUETE/storage/framework/cache
mkdir -p temp_$NOMBRE_PAQUETE/storage/framework/sessions
mkdir -p temp_$NOMBRE_PAQUETE/storage/framework/views
mkdir -p temp_$NOMBRE_PAQUETE/bootstrap/cache

echo "Creando archivo .env.example..."
cp .env.example temp_$NOMBRE_PAQUETE/.env.example

echo "Empaquetando archivos..."
cd temp_$NOMBRE_PAQUETE
zip -r ../$NOMBRE_PAQUETE.zip .
if [ $? -ne 0 ]; then
    echo "Error al empaquetar archivos."
    exit 1
fi
cd ..

echo "Limpiando archivos temporales..."
rm -rf temp_$NOMBRE_PAQUETE

echo ""
echo "==================================================="
echo "    EMPAQUETADO COMPLETADO"
echo "==================================================="
echo ""
echo "El paquete ha sido creado como: $NOMBRE_PAQUETE.zip"
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
