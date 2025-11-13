<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name') }}</title>

    <!-- CSS (archivos locales) -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/sweetalert2.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @yield('styles')
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Variables para el tema claro (default) */
            --bg-color: #f8fafc;
            --bg-pattern: radial-gradient(circle at 25px 25px, rgba(102, 126, 234, 0.02) 2%, transparent 0%);
            --sidebar-bg: rgba(255, 255, 255, 0.95);
            --card-bg: rgba(255, 255, 255, 0.9);
            --text-color: #1a202c;
            --text-muted: #718096;
            --border-color: rgba(226, 232, 240, 0.8);
            --nav-link-color: #4a5568;
            --nav-link-hover-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --nav-link-hover-color: #fff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Tema oscuro */
        body.dark-mode {
            --bg-color: #0f172a;
            --bg-pattern: radial-gradient(circle at 25px 25px, rgba(102, 126, 234, 0.05) 2%, transparent 0%);
            --sidebar-bg: rgba(30, 41, 59, 0.95);
            --card-bg: rgba(51, 65, 85, 0.9);
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: rgba(71, 85, 105, 0.8);
            --nav-link-color: #cbd5e1;
            --nav-link-hover-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --nav-link-hover-color: #fff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px 2px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: var(--bg-color);
            background-image: var(--bg-pattern);
            background-size: 50px 50px;
            color: var(--text-color);
            transition: var(--transition);
            font-weight: 400;
            line-height: 1.6;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
            pointer-events: none;
            z-index: -1;
        }

        #sidebar {
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: var(--transition);
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #sidebar .p-3 {
            height: 100%;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        #sidebar .p-3::-webkit-scrollbar {
            width: 6px;
        }

        #sidebar .p-3::-webkit-scrollbar-track {
            background: transparent;
        }

        #sidebar .p-3::-webkit-scrollbar-thumb {
            background: var(--text-muted);
            border-radius: 10px;
            opacity: 0.5;
        }

        #sidebar .p-3::-webkit-scrollbar-thumb:hover {
            background: var(--text-color);
            opacity: 1;
        }

        #sidebar .nav.flex-column {
            flex: 1;
            padding: 0 16px;
        }

        #content {
            margin-left: var(--sidebar-width);
            padding: 24px;
            transition: var(--transition);
            min-height: 100vh;
            padding-top: calc(var(--header-height) + 24px);
        }

        .sidebar-collapsed #sidebar {
            transform: translateX(-100%);
        }

        .sidebar-collapsed #content {
            margin-left: 0;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            z-index: 999;
            transition: var(--transition);
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 0 24px;
        }
        
        .navbar .dropdown-menu {
            background-color: var(--card-bg);
        }
        
        .navbar .dropdown-item {
            color: var(--text-color);
        }
        
        .navbar .dropdown-item:hover {
            background-color: var(--primary-color);
            color: #fff;
        }

        .sidebar-collapsed .navbar {
            left: 0;
        }

        .nav-link.dropdown-toggle::after {
            float: right;
            margin-top: 8px;
            transition: var(--transition);
        }

        .nav-link {
            color: var(--nav-link-color);
            padding: 14px 20px;
            margin: 6px 0;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            font-weight: 500;
            font-size: 14px;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--nav-link-hover-bg);
            transition: var(--transition);
            z-index: -1;
        }

        .nav-link:hover::before, .nav-link.active::before {
            left: 0;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--nav-link-hover-color) !important;
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        #sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 16px;
        }

        #sidebar .collapse {
            transition: var(--transition);
        }

        #sidebar .collapse .nav-link {
            padding-left: 40px;
            font-size: 13px;
            margin: 3px 0;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            color: var(--text-color);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 24px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .card-body {
            padding: 24px;
        }
        
        .card-footer {
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            padding: 16px 24px;
        }

        .required:after {
            content: ' *';
            color: red;
        }
        
        /* Formularios en modo oscuro */
        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #333;
            border-color: var(--border-color);
            color: var(--text-color);
        }
        
        .dark-mode .form-control:focus,
        .dark-mode .form-select:focus {
            background-color: #444;
            color: var(--text-color);
        }
        
        /* Tablas en modo oscuro */
        .dark-mode .table {
            color: var(--text-color);
        }
        
        .dark-mode .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .dark-mode .table-hover > tbody > tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Select2 Bootstrap 5 Theme */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
        }
        
        .dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: #333;
            color: var(--text-color);
        }
        
        .dark-mode .select2-container--bootstrap-5 .select2-selection__rendered {
            color: var(--text-color);
        }
        
        .modal-backdrop {
            z-index: 1040;
        }
        
        .modal {
            z-index: 1050;
        }
        
        .dark-mode .modal-content {
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        
        .dark-mode .modal-header,
        .dark-mode .modal-footer {
            border-color: var(--border-color);
        }

        .swal2-popup {
            font-size: 0.9rem !important;
        }
        
        .dark-mode .swal2-popup {
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        /* Estilos para el título del sistema */
        .app-title {
            padding: 1rem;
            text-align: center;
        }

        .app-title h5 {
            color: var(--text-color);
            font-size: 1rem;
            margin: 0;
            font-weight: 600;
        }

        .app-title p {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin: 0;
        }
        
        /* Estilos para el logo y nombre de la empresa */
        .empresa-info {
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
            position: relative;
        }

        .empresa-info::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .empresa-info h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .empresa-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .empresa-info img {
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 16px;
        }
        
        /* Botón de cambio de tema */
        .theme-toggle {
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            font-size: 13px;
            font-weight: 500;
        }
        
        .theme-toggle i {
            margin-right: 8px;
            font-size: 14px;
        }
        
        .theme-toggle:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            z-index: 1030;
            transition: var(--transition);
        }

        .navbar.sidebar-collapsed {
            left: 60px;
        }

        .navbar .btn {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            color: var(--primary-color);
            border-radius: var(--border-radius-sm);
            padding: 0.5rem 1rem;
            transition: var(--transition);
        }

        .navbar .btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        .navbar .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-lg);
            margin-top: 0.5rem;
        }

        .navbar .dropdown-item {
            color: var(--text-color);
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
        }

        .navbar .dropdown-item:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Developer Contact Info - Linear Design */
        .developer-contact {
            justify-content: flex-start;
            padding: 0 1rem;
        }

        .contact-linear {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .contact-linear i {
            color: var(--primary-color);
            font-size: 0.8rem;
        }

        .contact-linear a {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-linear a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .separator {
            color: var(--border-color);
            font-weight: 300;
        }

        .navbar-actions {
            gap: 0.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1199.98px) {
            .developer-contact {
                display: none !important;
            }
        }

        /* Alert improvements */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(20px);
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        /* Form improvements */
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            padding: 12px 16px;
            transition: var(--transition);
            background: var(--card-bg);
            color: var(--text-color);
            font-weight: 500;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: var(--card-bg);
            color: var(--text-color);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius-sm);
            padding: 12px 24px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Table improvements */
        .table {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .table th {
            background: rgba(102, 126, 234, 0.1);
            border: none;
            padding: 16px;
            font-weight: 600;
            color: var(--text-color);
        }

        .table td {
            border: none;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 260px;
            }

            #sidebar {
                transform: translateX(-100%);
            }

            body.sidebar-open #sidebar {
                transform: translateX(0);
            }

            #content {
                margin-left: 0;
                padding: 16px;
                padding-top: calc(var(--header-height) + 16px);
            }

            .navbar {
                left: 0;
            }

            .empresa-info {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    @auth
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="p-3">
                <div class="empresa-info text-center mb-4">
                    @php
                        $empresa = \App\Models\Empresa::first();
                    @endphp
                    
                    @if($empresa && $empresa->logo)
                        <img src="{{ asset('storage/' . $empresa->logo) }}" 
                             alt="Logo de la empresa" 
                             class="img-fluid mb-3" 
                             style="max-height: 100px;">
                    @endif
                    
                    @if($empresa)
                        <h4>{{ $empresa->nombre_comercial }}</h4>
                        <p class="small text-muted">{{ $empresa->nit }}</p>
                    @endif
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
                        <div class="collapse {{ request()->routeIs('ventas.*', 'compras.*', 'movimientos.*', 'movimientos-masivos.*', 'ubicaciones.*', 'sugeridos.*', 'facturas.electronicas.*', 'creditos.*', 'cajas.*') ? 'show' : '' }}" 
                             id="movimientosMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('ventas.create') }}" 
                                       class="nav-link {{ request()->routeIs('ventas.create') ? 'active' : '' }}">
                                        <i class="fas fa-cart-plus"></i> Nueva Venta
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('ventas.index') }}" 
                                       class="nav-link {{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                                        <i class="fas fa-list"></i> Listado de Ventas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cotizaciones.index') }}" 
                                       class="nav-link {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice"></i> Cotizaciones
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('remisiones.index') }}" 
                                       class="nav-link {{ request()->routeIs('remisiones.*') ? 'active' : '' }}">
                                        <i class="fas fa-truck"></i> Remisiones
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cajas.index') }}" 
                                       class="nav-link {{ request()->routeIs('cajas.*') ? 'active' : '' }}">
                                        <i class="fas fa-cash-register"></i> Cajas Diarias
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('creditos.index') }}" 
                                       class="nav-link {{ request()->routeIs('creditos.*') ? 'active' : '' }}">
                                        <i class="fas fa-credit-card"></i> Créditos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('facturas.electronicas.index') }}" 
                                       class="nav-link {{ request()->routeIs('facturas.electronicas.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice"></i> Facturas Electrónicas
                                    </a>
                                </li>
                                

                                <!-- Módulo de Alegra -->
                                <li class="nav-item">
                                    <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#alegraMenu">
                                        <i class="fas fa-receipt"></i> Alegra
                                    </a>
                                    <div class="collapse {{ request()->routeIs('alegra.*') ? 'show' : '' }}" id="alegraMenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item">
                                                <a href="{{ route('alegra.facturas.index') }}" 
                                                   class="nav-link {{ request()->routeIs('alegra.facturas.*') ? 'active' : '' }}">
                                                    <i class="fas fa-list"></i> Listar Facturas
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('alegra.reportes.dashboard') }}" 
                                                   class="nav-link {{ request()->routeIs('alegra.reportes.*') ? 'active' : '' }}">
                                                    <i class="fas fa-chart-bar"></i> Reportes
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
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
                                    <a href="{{ route('contabilidad.dashboard') }}" 
                                       class="nav-link {{ request()->routeIs('contabilidad.dashboard') ? 'active' : '' }}">
                                        <i class="fas fa-tachometer-alt text-success"></i> Dashboard NIF
                                    </a>
                                </li>
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
                                    <a href="{{ route('contabilidad.reportes.index') }}" 
                                       class="nav-link {{ request()->routeIs('reportes.*') || request()->routeIs('contabilidad.reportes.*') ? 'active' : '' }}">
                                        <i class="fas fa-chart-bar"></i> Reportes
                                    </a>
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item">
                                            <small class="text-muted ms-3">📊 INFORMES NIF COLOMBIA</small>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('balance-general.index') }}" 
                                               class="nav-link {{ request()->routeIs('balance-general.*') ? 'active' : '' }}">
                                                <i class="fas fa-balance-scale text-success"></i> Balance General NIF
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('estado-resultados.index') }}" 
                                               class="nav-link {{ request()->routeIs('estado-resultados.*') ? 'active' : '' }}">
                                                <i class="fas fa-chart-line text-primary"></i> Estado de Resultados NIF
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('flujo-efectivo.index') }}" 
                                               class="nav-link {{ request()->routeIs('flujo-efectivo.*') ? 'active' : '' }}">
                                                <i class="fas fa-water text-info"></i> Flujo de Efectivo NIF
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li class="nav-item">
                                            <small class="text-muted ms-3">📚 LIBROS CONTABLES</small>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.libro-diario') }}" 
                                               class="nav-link">
                                                <i class="fas fa-book text-secondary"></i> Libro Diario
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.libro-mayor') }}" 
                                               class="nav-link">
                                                <i class="fas fa-book-open text-secondary"></i> Libro Mayor
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li class="nav-item">
                                            <small class="text-muted ms-3">🏛️ REPORTES FISCALES</small>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.fiscal-iva') }}" 
                                               class="nav-link">
                                                <i class="fas fa-percentage text-warning"></i> Reporte Fiscal IVA
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reportes.fiscal-retenciones') }}" 
                                               class="nav-link">
                                                <i class="fas fa-hand-holding-usd text-warning"></i> Reporte Fiscal Retenciones
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo DIAN -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dian.*') ? 'active' : '' }}" 
                           href="{{ route('dian.dashboard') }}">
                            <i class="fas fa-robot text-success"></i>
                            <span>Módulo DIAN</span>
                            <span class="badge bg-success ms-2">AUTO</span>
                        </a>
                        <div class="collapse {{ request()->routeIs('dian.*') ? 'show' : '' }}" id="dianSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <small class="text-muted ms-3">🤖 PROCESAMIENTO AUTOMÁTICO</small>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dian.dashboard') }}" 
                                       class="nav-link {{ request()->routeIs('dian.dashboard') ? 'active' : '' }}">
                                        <i class="fas fa-tachometer-alt text-success"></i> Dashboard DIAN
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dian.configuracion') }}" 
                                       class="nav-link {{ request()->routeIs('dian.configuracion') ? 'active' : '' }}">
                                        <i class="fas fa-cog text-primary"></i> Configuración
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dian.facturas') }}" 
                                       class="nav-link {{ request()->routeIs('dian.facturas') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice text-info"></i> Facturas Procesadas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dian.buzon') }}" 
                                       class="nav-link {{ request()->routeIs('dian.buzon') ? 'active' : '' }}">
                                        <i class="fas fa-inbox text-warning"></i> Buzón de Correos
                                        <span class="badge bg-warning text-dark ms-2">OUTLOOK</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('email-configurations.index') }}" 
                                       class="nav-link {{ request()->routeIs('email-configurations.*') ? 'active' : '' }}">
                                        <i class="fas fa-envelope-open-text text-info"></i> Config. Email
                                        <span class="badge bg-info text-white ms-2">NUEVO</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <hr class="dropdown-divider">
                                </li>
                                <li class="nav-item">
                                    <small class="text-muted ms-3">⚡ ACCIONES RÁPIDAS</small>
                                </li>
                                <li class="nav-item">
                                    <form action="{{ route('dian.procesar-emails') }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="nav-link btn btn-link text-start w-100 border-0 p-2">
                                            <i class="fas fa-download text-primary"></i> Procesar Emails
                                        </button>
                                    </form>
                                </li>
                                <li class="nav-item">
                                    <form action="{{ route('dian.enviar-acuses') }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="nav-link btn btn-link text-start w-100 border-0 p-2">
                                            <i class="fas fa-paper-plane text-success"></i> Enviar Acuses
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Módulo de Gestión Porcina eliminado -->
                    <!-- El código ha sido migrado al proyecto pig_farm_magnament -->

                    <!-- Módulo de Configuración -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#configMenu">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <div class="collapse {{ request()->routeIs('users.*', 'empresa.*', 'regularizacion.*', 'backup.*', 'facturacion.*') ? 'show' : '' }}" id="configMenu">
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
                                <li class="nav-item">
                                    <a href="{{ route('facturacion.index') }}" 
                                       class="nav-link {{ request()->routeIs('facturacion.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice-dollar"></i> Facturación Electrónica
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Acerca de -->
                    <li class="nav-item">
                        <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">
                            <i class="fas fa-info-circle"></i> Acerca de
                        </a>
                    </li>
                </ul>
                
                <!-- Footer del sidebar -->
                <div class="mt-auto pt-4 text-center">
                    <div class="app-title">
                        <h5>Sistema de Ventas</h5>
                        <p>LC DESARROLLO</p>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg" id="main-navbar">
            <div class="container-fluid">
                <!-- Información de Contacto del Desarrollador -->
                <div class="developer-contact d-none d-xl-flex">
                    <span class="contact-linear">
                        <i class="fas fa-code"></i>
                        Tecnólogo Luis Carlos Correa Arrieta
                        <span class="separator">|</span>
                        <i class="fas fa-phone"></i>
                        <a href="tel:3012481020">3012481020</a>
                        <span class="separator">|</span>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:pcapacho24@gmail.com">pcapacho24@gmail.com</a>
                    </span>
                </div>
                
                <div class="navbar-actions ms-auto d-flex align-items-center">
                    <!-- Botón de cambio de tema -->
                    <div class="nav-item me-3">
                        <a class="nav-link theme-toggle" id="theme-toggle">
                            <i class="fas fa-sun" id="theme-icon"></i>
                            <span id="theme-text">Modo Oscuro</span>
                        </a>
                    </div>
                    
                    <!-- Toggle Sidebar Button -->
                    <div class="nav-item me-3">
                        <a class="nav-link" id="sidebarCollapse">
                            <i class="fas fa-bars"></i>
                        </a>
                    </div>

                    <!-- Usuario Dropdown -->
                    <div class="dropdown">
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

    <!-- Scripts (archivos locales) -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>

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
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarCollapse = document.getElementById('sidebarCollapse');
                const sidebar = document.getElementById('sidebar');
                const content = document.getElementById('content');
                const navbar = document.getElementById('main-navbar');
                
                if (sidebarCollapse && sidebar && content) {
                    sidebarCollapse.addEventListener('click', function() {
                        sidebar.classList.toggle('collapsed');
                        content.classList.toggle('sidebar-collapsed');
                        if (navbar) {
                            navbar.classList.toggle('sidebar-collapsed');
                        }
                    });
                }
            });

            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Tema oscuro
            function setTheme(isDark) {
                if (isDark) {
                    $('body').addClass('dark-mode');
                    $('#theme-icon').removeClass('fa-sun').addClass('fa-moon');
                    $('#theme-text').text('Modo Claro');
                } else {
                    $('body').removeClass('dark-mode');
                    $('#theme-icon').removeClass('fa-moon').addClass('fa-sun');
                    $('#theme-text').text('Modo Oscuro');
                }
                localStorage.setItem('dark-mode', isDark ? 'true' : 'false');
            }
            
            // Verificar preferencia guardada
            const savedTheme = localStorage.getItem('dark-mode');
            if (savedTheme === 'true') {
                setTheme(true);
            }
            
            // Cambiar tema al hacer clic en el botón
            $('#theme-toggle').on('click', function() {
                const isDarkMode = $('body').hasClass('dark-mode');
                setTheme(!isDarkMode);
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