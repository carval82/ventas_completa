#!/bin/bash

echo "==================================================="
echo "    INSTALACION DEL SISTEMA DE VENTAS"
echo "==================================================="
echo ""

echo "Paso 1: Instalando dependencias de PHP..."
composer install
if [ $? -ne 0 ]; then
    echo "Error al instalar dependencias de PHP."
    exit 1
fi
echo "Dependencias de PHP instaladas correctamente."
echo ""

echo "Paso 2: Instalando dependencias de JavaScript..."
npm install
if [ $? -ne 0 ]; then
    echo "Error al instalar dependencias de JavaScript."
    exit 1
fi
echo "Dependencias de JavaScript instaladas correctamente."
echo ""

echo "Paso 3: Compilando assets..."
npm run build
if [ $? -ne 0 ]; then
    echo "Error al compilar assets."
    exit 1
fi
echo "Assets compilados correctamente."
echo ""

echo "Paso 4: Configurando el entorno..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Archivo .env creado. Por favor, configura la conexión a la base de datos."
    if command -v nano &> /dev/null; then
        nano .env
    elif command -v vi &> /dev/null; then
        vi .env
    else
        echo "No se encontró un editor de texto. Por favor, edita el archivo .env manualmente."
    fi
else
    echo "El archivo .env ya existe."
fi
echo ""

echo "Paso 5: Generando clave de aplicación..."
php artisan key:generate
if [ $? -ne 0 ]; then
    echo "Error al generar la clave de aplicación."
    exit 1
fi
echo "Clave de aplicación generada correctamente."
echo ""

echo "Paso 6: ¿Deseas crear la base de datos y ejecutar las migraciones? (S/N)"
read ejecutar_migraciones
if [ "$ejecutar_migraciones" = "S" ] || [ "$ejecutar_migraciones" = "s" ]; then
    echo "Ejecutando migraciones y seeders..."
    php artisan migrate:fresh --seed
    if [ $? -ne 0 ]; then
        echo "Error al ejecutar las migraciones."
        exit 1
    fi
    echo "Migraciones y seeders ejecutados correctamente."
else
    echo "Migraciones omitidas. Deberás ejecutarlas manualmente."
fi
echo ""

echo "Paso 7: Configurando permisos..."
chmod -R 775 storage bootstrap/cache
echo "Permisos configurados correctamente."
echo ""

echo "Paso 8: Limpiando caché..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "Caché limpiada correctamente."
echo ""

echo "==================================================="
echo "    INSTALACIÓN COMPLETADA"
echo "==================================================="
echo ""
echo "Para iniciar el servidor, ejecuta: php artisan serve"
echo "Luego abre tu navegador en: http://localhost:8000"
echo ""
echo "Credenciales por defecto:"
echo "Usuario: admin@admin.com"
echo "Contraseña: password"
echo ""
echo "Para más información, consulta el archivo INSTALACION.md"
echo ""

read -p "Presiona Enter para continuar..."
