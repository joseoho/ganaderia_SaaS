<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\VentaController;

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
});