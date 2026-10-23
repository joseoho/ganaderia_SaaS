<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEtiquetaController;
use App\Http\Controllers\AnimalQrController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\GastosController;
use App\Http\Controllers\GenealogiController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InventarioInsumoController;
use App\Http\Controllers\MovilizacionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PlataformaController;
use App\Http\Controllers\PotreroController;
use App\Http\Controllers\ProduccionCarneController;
use App\Http\Controllers\ProduccionLecheController;
use App\Http\Controllers\RegistroVacunaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReproduccionController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Portada: redirige al panel si hay sesion y al login si no.
//
// NO se usa la vista `welcome` de Laravel: pide `resources/css/app.css`, que es
// Tailwind v4 y no esta en los inputs de Vite (la interfaz de este proyecto es
// Bootstrap), asi que en produccion devolvia un error 500. Era el unico test en
// rojo de la suite. Si algun dia se quiere una portada publica de verdad, se
// crea una vista propia y se cambia solo esta ruta.
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('inicio');

// =============================
//   AUTENTICACIÓN
// Alta publica de ganaderias. Las rutas existen siempre, pero el middleware
// `registro-abierto` responde 404 si REGISTRO_PUBLICO esta desactivado; asi el
// interruptor se decide en cada peticion (y se puede probar) en vez de al
// arrancar la aplicacion. El limite de intentos frena las altas automatizadas.
Auth::routes(['register' => false, 'verify' => true]);

Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])
    ->middleware(['guest', 'registro-abierto'])
    ->name('register');

Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
    ->middleware(['guest', 'registro-abierto', 'throttle:10,1']);

// El middleware se declara en la ruta (estilo Laravel 11+) en lugar del
// constructor del controlador, que es la forma antigua.
//
// `verified` en /home es lo que cierra el circulo: recien registrado, el usuario
// cae en /home y desde ahi se le manda a verificar el correo.
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('home');

