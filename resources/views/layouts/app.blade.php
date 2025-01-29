<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name') }}</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    @yield('styles')
    
    <style>
        :root {
            --primary-color: #39A900;
            --sidebar-width: 250px;
        }

        body {
            min-height: 100vh;
            background: #f5f6fa;
        }

        #sidebar {
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 100;
            transition: all 0.3s;
            background: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }

        #sidebar .p-3 {
            height: 100%;
            overflow-y: auto;
        }

        #sidebar .p-3::-webkit-scrollbar {
            width: 5px;
        }

        #sidebar .p-3::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #sidebar .p-3::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 5px;
        }

        #sidebar .p-3::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        #sidebar .p-3::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        #content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        .sidebar-collapsed #sidebar {
            margin-left: calc(-1 * var(--sidebar-width));
        }

        .sidebar-collapsed #content {
            margin-left: 0;
        }

        .navbar {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            background: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .sidebar-collapsed .navbar {
            margin-left: 0;
        }

        .nav-link.dropdown-toggle::after {
            float: right;
            margin-top: 8px;
        }

        .nav-link {
            color: #555;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--primary-color);
            color: #fff !important;
        }

        #sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        #sidebar .collapse {
            transition: all 0.3s;
        }

        #sidebar .collapse .nav-link {
            padding-left: 30px;
            font-size: 0.9rem;
        }

        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .required:after {
            content: ' *';
            color: red;
        }

        /* Select2 Bootstrap 5 Theme */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
        }
        
        .modal-backdrop {
            z-index: 1040;
        }
        
        .modal {
            z-index: 1050;
        }

        .swal2-popup {
            font-size: 0.9rem !important;
        }

        /* Estilos para el título del sistema */
        .app-title {
            padding: 1rem;
            text-align: center;
        }

        .app-title h3 {
            color: #333;
            font-size: 1.2rem;
            margin: 0;
            font-weight: 600;
        }

        .app-title p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
    </style>
