<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Venta;
use App\Models\Notificacion;
use App\Models\Gastos;
use App\Models\Reproduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $inquilinoId = Auth::user()->inquilino_id;

        // Próximos partos (filtrado directo en BD)
        $partosProximos = Reproduccion::where('inquilino_id', $inquilinoId)
            ->whereIn('tipo', ['monta', 'inseminación'])
            ->whereRaw('DATE_ADD(fecha, INTERVAL 283 DAY) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
            ->get();

        // Totales
        $totalAnimales = Animal::where('inquilino_id', $inquilinoId)->count();
        $totalCompras  = Compra::where('inquilino_id', $inquilinoId)->sum('monto_total');
        $totalVentas   = Venta::where('inquilino_id', $inquilinoId)->sum('monto_total');
        $totalGastos   = Gastos::where('inquilino_id', $inquilinoId)->sum('monto');
        $notificacionesPendientes = Notificacion::where('usuario_id', Auth::id())
            ->where('estado', 'pendiente')
            ->count();

        // Últimas actividades
        $ultimosGastos = Gastos::where('inquilino_id', $inquilinoId)
            ->orderBy('fecha', 'desc')
            ->take(5)
            ->get();

        $ultimasVentas = Venta::where('inquilino_id', $inquilinoId)
            ->orderBy('fecha', 'desc')
            ->take(5)
            ->get();

        // Totalizar leche y carne del mes
        $mesActual  = now()->month;
        $anioActual = now()->year;

        $totalLecheMes = \App\Models\ProduccionLeche::where('inquilino_id', $inquilinoId)
            ->whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->sum('litros');

        $totalCarneMes = \App\Models\ProduccionCarne::where('inquilino_id', $inquilinoId)
            ->whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->sum('ganancia_diaria');

        return view('dashboard.index', compact(
            'totalAnimales',
            'totalCompras',
            'totalVentas',
            'totalGastos',
            'notificacionesPendientes',
            'ultimosGastos',
            'ultimasVentas',
            'partosProximos',
            'totalLecheMes',
            'totalCarneMes'
        ));
    }

    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}