<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') | CONTROL GANADERO SaaS</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- jQuery UI (para datepicker) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    
    <!-- Bootstrap 5 (Solo una vez) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Estilos personalizados -->
    @if(file_exists(public_path('css/dashboard.css')))
        <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    @endif
    
    @stack('styles')
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">
            USUARIO: {{ Auth::user()->name }}
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <input class="form-control form-control-dark w-100" value="GANADERIA: {{ Auth::user()->inquilino->nombre }}" type="text" readonly>
        <input value="{{ Auth::user()->inquilino->id }}" type="hidden" readonly>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a class="nav-link px-3" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="/dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <!-- Opciones principales -->
                        <li class="nav-item">
                            <a class="nav-link" href="/animales">
                                <i class="fas fa-cow"></i> Animales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/produccion">
                                <i class="fas fa-drumstick-bite"></i> Producción Carnes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/produccion_leche">
                                <i class="fas fa-wine-bottle"></i> Producción Leche
                            </a>
                        </li>
                        
                        <!-- Solo Admin -->
                       {{-- @if(Auth::user()->rol === 'Administrador') --}}
                            <li class="nav-item">
                                <a class="nav-link" href="/compras">
                                    <i class="fas fa-shopping-cart"></i> Compras
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ventas">
                                    <i class="fas fa-cash-register"></i> Ventas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/movilizaciones">
                                    <i class="fas fa-truck-moving"></i> Movilizaciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/reproduccion">
                                    <i class="fas fa-baby"></i> Reproducción
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/inventario">
                                    <i class="fas fa-boxes"></i> Inventario Insumos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/tratamientos">
                                    <i class="fas fa-stethoscope"></i> Tratamientos
                                </a>
                            </li>
                            {{-- <li class="nav-item">
                                <a class="nav-link" href="/vacunas">
                                    <i class="fas fa-syringe"></i> Vacunas
                                </a>
                            </li> --}}
                            <li class="nav-item">
                                <a class="nav-link" href="/reportes">
                                    <i class="fas fa-chart-bar"></i> Reportes
                                </a>
                            </li>
                            
                            
                            <!-- Gestión -->
                            <li class="nav-item">
                                <a class="nav-link" href="/notificaciones">
                                    <i class="fas fa-bell"></i> Notificaciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/genealogia">
                                    <i class="fas fa-sitemap"></i> Genealogía
                                </a>
                            </li>
                       {{-- @endif --}}
                        
                        <!-- Para todos los usuarios -->
                        <li class="nav-item">
                            <a class="nav-link" href="/registro_vacunas">
                                <i class="fas fa-shield-alt"></i> Registro Vacunas
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('assets/images/bobinonet.png') }}" style="height: 70px; width: auto; margin-right: 15px;" alt="Logo">
                        <h1 class="h2">@yield('page-title', 'Panel de Control')</h1>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        @yield('header-buttons')
                    </div>
                </div>

                <!-- Mensajes de Alerta (Aquí se muestran los errores y success) -->
                <div class="container mt-2">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>¡Por favor corrige los siguientes errores!
                            </h5>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>

                <!-- Contenido de las vistas -->
                <div class="container-fluid">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts al final del body para mejor performance -->
    <!-- Bootstrap Bundle con Popper (Solo una vez) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Feather Icons (ejecutar después de cargar) -->
    <script>
        // Inicializar Feather Icons cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            
            // Auto-ocultar alertas después de 5 segundos
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
    
    <!-- Scripts personalizados -->
    @if(file_exists(public_path('js/dashboard.js')))
        <script src="{{ asset('js/dashboard.js') }}"></script>
    @endif
    
    @stack('scripts')
</body>
</html>