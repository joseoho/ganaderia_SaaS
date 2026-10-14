<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenealogiController;
use App\Http\Controllers\InventarioInsumoController;
use App\Http\Controllers\MovilizacionController;
use App\Http\Controllers\ProduccionCarneController;
use App\Http\Controllers\ProduccionLecheController;
use App\Http\Controllers\RegistroVacunaController;
use App\Http\Controllers\ReproduccionController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\AnimalQrController;
use App\Http\Controllers\AnimalEtiquetaController;
use App\Http\Controllers\PotreroController;
use App\Http\Controllers\GastosController;
use App\Http\Controllers\ImportController;
Route::get('/', function () {
    return view('welcome');
});

//=============================
//   AUTENTICACIÓN
Auth::routes();

// El middleware se declara en la ruta (estilo Laravel 11+) en lugar del
// constructor del controlador, que es la forma antigua.
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->middleware(['auth', 'tenant'])
    ->name('home');


// =============================
//   RUTAS PROTEGIDAS
// =============================
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {

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

    Route::resource('compras', CompraController::class)
        ->parameters(['compras' => 'compra']);

    Route::resource('ventas', VentaController::class)
        ->parameters(['ventas' => 'venta']);

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

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


    Route::resource('gastos', GastosController::class);

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

    // Reporte compras
    Route::get('reportes/compras', [ReporteController::class, 'reporteCompras'])->name('reportes.compras');
    Route::get('reportes/compras/generar', [ReporteController::class, 'generarCompras'])->name('reportes.compras.generar');

    // Reporte ventas
    Route::get('reportes/ventas', [ReporteController::class, 'reporteVentas'])->name('reportes.ventas');
    Route::get('reportes/ventas/generar', [ReporteController::class, 'generarVentas'])->name('reportes.ventas.generar');

    // Reporte producción carne
    Route::get('reportes/carne', [ReporteController::class, 'reporteCarne'])->name('reportes.carne');
    Route::get('reportes/carne/generar', [ReporteController::class, 'generarCarne'])->name('reportes.carne.generar');

    // Reporte producción leche
    Route::get('reportes/leche', [ReporteController::class, 'reporteLeche'])->name('reportes.leche');
    Route::get('reportes/leche/generar', [ReporteController::class, 'generarLeche'])->name('reportes.leche.generar');

    // Reporte Financiero
    Route::get('reportes/financiero', [ReporteController::class, 'reporteFinanciero'])
        ->name('reportes.financiero');

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


////////dashboad reportes
        Route::get('dashboard-reportes', [ReporteController::class, 'dashboard'])
    ->name('reportes.dashboard');

///////Import Controller
Route::prefix('importar')->group(function () {

    // Dashboard de importación
    Route::get('/', [ImportController::class, 'index'])->name('import.index');

    // Animales
    Route::get('/animales', [ImportController::class, 'animalesForm'])->name('import.animales.form');
    Route::post('/animales/upload', [ImportController::class, 'animalesUpload'])->name('import.animales.upload');
    Route::post('/animales/preview', [ImportController::class, 'animalesPreview'])->name('import.animales.preview');
    Route::post('/animales/import', [ImportController::class, 'animalesImport'])->name('import.animales.import');

    // Producción
    Route::get('/produccion', [ImportController::class, 'produccionForm'])->name('import.produccion.form');
    Route::post('/produccion/upload', [ImportController::class, 'produccionUpload'])->name('import.produccion.upload');
    Route::post('/produccion/preview', [ImportController::class, 'produccionPreview'])->name('import.produccion.preview');
    Route::post('/produccion/import', [ImportController::class, 'produccionImport'])->name('import.produccion.import');

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