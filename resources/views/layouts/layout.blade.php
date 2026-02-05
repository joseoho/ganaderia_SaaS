<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') CONTROL GANADERO SaaS</title>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- jQuery UI (para datepicker) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos personalizados (si existen) -->
    @if(file_exists(public_path('css/dashboard.css')))
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    @endif
    
    @stack('styles')
</head>
  </head>
  <body>

  <!-- jQuery (opcional, necesario para algunos plugins) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
      <!-- SweetAlert2 JS -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Scripts personalizados (si existen) -->
    @if(file_exists(public_path('js/dashboard.js')))
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @endif
    
  
    
    @stack('scripts')
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
  <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">USUARIO: {{ Auth::user()->name }}</a>
  <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <input class="form-control form-control-dark w-100" value ="GANADERIA: {{ Auth::user()->inquilino->nombre }}" type="text" placeholder="Usuario" aria-label="usuario" readonly>
  <input value ="GANADERIA: {{ Auth::user()->inquilino->id }}" type="hidden" readonly>
  <div class="navbar-nav">
      <div class="nav-item text-nowrap">
           <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
               @csrf
           </form>
           <a class="nav-link px-3" href="/login" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
               Log out
           </a>
       </div>
   </div>
   
</header>

<div class="container-fluid">
  <div class="row">
   <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
  <div class="position-sticky pt-3">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="/dashboard">
          <span data-feather="dashboard"></span>
          Dashboard
        </a>
      </li>
      
      {{-- @if(Auth::user()->rol === 'Administrador') --}}
        <!-- Opciones solo para administrador -->
        <li class="nav-item">
          <a class="nav-link" href="/animales">
            <span data-feather="map"></span>
            Animales
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/notificaciones">
            <span data-feather="map"></span>
            Notificaciones
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/compras">
            <span data-feather="layers"></span>
            Compras
          </a>
        </li> 
        <li class="nav-item">
            <a class="nav-link" href="/ventas">
              <span data-feather="shopping-cart"></span>
              Ventas
            </a>
        <li class="nav-item">
          <a class="nav-link" href="/genealogia">
            <span data-feather="layers"></span>
            Genealogía
          </a>
        </li> 
        <li class="nav-item">
          <a class="nav-link" href="/inventario">
            <span data-feather="layers"></span>
            Inventarios Insumos
          </a>
        </li>   
          
        <li class="nav-item">
          <a class="nav-link" href="/movilizaciones">
            <span data-feather="map"></span>
            Movilizaciones
          </a>
        </li>
      {{-- @endif --}}
      
      <!-- Opciones para todos los usuarios -->
      <li class="nav-item">
        <a class="nav-link" href="/produccion">
          <span data-feather="droplet"></span>
          Producción carnes
        </a>
      </li>
        <li class="nav-item">
          <a class="nav-link" href="/inventario_leche">
            <span data-feather="repeat"></span>
            Inventario leche
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/registro_vacunas">
            <span data-feather="repeat"></span>
            Registro Vacunas
          </a>
        </li>
      @if(Auth::user()->rol === 'Administrador')
        <!-- Más opciones solo para admin -->
        <li class="nav-item">
          <a class="nav-link" href="/reportes">
            <span data-feather="dollar-sign"></span>
            Reportes
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/reproduccion">
            <span data-feather="credit-card"></span>
            Reproduccion
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/tratamientos">
            <span data-feather="credit-card"></span>
            Tratamientos
          </a>
        </li>
         <li class="nav-item">
          <a class="nav-link" href="/vacunas">
            <span data-feather="credit-card"></span>
            Vacunas
          </a>
        </li>
        
        {{-- <li class="nav-item">
          <a class="nav-link" href="{{ route('register') }}">
            <span data-feather="users"></span>
            Gestión Usuarios
          </a>
        </li> --}}
    
    </ul>
        <a class="nav-link" href="/transaccioneshistoricas">
                <span data-feather="file-text"></span>
              Historico Transacciones
        </a>
        <a class="nav-link" href="/animaleshistoricas">
                <span data-feather="file-text"></span>
              Historico Animales
        </a>
            @endif
    {{-- @if(Auth::user()->rol === 'Administrador') --}}
      {{-- <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Reportes</span>
        <a class="link-secondary" href="#" aria-label="Add a new report">
          <span data-feather="plus-circle"></span>
        </a>
      </h6>
      <ul class="nav flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link" href="/reportes">
            <span data-feather="file-text"></span>
            Reporte Transacciones
          </a>
          <a class="nav-link" href="/reportesanimales">
            <span data-feather="file-text"></span>
           Reporte Animales
          </a>
      
        </li> --}}
      </ul>
    {{-- @endif --}}
  </div>
</nav>
    
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-1 mb-2 border-bottom">
    <div class="d-flex align-items-center">
      <!-- Logo alineado a la izquierda -->
      <img src="{{ asset('assets/images/bobinonet.png') }}" style="height: 120px; width: auto; margin-right: 10px;" alt="Logo">
    </div>
  </div>
  @yield('content')
</main>
  </div>
</div>


    {{-- <script src="../assets/dist/js/bootstrap.bundle.min.js"></script> --}}

    {{-- <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script><script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script><script src="dashboard.js"></script> --}}

    
  </body>
</html>
