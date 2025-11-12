<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sistema de Equivalencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3><i class="fas fa-flask"></i> Test Sistema de Equivalencias</h3>
                <small>Prueba las conversiones de unidades implementadas</small>
            </div>
            <div class="card-body">
                <!-- Test de API -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>🧪 Test de Conversiones API</h5>
                        <div class="mb-3">
                            <label class="form-label">Producto ID</label>
                            <select class="form-select" id="producto-test">
                                <option value="1">Producto 1 (Paca Arroz)</option>
                                <option value="2">Producto 2 (Bulto)</option>
                                <option value="3">Producto 3 (Galón)</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Cantidad</label>
                                <input type="number" class="form-control" id="cantidad-test" value="50" step="0.001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">De</label>
                                <select class="form-select" id="unidad-origen">
                                    <option value="lb">Libras</option>
                                    <option value="kg">Kilos</option>
                                    <option value="paca">Paca</option>
                                    <option value="bulto">Bulto</option>
                                    <option value="l">Litros</option>
                                    <option value="galon">Galón</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">A</label>
                                <select class="form-select" id="unidad-destino">
                                    <option value="paca">Paca</option>
                                    <option value="lb">Libras</option>
                                    <option value="kg">Kilos</option>
                                    <option value="bulto">Bulto</option>
                                    <option value="l">Litros</option>
                                    <option value="galon">Galón</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="testConversion()">
                                <i class="fas fa-calculator"></i> Probar Conversión
                            </button>
                            <button class="btn btn-info" onclick="obtenerUnidadesDisponibles()">
                                <i class="fas fa-list"></i> Ver Unidades Disponibles
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>📊 Resultados</h5>
                        <div id="resultado-test" class="alert alert-secondary">
                            <i class="fas fa-info-circle"></i> Selecciona valores y presiona "Probar Conversión"
                        </div>
                        
                        <h6>🔍 Unidades Disponibles</h6>
                        <div id="unidades-disponibles" class="alert alert-light">
                            <i class="fas fa-clock"></i> Presiona "Ver Unidades Disponibles"
                        </div>
                    </div>
                </div>
                
                <!-- Ejemplos predefinidos -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>📋 Ejemplos Rápidos</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="card-title">🌾 Paca de Arroz</h6>
                                        <p class="card-text">50 libras = ? pacas</p>
                                        <button class="btn btn-sm btn-primary" onclick="testEjemplo(1, 50, 'lb', 'paca')">
                                            Probar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6 class="card-title">📦 Bulto</h6>
                                        <p class="card-text">120 kilos = ? bultos</p>
                                        <button class="btn btn-sm btn-success" onclick="testEjemplo(2, 120, 'kg', 'bulto')">
                                            Probar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h6 class="card-title">🥤 Galón</h6>
                                        <p class="card-text">7.57 litros = ? galones</p>
                                        <button class="btn btn-sm btn-info" onclick="testEjemplo(3, 7.57, 'l', 'galon')">
                                            Probar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Log de pruebas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>📝 Log de Pruebas</h5>
                        <div id="log-pruebas" class="bg-dark text-light p-3" style="height: 200px; overflow-y: auto; font-family: monospace;">
                            <div class="text-success">[INFO] Sistema de equivalencias listo para pruebas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function log(mensaje, tipo = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const colores = {
                'info': 'text-info',
                'success': 'text-success',
                'error': 'text-danger',
                'warning': 'text-warning'
            };
            
            const logDiv = document.getElementById('log-pruebas');
            logDiv.innerHTML += `<div class="${colores[tipo]}">[${timestamp}] ${mensaje}</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function testConversion() {
            const productoId = document.getElementById('producto-test').value;
            const cantidad = document.getElementById('cantidad-test').value;
            const unidadOrigen = document.getElementById('unidad-origen').value;
            const unidadDestino = document.getElementById('unidad-destino').value;
            
            log(`Probando conversión: ${cantidad} ${unidadOrigen} → ${unidadDestino} (Producto ${productoId})`);
            
            $.ajax({
                url: '/api/conversiones/convertir-unidad',
                method: 'POST',
                data: {
                    producto_id: productoId,
                    unidad_origen: unidadOrigen,
                    unidad_destino: unidadDestino,
                    cantidad: cantidad,
                    precio: 100 // Precio de ejemplo
                },
                success: function(response) {
                    if (response.success) {
                        const resultado = `
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle"></i> ¡Conversión Exitosa!</h6>
                                <strong>${cantidad} ${unidadOrigen.toUpperCase()}</strong> = 
                                <strong>${response.data.cantidad_convertida} ${response.data.unidad_destino.toUpperCase()}</strong><br>
                                <small>Factor: ${response.data.factor_conversion}</small><br>
                                ${response.data.descripcion ? '<small>' + response.data.descripcion + '</small>' : ''}
                            </div>
                        `;
                        document.getElementById('resultado-test').innerHTML = resultado;
                        log(`✅ Éxito: ${cantidad} ${unidadOrigen} = ${response.data.cantidad_convertida} ${response.data.unidad_destino}`, 'success');
                    } else {
                        document.getElementById('resultado-test').innerHTML = `
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle"></i> Error</h6>
                                ${response.message}
                            </div>
                        `;
                        log(`❌ Error: ${response.message}`, 'error');
                    }
                },
                error: function(xhr) {
                    document.getElementById('resultado-test').innerHTML = `
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-times-circle"></i> Error de Conexión</h6>
                            No se pudo conectar con la API
                        </div>
                    `;
                    log(`❌ Error de conexión: ${xhr.status}`, 'error');
                }
            });
        }
        
        function obtenerUnidadesDisponibles() {
            const productoId = document.getElementById('producto-test').value;
            
            log(`Obteniendo unidades disponibles para producto ${productoId}`);
            
            $.ajax({
                url: '/api/conversiones/unidades-disponibles',
                method: 'GET',
                data: { producto_id: productoId },
                success: function(response) {
                    if (response.success) {
                        let html = '<strong>Unidades disponibles:</strong><br>';
                        response.data.unidades.forEach(unidad => {
                            html += `<span class="badge bg-primary me-1">${unidad.codigo.toUpperCase()}</span>`;
                        });
                        html += `<br><small>Unidad base: <strong>${response.data.unidad_base.toUpperCase()}</strong></small>`;
                        
                        document.getElementById('unidades-disponibles').innerHTML = html;
                        log(`✅ Unidades obtenidas: ${response.data.unidades.length} disponibles`, 'success');
                    } else {
                        document.getElementById('unidades-disponibles').innerHTML = `
                            <div class="text-danger">${response.message}</div>
                        `;
                        log(`❌ Error: ${response.message}`, 'error');
                    }
                },
                error: function(xhr) {
                    log(`❌ Error al obtener unidades: ${xhr.status}`, 'error');
                }
            });
        }
        
        function testEjemplo(productoId, cantidad, unidadOrigen, unidadDestino) {
            document.getElementById('producto-test').value = productoId;
            document.getElementById('cantidad-test').value = cantidad;
            document.getElementById('unidad-origen').value = unidadOrigen;
            document.getElementById('unidad-destino').value = unidadDestino;
            
            testConversion();
        }
        
        // Auto-cargar unidades al cambiar producto
        document.getElementById('producto-test').addEventListener('change', obtenerUnidadesDisponibles);
        
        // Cargar unidades iniciales
        setTimeout(obtenerUnidadesDisponibles, 1000);
    </script>
</body>
</html>
