# Script de PowerShell para hacer backup completo del sistema de ventas

# Configurar variables
$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$backupDir = "C:\backups_ventas"
$proyectoDir = "C:\xampp\htdocs\laravel\ventas_completa"
$backupNombre = "ventas_completa_backup_$fecha"
$backupArchivo = "$backupDir\$backupNombre.zip"
$backupSQL = "$backupDir\$backupNombre.sql"

# Crear directorio de backups si no existe
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
    Write-Host "Directorio de backups creado: $backupDir"
}

Write-Host ""
Write-Host "====================================================="
Write-Host " Backup Completo - Sistema de Ventas"
Write-Host "====================================================="
Write-Host ""

# Backup de la base de datos
Write-Host "Creando backup de la base de datos..."

# Obtener configuración de la base de datos del archivo .env
$envFile = "$proyectoDir\.env"
if (Test-Path $envFile) {
    $dbName = (Get-Content $envFile | Select-String "DB_DATABASE=(.*)").Matches.Groups[1].Value
    $dbUser = (Get-Content $envFile | Select-String "DB_USERNAME=(.*)").Matches.Groups[1].Value
    $dbPass = (Get-Content $envFile | Select-String "DB_PASSWORD=(.*)").Matches.Groups[1].Value
    
    if ([string]::IsNullOrEmpty($dbName)) { $dbName = "laravel" }
    if ([string]::IsNullOrEmpty($dbUser)) { $dbUser = "root" }
    if ([string]::IsNullOrEmpty($dbPass)) { $dbPass = "" }
} else {
    # Valores predeterminados si no se encuentra el archivo .env
    $dbName = "laravel"
    $dbUser = "root"
    $dbPass = ""
}

# Ruta de mysqldump en XAMPP
$mysqldump = "C:\xampp\mysql\bin\mysqldump.exe"

# Verificar que existe mysqldump
if (Test-Path $mysqldump) {
    # Construir el comando
    if ([string]::IsNullOrEmpty($dbPass)) {
        $command = "& `"$mysqldump`" --user=$dbUser --databases $dbName --result-file=`"$backupSQL`""
    } else {
        $command = "& `"$mysqldump`" --user=$dbUser --password=$dbPass --databases $dbName --result-file=`"$backupSQL`""
    }
    
    # Ejecutar comando
    try {
        Invoke-Expression $command
        if (Test-Path $backupSQL) {
            $fileSize = (Get-Item $backupSQL).Length / 1MB
            Write-Host "Backup de base de datos creado exitosamente: $backupSQL ($([math]::Round($fileSize, 2)) MB)"
        } else {
            Write-Host "Error: No se pudo crear el archivo de backup de la base de datos" -ForegroundColor Red
        }
    } catch {
        Write-Host "Error al crear backup de la base de datos: $_" -ForegroundColor Red
    }
} else {
    Write-Host "Error: No se encontró mysqldump en la ruta especificada" -ForegroundColor Red
}

# Backup de archivos
Write-Host ""
Write-Host "Creando backup de archivos del proyecto..."

try {
    # Crear una lista de exclusiones
    $exclusiones = @(
        "$proyectoDir\node_modules",
        "$proyectoDir\vendor",
        "$proyectoDir\storage\logs",
        "$proyectoDir\storage\framework\cache",
        "$proyectoDir\.git"
    )
    
    # Obtener todos los archivos y directorios, excluyendo los que están en la lista
    $archivos = Get-ChildItem -Path $proyectoDir -Recurse | Where-Object {
        $item = $_
        $excluir = $false
        foreach ($exclusion in $exclusiones) {
            if ($item.FullName.StartsWith($exclusion)) {
                $excluir = $true
                break
            }
        }
        -not $excluir
    }
    
    # Comprimir archivos
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $compressionLevel = [System.IO.Compression.CompressionLevel]::Optimal
    
    # Crear un archivo ZIP temporal
    $tempZip = "$backupDir\temp_$backupNombre.zip"
    $zip = [System.IO.Compression.ZipFile]::Open($tempZip, [System.IO.Compression.ZipArchiveMode]::Create)
    
    # Agregar archivos al ZIP
    $count = 0
    $total = $archivos.Count
    
    foreach ($archivo in $archivos) {
        $count++
        $relativePath = $archivo.FullName.Substring($proyectoDir.Length + 1)
        
        if ($count % 100 -eq 0) {
            Write-Host "Procesando $count de $total archivos..."
        }
        
        if (-not [string]::IsNullOrEmpty($relativePath)) {
            try {
                if (-not $archivo.PSIsContainer) {
                    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $archivo.FullName, $relativePath, $compressionLevel) | Out-Null
                } else {
                    $zipEntry = $zip.CreateEntry("$relativePath/")
                }
            } catch {
                Write-Host "Error al comprimir $relativePath : $_" -ForegroundColor Yellow
            }
        }
    }
    
    # Cerrar el archivo ZIP
    $zip.Dispose()
    
    # Renombrar el archivo temporal
    Move-Item -Path $tempZip -Destination $backupArchivo -Force
    
    $fileSize = (Get-Item $backupArchivo).Length / 1MB
    Write-Host "Backup de archivos creado exitosamente: $backupArchivo ($([math]::Round($fileSize, 2)) MB)"
} catch {
    Write-Host "Error al crear backup de archivos: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "====================================================="
Write-Host " Backup Completo Finalizado"
Write-Host "====================================================="
Write-Host ""
Write-Host "Los archivos de backup se encuentran en:"
Write-Host ""
Write-Host "1. Archivos del proyecto: $backupArchivo"
Write-Host "2. Base de datos: $backupSQL"
Write-Host ""
Write-Host "Ahora puedes proceder con la reestructuración para hacer el sistema responsive."
Write-Host ""

Read-Host "Presiona Enter para continuar..."
