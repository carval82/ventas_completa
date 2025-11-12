<?php
echo "🔧 SOLUCIONANDO ERROR DE MYSQL BACKUP\n";
echo "====================================\n\n";

echo "📋 PROBLEMA IDENTIFICADO:\n";
echo "=========================\n";
echo "Error: Can't create/write to file 'C:\\xampp\\tmp\\#sql1be8_21c_2.MAI'\n";
echo "Causa: El directorio temporal de MySQL no existe o no tiene permisos\n\n";

echo "🛠️ SOLUCIONES:\n";
echo "===============\n\n";

echo "1. 📁 CREAR DIRECTORIO TEMPORAL:\n";
echo "================================\n";
$tmpDir = 'C:\\xampp\\tmp';
if (!is_dir($tmpDir)) {
    if (mkdir($tmpDir, 0777, true)) {
        echo "✅ Directorio creado: $tmpDir\n";
    } else {
        echo "❌ Error creando directorio: $tmpDir\n";
    }
} else {
    echo "✅ Directorio ya existe: $tmpDir\n";
}

echo "\n2. 🔧 VERIFICAR PERMISOS:\n";
echo "=========================\n";
if (is_writable($tmpDir)) {
    echo "✅ Directorio escribible: $tmpDir\n";
} else {
    echo "❌ Directorio NO escribible: $tmpDir\n";
    echo "   Ejecuta como administrador o cambia permisos\n";
}

echo "\n3. 🗄️ VERIFICAR MYSQL:\n";
echo "======================\n";
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (file_exists($mysqldump)) {
    echo "✅ mysqldump encontrado: $mysqldump\n";
} else {
    echo "❌ mysqldump NO encontrado: $mysqldump\n";
}

echo "\n4. 🔄 ALTERNATIVA - COMANDO MEJORADO:\n";
echo "====================================\n";
echo "Usar comando con --single-transaction y --tmpdir:\n";
echo "mysqldump --single-transaction --tmpdir=\"C:\\xampp\\tmp\" ...\n\n";

echo "5. 📧 VERIFICAR SISTEMA DE EMAIL:\n";
echo "=================================\n";
echo "El sistema de backup SÍ envía emails usando:\n";
echo "• Mail::send('emails.backup', ...)\n";
echo "• Template: resources/views/emails/backup.blade.php\n";
echo "• Adjunta el archivo de backup\n";
echo "• Usa la misma configuración SMTP que los acuses\n\n";

echo "🎯 COMANDO CORREGIDO PARA BACKUP:\n";
echo "=================================\n";
$database = 'ventas_completa';
$username = 'root';
$password = '';
$outputFile = 'C:\\xampp\\htdocs\\laravel\\ventas_completa\\storage\\app\\backups\\test_backup.sql';

$commandFixed = "\"$mysqldump\" --single-transaction --routines --triggers --tmpdir=\"$tmpDir\" --user=$username --password=$password --databases $database --result-file=\"$outputFile\"";

echo "Comando mejorado:\n";
echo "$commandFixed\n\n";

echo "🧪 PROBANDO BACKUP MEJORADO:\n";
echo "============================\n";

try {
    // Crear directorio de backups si no existe
    $backupDir = 'C:\\xampp\\htdocs\\laravel\\ventas_completa\\storage\\app\\backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
        echo "✅ Directorio de backups creado\n";
    }
    
    echo "🚀 Ejecutando comando de backup...\n";
    exec($commandFixed . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        if (file_exists($outputFile) && filesize($outputFile) > 0) {
            $size = round(filesize($outputFile) / 1024 / 1024, 2);
            echo "✅ BACKUP EXITOSO!\n";
            echo "📁 Archivo: $outputFile\n";
            echo "📊 Tamaño: {$size} MB\n";
            
            // Limpiar archivo de prueba
            unlink($outputFile);
            echo "🗑️ Archivo de prueba eliminado\n";
        } else {
            echo "❌ Archivo de backup vacío o no creado\n";
        }
    } else {
        echo "❌ ERROR EN BACKUP:\n";
        foreach ($output as $line) {
            echo "   $line\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error ejecutando backup: " . $e->getMessage() . "\n";
}

echo "\n📧 PARA PROBAR ENVÍO DE EMAIL:\n";
echo "==============================\n";
echo "1. Corrige el error de MySQL arriba\n";
echo "2. Ejecuta: php artisan backup:database --send-email\n";
echo "3. El backup se enviará usando la misma configuración SMTP\n";
echo "4. Destinatario configurado en la base de datos (tabla settings)\n\n";

echo "🔧 CONFIGURACIÓN RECOMENDADA:\n";
echo "=============================\n";
echo "Agregar en my.ini de MySQL (C:\\xampp\\mysql\\bin\\my.ini):\n";
echo "[mysqld]\n";
echo "tmpdir = \"C:/xampp/tmp\"\n";
echo "secure-file-priv = \"\"\n\n";

echo "🏁 Diagnóstico completado\n";
