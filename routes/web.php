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

Route::get('/', function () {
    return view('welcome');
});
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
// Route::resource('notificaciones', App\Http\Controllers\NotificacionController::class);
Route::resource('notificaciones', App\Http\Controllers\NotificacionController::class)
     ->parameters(['notificaciones' => 'notificacion']);
// Route::resource('animales', App\Http\Controllers\AnimalController::class);
Route::resource('animales', App\Http\Controllers\AnimalController::class)
     ->parameters(['animales' => 'animal']);
     Route::resource('compras', App\Http\Controllers\CompraController::class)
     ->parameters(['compras' => 'compra']);
Route::resource('ventas', App\Http\Controllers\VentaController::class)
     ->parameters(['ventas' => 'venta']);
     Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
     ->name('dashboard');
     Route::resource('genealogia', App\Http\Controllers\GenealogiController::class)
     ->parameters(['genealogia' => 'genealogium']);
     Route::resource('inventario', App\Http\Controllers\InventarioInsumoController::class)
     ->parameters(['inventario' => 'inventario']);
     Route::resource('movilizaciones', App\Http\Controllers\MovilizacionController::class)
     ->parameters(['movilizaciones' => 'movilizacione']);
     Route::resource('produccion', App\Http\Controllers\ProduccionCarneController::class)
     ->parameters(['produccion' => 'produccion']);
     Route::resource('produccion_leche', App\Http\Controllers\ProduccionLecheController::class)
     ->parameters(['produccion_leche' => 'produccion_leche']);
     // Route::resource('vacunas', App\Http\Controllers\VacunaController::class)
     // ->parameters(['vacunas' => 'vacuna']);
     Route::resource('registro_vacunas', App\Http\Controllers\RegistroVacunaController::class)
     ->parameters(['registro_vacunas' => 'registro_vacuna']);
     Route::resource('reproduccion', App\Http\Controllers\ReproduccionController::class)
     ->parameters(['reproduccion' => 'reproduccion']);
     Route::resource('tratamientos', App\Http\Controllers\TratamientoController::class)
     ->parameters(['tratamientos' => 'tratamiento']);
     Route::get('reporte-general', [App\Http\Controllers\ReporteController::class, 'index'])
    ->name('reporte.general');
});