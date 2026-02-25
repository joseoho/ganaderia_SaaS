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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    // REPORTE GENERAL CON FILTROS
    public function index(Request $request)
    {
        $inquilino = Auth::user()->inquilino_id;

        // FILTROS
        $desde = $request->desde;
        $hasta = $request->hasta;

        // ANIMALES
        $total_animales = Animal::where('inquilino_id', $inquilino)->count();
        $machos = Animal::where('inquilino_id', $inquilino)->where('sexo', 'M')->count();
        $hembras = Animal::where('inquilino_id', $inquilino)->where('sexo', 'H')->count();
        $madres = Animal::where('inquilino_id', $inquilino)->where('tipo', 'Madre')->count();
        $padres = Animal::where('inquilino_id', $inquilino)->where('tipo', 'Padre')->count();
        $mautes = Animal::where('inquilino_id', $inquilino)->where('tipo', 'Maute')->count();

        // PRODUCCIÓN FILTRADA
        $produccion_leche_total = ProduccionLeche::where('inquilino_id', $inquilino)
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->sum('litros');

        $produccion_carne_total = ProduccionCarne::where('inquilino_id', $inquilino)
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->sum('ganancia_diaria');

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
            'madres',
            'padres',
            'mautes',
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
            'genealogia',
            'desde',
            'hasta'
        ));
    }

    // REPORTE POR ANIMAL
    public function porAnimal(Request $request)
{
    $request->validate([
        'animal_id' => 'required'
    ]);

    $animal = Animal::where('id', $request->animal_id)
        ->where('inquilino_id', Auth::user()->inquilino_id)
        ->firstOrFail();

    $produccion_leche = ProduccionLeche::where('animal_id', $animal->id)->get();
    $produccion_carne = ProduccionCarne::where('animal_id', $animal->id)->get();
    $tratamientos = Tratamiento::where('animal_id', $animal->id)->get();
    $vacunas = RegistroVacuna::where('animal_id', $animal->id)->get();
    $reproduccion = Reproduccion::where('animal_id', $animal->id)->get();
    $genealogia = Genealogia::where('animal_id', $animal->id)->first();

    return view('reportes.poranimal', compact(
        'animal',
        'produccion_leche',
        'produccion_carne',
        'tratamientos',
        'vacunas',
        'reproduccion',
        'genealogia'
    ));
}

    public function porFechas(Request $request)
    {
        $inquilino = Auth::user()->inquilino_id;

        // FILTROS
        $desde = $request->desde;
        $hasta = $request->hasta;

        // ANIMALES
        $total_animales = Animal::where('inquilino_id', $inquilino)->count();
        $machos = Animal::where('inquilino_id', $inquilino)->where('sexo', 'M')->count();
        $hembras = Animal::where('inquilino_id', $inquilino)->where('sexo', 'H')->count();
        $madres = Animal::where('inquilino_id', $inquilino)->where('tipo', 'Madre')->count();
        $padres = Animal::where('inquilino_id', $inquilino)->where('tipo', 'Padre')->count();
        $mautes = Animal::where('inquilino_id', $inquilino)->where('tipo', 'Maute')->count();

        // PRODUCCIÓN FILTRADA
        $produccion_leche_total = ProduccionLeche::where('inquilino_id', $inquilino)
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->sum('litros');

        $produccion_carne_total = ProduccionCarne::where('inquilino_id', $inquilino)
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->sum('ganancia_diaria');

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

        $tratamientos_historico = Tratamiento::where('inquilino_id', $inquilino)
            ->with('animal')
            ->get();

        // VACUNAS
        $vacunas = RegistroVacuna::where('inquilino_id', $inquilino)
            ->with('animal')
            ->get();

        // GENEALOGÍA
        $genealogia = Genealogia::where('inquilino_id', $inquilino)->get();

        return view('reportes.porfechas', compact(
            'total_animales',
            'machos',
            'hembras',
            'madres',
            'padres',
            'mautes',
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
            'genealogia',
            'desde',
            'hasta'
        ));
    }

    public function seleccionarAnimal()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();

        return view('reportes.seleccionaranimal', compact('animales'));
    }

}