</head>
<body>
    @auth
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="p-3">
                <div class="text-center mb-4">
                    <div class="app-title">
                        <h3>Sistema de Ventas</h3>
                        <p>LC DESARROLLO</p>
                    </div>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>

                    <!-- Módulo de Movimientos -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#movimientosMenu">
                            <i class="fas fa-exchange-alt"></i> Movimientos
                        </a>
                        <div class="collapse {{ request()->routeIs('ventas.*', 'compras.*', 'movimientos.*', 'movimientos-masivos.*', 'ubicaciones.*', 'sugeridos.*') ? 'show' : '' }}" 
                             id="movimientosMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('ventas.index') }}" 
                                       class="nav-link {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
                                        <i class="fas fa-shopping-cart"></i> Ventas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('compras.index') }}" 
                                       class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
                                        <i class="fas fa-truck"></i> Compras
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('sugeridos.index') }}" 
                                       class="nav-link {{ request()->routeIs('sugeridos.*') ? 'active' : '' }}">
                                        <i class="fas fa-clipboard-list"></i> Sugeridos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('movimientos.index') }}" 
                                       class="nav-link {{ request()->routeIs('movimientos.index') ? 'active' : '' }}">
                                        <i class="fas fa-boxes"></i> Movimientos Internos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('movimientos-masivos.index') }}" 
                                       class="nav-link {{ request()->routeIs('movimientos-masivos.*') ? 'active' : '' }}">
                                        <i class="fas fa-boxes"></i> Movimientos Masivos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('ubicaciones.index') }}" 
                                       class="nav-link {{ request()->routeIs('ubicaciones.*') ? 'active' : '' }}">
                                        <i class="fas fa-map-marker-alt"></i> Ubicaciones
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('movimientos.reporte-stock') }}" 
                                       class="nav-link {{ request()->routeIs('movimientos.reporte-stock') ? 'active' : '' }}">
                                        <i class="fas fa-chart-bar"></i> Reporte de Stock
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('movimientos.stock-bajo') }}" 
                                       class="nav-link {{ request()->routeIs('movimientos.stock-bajo') ? 'active' : '' }}">
                                        <i class="fas fa-exclamation-triangle"></i> Alertas Stock
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo de Productos -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#productosMenu">
                            <i class="fas fa-box"></i> Productos
                        </a>
                        <div class="collapse {{ request()->routeIs('productos.*') ? 'show' : '' }}" id="productosMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('productos.index') }}" class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}">
                                        <i class="fas fa-boxes"></i> Productos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('import.form') }}" class="nav-link {{ request()->routeIs('import.form') ? 'active' : '' }}">
                                        <i class="fas fa-file-import nav-icon"></i> Importar Inventario
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo de Terceros -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#tercerosMenu">
                            <i class="fas fa-users"></i> Terceros
                        </a>
                        <div class="collapse {{ request()->routeIs('clientes.*', 'proveedores.*') ? 'show' : '' }}" id="tercerosMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                                        <i class="fas fa-user-friends"></i> Clientes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('proveedores.index') }}" class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                                        <i class="fas fa-truck-loading"></i> Proveedores
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('import.terceros.form') }}" class="nav-link {{ request()->routeIs('import.terceros.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-import"></i> Importar Terceros
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo de Contabilidad -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#contabilidadMenu">
                            <i class="fas fa-calculator"></i> Contabilidad
                        </a>
                        <div class="collapse {{ request()->is('contabilidad/*') ? 'show' : '' }}" id="contabilidadMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('plan-cuentas.index') }}" 
                                       class="nav-link {{ request()->routeIs('plan-cuentas.*') ? 'active' : '' }}">
                                        <i class="fas fa-list"></i> Plan de Cuentas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('comprobantes.index') }}" 
                                       class="nav-link {{ request()->routeIs('comprobantes.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice"></i> Comprobantes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('reportes.index') }}" 
                                       class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                                        <i class="fas fa-chart-bar"></i> Reportes
                                    </a>
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.balance-general') }}" 
                                               class="nav-link">
                                                <i class="fas fa-balance-scale"></i> Balance General
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.estado-resultados') }}" 
                                               class="nav-link">
                                                <i class="fas fa-chart-line"></i> Estado de Resultados
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.libro-diario') }}" 
                                               class="nav-link">
                                                <i class="fas fa-book"></i> Libro Diario
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.libro-mayor') }}" 
                                               class="nav-link">
                                                <i class="fas fa-book-open"></i> Libro Mayor
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo de Configuración -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#configMenu">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <div class="collapse {{ request()->routeIs('users.*', 'empresa.*', 'regularizacion.*', 'backup.*') ? 'show' : '' }}" id="configMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                        <i class="fas fa-users"></i> Usuarios
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('empresa.index') }}" class="nav-link {{ request()->routeIs('empresa.*') ? 'active' : '' }}">
                                        <i class="fas fa-building"></i> Empresa
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('regularizacion.index') }}" class="nav-link {{ request()->routeIs('regularizacion.*') ? 'active' : '' }}">
                                        <i class="fas fa-sync-alt nav-icon"></i> Regularización Stock/Precios
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('backup.index') }}" class="nav-link {{ request()->routeIs('backup.*') ? 'active' : '' }}">
                                        <i class="fas fa-database nav-icon"></i> Backup/Restore
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo de Créditos -->
                    <li class="nav-item">
                        <a href="{{ route('creditos.index') }}" class="nav-link {{ request()->routeIs('creditos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>Créditos</p>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button id="sidebarCollapse" class="btn">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="dropdown ms-auto">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> {{ Auth::user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main id="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    @else
        @yield('content')
    @endauth

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Configuración global de Axios
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Inicialización global
        $(document).ready(function() {
            console.log('Layout initialized');
            
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('body').toggleClass('sidebar-collapsed');
            });

            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Configurar SweetAlert2
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });

            // Exponer Toast globalmente
            window.Toast = Toast;
        });
    </script>

    @stack('scripts')
</body>
</html>