// =============================
//   RUTAS PROTEGIDAS
// =============================
// `solo-admin` limita los borrados (verbo DELETE) al administrador de la
// ganaderia; el resto del panel queda abierto al operario.
Route::middleware(['auth', 'verified', 'tenant', 'suscripcion', 'solo-admin'])->group(function () {

    Route::resource('notificaciones', NotificacionController::class)
        ->parameters(['notificaciones' => 'notificacion']);
    // =============================
    //   ETIQUETAS QR
    // =============================
    Route::get('/animales/etiquetas', [AnimalEtiquetaController::class, 'index'])
        ->name('animales.etiquetas');

    Route::post('/animales/etiquetas/generar', [AnimalEtiquetaController::class, 'generar'])
        ->name('animales.etiquetas.generar');

    Route::resource('animales', AnimalController::class)
        ->parameters(['animales' => 'animal']);

    // Compras, ventas y gastos son DINERO: solo con el permiso `dinero.ver`
    // (ver la matriz en app/Support/MatrizDePermisos.php).
    Route::resource('compras', CompraController::class)
        ->parameters(['compras' => 'compra'])
        ->middleware('can:dinero.ver');

    Route::resource('ventas', VentaController::class)
        ->parameters(['ventas' => 'venta'])
        ->middleware('can:dinero.ver');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // =============================
    //   SUSCRIPCIÓN Y COBRO
    // =============================
    // El estado lo ve cualquiera de la finca (también cuando está vencida: es la
    // pantalla a la que se redirige). Subir el pago, solo el administrador: es
    // dinero y compromete a la finca.
    Route::get('suscripcion', [SuscripcionController::class, 'index'])->name('suscripcion.index');
    Route::post('suscripcion/pagos', [SuscripcionController::class, 'storePago'])
        ->middleware('can:usuarios.gestionar')
        ->name('suscripcion.pagos.store');

    // Vaciar los datos de ejemplo de una ganaderia demo. Es un borrado masivo:
    // la comprobacion de permiso va dentro del controlador (`registros.eliminar`)
    // porque ademas exige que la ganaderia este marcada como demo.
    Route::post('demo/vaciar', [DemoController::class, 'vaciar'])->name('demo.vaciar');

    // =============================
    //   USUARIOS DE LA GANADERIA
    // =============================
    // Alta, cambio de rol y activacion/desactivacion de la gente. No hay
    // `destroy` a proposito: las cuentas se desactivan, porque el historico
    // (quien registro una venta, quien hizo un pesaje) apunta a ellas.
    Route::middleware('can:usuarios.gestionar')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])
            ->name('usuarios.estado');
    });

    Route::resource('genealogia', GenealogiController::class)
        ->parameters(['genealogia' => 'genealogium']);

    Route::resource('inventario', InventarioInsumoController::class)
        ->parameters(['inventario' => 'inventario']);

    Route::resource('movilizaciones', MovilizacionController::class)
        ->parameters(['movilizaciones' => 'movilizacione']);

    Route::resource('produccion', ProduccionCarneController::class)
        ->parameters(['produccion' => 'produccion']);

    Route::resource('produccion_leche', ProduccionLecheController::class)
        ->parameters(['produccion_leche' => 'produccion_leche']);

    Route::resource('registro_vacunas', RegistroVacunaController::class)
        ->parameters(['registro_vacunas' => 'registro_vacuna']);

    Route::resource('reproduccion', ReproduccionController::class)
        ->parameters(['reproduccion' => 'reproduccion']);

    Route::resource('tratamientos', TratamientoController::class)
        ->parameters(['tratamientos' => 'tratamiento']);

    Route::resource('potreros', PotreroController::class);

    Route::post('potreros/{id}/asignar', [PotreroController::class, 'asignarAnimales'])
        ->name('potreros.asignar');

    Route::post('potreros/{id}/salida', [PotreroController::class, 'salidaAnimales'])
        ->name('potreros.salida');

    Route::resource('gastos', GastosController::class)
        ->middleware('can:dinero.ver');

    // =============================
    //   REPORTES
    // =============================
    Route::get('reporte-general', [ReporteController::class, 'index'])
        ->name('reporte.general');

    Route::get('reporte-general/animal', [ReporteController::class, 'seleccionarAnimal'])
        ->name('reporte.general.animal.select');

    Route::get('reporte-general/animal/generar', [ReporteController::class, 'porAnimal'])
        ->name('reporte.general.animal');

    Route::get('reporte-porfecha', [ReporteController::class, 'porFechas'])
        ->name('reporte.porfechas');

    // Reportes generales
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');

    // Reporte animales en potreros
    Route::get('reportes/potreros', [ReporteController::class, 'reportePotreros'])->name('reportes.potreros');
    Route::get('reportes/potreros/generar', [ReporteController::class, 'generarPotreros'])->name('reportes.potreros.generar');

    // Reportes de DINERO: mismo permiso que las pantallas de las que salen.
    // Los de produccion, potreros y animales no son dinero y quedan abiertos.
    Route::get('reportes/compras', [ReporteController::class, 'reporteCompras'])->name('reportes.compras')->middleware('can:dinero.ver');
    Route::get('reportes/compras/generar', [ReporteController::class, 'generarCompras'])->name('reportes.compras.generar')->middleware('can:dinero.ver');

    // Reporte ventas
    Route::get('reportes/ventas', [ReporteController::class, 'reporteVentas'])->name('reportes.ventas')->middleware('can:dinero.ver');
    Route::get('reportes/ventas/generar', [ReporteController::class, 'generarVentas'])->name('reportes.ventas.generar')->middleware('can:dinero.ver');

    // Reporte producción carne
    Route::get('reportes/carne', [ReporteController::class, 'reporteCarne'])->name('reportes.carne');
    Route::get('reportes/carne/generar', [ReporteController::class, 'generarCarne'])->name('reportes.carne.generar');

    // Reporte producción leche
    Route::get('reportes/leche', [ReporteController::class, 'reporteLeche'])->name('reportes.leche');
    Route::get('reportes/leche/generar', [ReporteController::class, 'generarLeche'])->name('reportes.leche.generar');

    // Reporte Financiero
    Route::get('reportes/financiero', [ReporteController::class, 'reporteFinanciero'])
        ->name('reportes.financiero')
        ->middleware('can:dinero.ver');

    // =============================
    //   QR DE ANIMALES
    // =============================
    Route::get('animales/{animal}/qr', [AnimalController::class, 'qr'])
        ->name('animales.qr');

    Route::get('animal/qr/{id}', [AnimalQrController::class, 'show'])
        ->name('animal.qr.show');

    // =============================
    //   QR PARA EDITAR LOS DATOS DEL ANIMAL
    // =============================

    Route::get('animal/qr/{id}/editar-todo', [AnimalQrController::class, 'editarTodo'])
        ->name('animal.qr.editar.todo');

    Route::post('animal/qr/{id}/actualizar-todo', [AnimalQrController::class, 'actualizarTodo'])
        ->name('animal.qr.actualizar.todo');

    // //////dashboad reportes
    Route::get('dashboard-reportes', [ReporteController::class, 'dashboard'])
        ->name('reportes.dashboard');

    // /////Import Controller
    // La importacion escribe en masa y puede PISAR datos (animales por codigo):
    // exige `datos.importar`, que hoy solo tiene el administrador.
    Route::prefix('importar')->middleware('can:datos.importar')->group(function () {

        // Dashboard de importación
        Route::get('/', [ImportController::class, 'index'])->name('import.index');

        // Animales
        Route::get('/animales', [ImportController::class, 'animalesForm'])->name('import.animales.form');
        Route::post('/animales/upload', [ImportController::class, 'animalesUpload'])->name('import.animales.upload');
        Route::post('/animales/preview', [ImportController::class, 'animalesPreview'])->name('import.animales.preview');
        Route::post('/animales/import', [ImportController::class, 'animalesImport'])->name('import.animales.import');

        // Producción: DOS pantallas, una por tipo.
        //
        // Antes era una sola que adivinaba por fila si aquello era leche o carne.
        // Al separarlas, cada tabla tiene sus propias columnas obligatorias y ya
        // no hay que rellenar media plantilla con celdas vacías.
        Route::prefix('produccion/{tipo}')
            ->whereIn('tipo', ['leche', 'carne'])
            ->group(function () {
                Route::get('/', [ImportController::class, 'produccionForm'])->name('import.produccion.form');
                Route::post('/upload', [ImportController::class, 'produccionUpload'])->name('import.produccion.upload');
                Route::post('/preview', [ImportController::class, 'produccionPreview'])->name('import.produccion.preview');
                Route::post('/import', [ImportController::class, 'produccionImport'])->name('import.produccion.import');
            });

        // Compras
        Route::get('/compras', [ImportController::class, 'comprasForm'])->name('import.compras.form');
        Route::post('/compras/upload', [ImportController::class, 'comprasUpload'])->name('import.compras.upload');
        Route::post('/compras/preview', [ImportController::class, 'comprasPreview'])->name('import.compras.preview');
        Route::post('/compras/import', [ImportController::class, 'comprasImport'])->name('import.compras.import');

        // Ventas
        Route::get('/ventas', [ImportController::class, 'ventasForm'])->name('import.ventas.form');
        Route::post('/ventas/upload', [ImportController::class, 'ventasUpload'])->name('import.ventas.upload');
        Route::post('/ventas/preview', [ImportController::class, 'ventasPreview'])->name('import.ventas.preview');
        Route::post('/ventas/import', [ImportController::class, 'ventasImport'])->name('import.ventas.import');
    });
}); // ← ESTE ES EL ÚNICO CIERRE CORRECTO

// =============================
//   PLATAFORMA (el operador que cobra)
// =============================
// Fuera del grupo de la finca a proposito: el operador no tiene ganaderia, asi
// que no pasa por `tenant` ni por la comprobacion de suscripcion.
Route::middleware(['auth', 'verified', 'can:plataforma.ver_pagos'])
    ->prefix('plataforma')
    ->name('plataforma.')
    ->group(function () {
        Route::get('pagos', [PlataformaController::class, 'pagos'])->name('pagos');
        Route::get('pagos/{pago}/comprobante', [PlataformaController::class, 'comprobante'])->name('comprobante');
        Route::post('pagos/{pago}/aprobar', [PlataformaController::class, 'aprobar'])->name('aprobar');
        Route::post('pagos/{pago}/rechazar', [PlataformaController::class, 'rechazar'])->name('rechazar');
    });
