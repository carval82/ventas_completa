// Script para agregar un botón flotante para volver al panel principal
document.addEventListener('DOMContentLoaded', function() {
    // Crear el botón flotante
    const floatingButton = document.createElement('div');
    floatingButton.style.position = 'fixed';
    floatingButton.style.bottom = '20px';
    floatingButton.style.right = '20px';
    floatingButton.style.backgroundColor = '#dc3545';
    floatingButton.style.color = 'white';
    floatingButton.style.padding = '15px 20px';
    floatingButton.style.borderRadius = '50px';
    floatingButton.style.boxShadow = '0 4px 8px rgba(0,0,0,0.3)';
    floatingButton.style.cursor = 'pointer';
    floatingButton.style.zIndex = '9999';
    floatingButton.style.fontSize = '16px';
    floatingButton.style.fontWeight = 'bold';
    floatingButton.innerHTML = '<i class="fas fa-home" style="margin-right: 8px;"></i> PANEL PRINCIPAL';
    
    // Agregar evento de clic para redirigir
    floatingButton.addEventListener('click', function() {
        window.location.href = '/home';
    });
    
    // Agregar el botón al body
    document.body.appendChild(floatingButton);
    
    // También crear un banner en la parte superior
    const topBanner = document.createElement('div');
    topBanner.style.position = 'fixed';
    topBanner.style.top = '0';
    topBanner.style.left = '0';
    topBanner.style.width = '100%';
    topBanner.style.backgroundColor = '#dc3545';
    topBanner.style.color = 'white';
    topBanner.style.padding = '10px';
    topBanner.style.textAlign = 'center';
    topBanner.style.zIndex = '9999';
    topBanner.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
    topBanner.innerHTML = '<a href="/home" style="color: white; text-decoration: none; font-weight: bold;"><i class="fas fa-arrow-left"></i> VOLVER AL PANEL PRINCIPAL <i class="fas fa-home"></i></a>';
    
    // Agregar el banner al body
    document.body.appendChild(topBanner);
    
    // Agregar margen superior al contenido para que no quede debajo del banner
    document.body.style.marginTop = '50px';
});
