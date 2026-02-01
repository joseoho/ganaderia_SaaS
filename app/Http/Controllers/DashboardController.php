<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Venta;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $inquilinoId = Auth::user()->inquilino_id;

        // Totales filtrados por inquilino
        $totalAnimales = Animal::where('inquilino_id', $inquilinoId)->count();
        $totalCompras = Compra::where('inquilino_id', $inquilinoId)->sum('monto_total');
        $totalVentas = Venta::where('inquilino_id', $inquilinoId)->sum('monto_total');
        $notificacionesPendientes = Notificacion::where('usuario_id', Auth::id())
                                                ->where('estado', 'pendiente')
                                                ->count();

        // Últimas actividades
        $ultimasCompras = Compra::where('inquilino_id', $inquilinoId)
                                ->orderBy('fecha', 'desc')
                                ->take(5)
                                ->get();

        $ultimasVentas = Venta::where('inquilino_id', $inquilinoId)
                              ->orderBy('fecha', 'desc')
                              ->take(5)
                              ->get();

        return view('dashboard.index', compact(
            'totalAnimales',
            'totalCompras',
            'totalVentas',
            'notificacionesPendientes',
            'ultimasCompras',
            'ultimasVentas'
        ));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
