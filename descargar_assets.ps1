# Script para descargar recursos CSS y JavaScript para el sistema de ventas
# Crear directorios si no existen
New-Item -ItemType Directory -Force -Path "public\assets\css"
New-Item -ItemType Directory -Force -Path "public\assets\js"
New-Item -ItemType Directory -Force -Path "public\assets\webfonts"

# Descargar archivos CSS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" -OutFile "public\assets\css\bootstrap.min.css"
Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" -OutFile "public\assets\css\all.min.css"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" -OutFile "public\assets\css\select2.min.css"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" -OutFile "public\assets\css\sweetalert2.min.css"

# Descargar archivos JavaScript
Invoke-WebRequest -Uri "https://code.jquery.com/jquery-3.7.1.min.js" -OutFile "public\assets\js\jquery-3.7.1.min.js"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" -OutFile "public\assets\js\bootstrap.bundle.min.js"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" -OutFile "public\assets\js\select2.min.js"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js" -OutFile "public\assets\js\sweetalert2.all.min.js"

# Descargar webfonts para Font Awesome
$webfontsBaseUrl = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts"
$webfonts = @(
    "fa-brands-400.ttf",
    "fa-brands-400.woff2",
    "fa-regular-400.ttf",
    "fa-regular-400.woff2",
    "fa-solid-900.ttf",
    "fa-solid-900.woff2",
    "fa-v4compatibility.ttf",
    "fa-v4compatibility.woff2"
)

foreach ($font in $webfonts) {
    Invoke-WebRequest -Uri "$webfontsBaseUrl/$font" -OutFile "public\assets\webfonts\$font"
}

Write-Host "Todos los recursos han sido descargados correctamente."
