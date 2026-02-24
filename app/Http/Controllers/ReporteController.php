<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\ProduccionLeche;
use App\Models\ProduccionCarne;
use App\Models\Compra;
use App\Models\Venta;
use App\Models\Movilizacion;
use App\Models\Reproduccion;
use App\Models\InventarioInsumo;
use App\Models\Tratamiento;
use App\Models\RegistroVacuna;
use App\Models\Genealogia;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function index()
    {
        $inquilino = Auth::user()->inquilino_id;

        // ANIMALES
        $total_animales = Animal::where('inquilino_id', $inquilino)->count();
        $machos = Animal::where('inquilino_id', $inquilino)->where('sexo', 'M')->count();
        $hembras = Animal::where('inquilino_id', $inquilino)->where('sexo', 'H')->count();
        // $madres = Animal::where('inquilino_id', $inquilino)->where('es_madre', 1)->count();
        // $padres = Animal::where('inquilino_id', $inquilino)->where('es_padre', 1)->count();

        // PRODUCCIÓN
        $produccion_leche_total = ProduccionLeche::where('inquilino_id', $inquilino)->sum('litros');
        $produccion_carne_total = ProduccionCarne::where('inquilino_id', $inquilino)->sum('ganancia_diaria');

        // COMPRAS / VENTAS
        $compras = Compra::where('inquilino_id', $inquilino)->count();
        $ventas = Venta::where('inquilino_id', $inquilino)->count();

        // MOVILIZACIONES
        $movilizaciones = Movilizacion::where('inquilino_id', $inquilino)->count();

        // REPRODUCCIÓN
        $reproducciones = Reproduccion::where('inquilino_id', $inquilino)->count();

        // INVENTARIO
        $inventario_insumos = InventarioInsumo::where('inquilino_id', $inquilino)->get();

        // TRATAMIENTOS
        $tratamientos_activos = Tratamiento::where('inquilino_id', $inquilino)
            ->where(function($q){
                $q->whereNull('fecha_fin')
                  ->orWhere('fecha_fin', '>=', now()->toDateString());
            })
            ->with('animal')
            ->get();

        // HISTÓRICO DE TRATAMIENTOS
        $tratamientos_historico = Tratamiento::where('inquilino_id', $inquilino)
            ->with('animal')
            ->get();

        // VACUNAS
        $vacunas = RegistroVacuna::where('inquilino_id', $inquilino)
            ->with('animal')
            ->get();

        // GENEALOGÍA
        $genealogia = Genealogia::where('inquilino_id', $inquilino)->get();

        return view('reportes.general', compact(
            'total_animales',
            'machos',
            'hembras',
            // 'madres',
            // 'padres',
            'produccion_leche_total',
            'produccion_carne_total',
            'compras',
            'ventas',
            'movilizaciones',
            'reproducciones',
            'inventario_insumos',
            'tratamientos_activos',
            'tratamientos_historico',
            'vacunas',
            'genealogia'
        ));
    }
}