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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


// =============================
//   RUTAS PROTEGIDAS
// =============================
Route::middleware(['auth', 'verified'])->group(function () {

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

//////////potreros
    Route::resource('potreros', PotreroController::class);
    Route::post('potreros/{id}/asignar', [PotreroController::class, 'asignarAnimales'])
        ->name('potreros.asignar');
    Route::post('potreros/{id}/salida', [PotreroController::class, 'salidaAnimales'])
        ->name('potreros.salida');
}); // ← ESTE ES EL ÚNICO CIERRE CORRECTO