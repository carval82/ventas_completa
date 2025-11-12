# Script para descargar recursos adicionales CSS y JavaScript para el sistema de ventas

# Crear directorios si no existen
New-Item -ItemType Directory -Force -Path "public\assets\css"
New-Item -ItemType Directory -Force -Path "public\assets\js"

# Descargar Chart.js
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/chart.js" -OutFile "public\assets\js\chart.min.js"

# Descargar Moment.js
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/momentjs/latest/moment.min.js" -OutFile "public\assets\js\moment.min.js"

# Descargar Daterangepicker JS y CSS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" -OutFile "public\assets\js\daterangepicker.min.js"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" -OutFile "public\assets\css\daterangepicker.css"

Write-Host "Todos los recursos adicionales han sido descargados correctamente."
