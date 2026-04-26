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
use App\Models\AnimalPotrero;
use App\Models\Potrero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
;
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


    // ============================
    // 1. FORMULARIOS
    // ============================

    public function reportePotreros()
    {
        return view('reportes.potreros.form');
    }

    public function reporteCompras()
    {
        return view('reportes.compras.form');
    }

    public function reporteVentas()
    {
        return view('reportes.ventas.form');
    }

    public function reporteCarne()
    {
        return view('reportes.carne.form');
    }

    public function reporteLeche()
    {
        return view('reportes.leche.form');
    }


    // ============================
    // 2. GENERADORES DE REPORTES
    // ============================

    public function generarPotreros(Request $request)
{
    $request->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date'
    ]);

    $registros = AnimalPotrero::with('animal', 'potrero')
        ->where('inquilino_id', Auth::user()->inquilino_id)
        ->whereBetween('fecha_entrada', [$request->desde, $request->hasta])
        ->get();

    // Totales
    $totalAnimales = $registros->count();
    $totalPotreros = $registros->groupBy('potrero_id')->count();

    return view('reportes.potreros.reporte', compact('registros', 'totalAnimales', 'totalPotreros'));
}

    public function generarCompras(Request $request)
{
    $request->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date'
    ]);

    $compras = Compra::where('inquilino_id', Auth::user()->inquilino_id)
        ->whereBetween('fecha', [$request->desde, $request->hasta])
        ->get();

    // Totales
    $totalCompras = $compras->count();
    $montoTotal = $compras->sum('monto');

    return view('reportes.compras.reporte', compact('compras', 'totalCompras', 'montoTotal'));
}

    public function generarVentas(Request $request)
{
    $request->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date'
    ]);

    $ventas = Venta::where('inquilino_id', Auth::user()->inquilino_id)
        ->whereBetween('fecha', [$request->desde, $request->hasta])
        ->get();

    // Totales
    $totalVentas = $ventas->count();
    $montoTotal = $ventas->sum('monto');

    return view('reportes.ventas.reporte', compact('ventas', 'totalVentas', 'montoTotal'));
}


   public function generarCarne(Request $request)
{
    $request->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date'
    ]);

    $carne = ProduccionCarne::with('animal')
        ->where('inquilino_id', Auth::user()->inquilino_id)
        ->whereBetween('fecha', [$request->desde, $request->hasta])
        ->get();

    // Totales
    $totalRegistros = $carne->count();
    $totalGanado = $carne->sum('ganancia_diaria');
    $promedioGanancia = $totalRegistros > 0 ? $totalGanado / $totalRegistros : 0;

    return view('reportes.carne.reporte', compact('carne', 'totalRegistros', 'totalGanado', 'promedioGanancia'));
}

    public function generarLeche(Request $request)
{
    $request->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date'
    ]);

    $leche = ProduccionLeche::with('animal')
        ->where('inquilino_id', Auth::user()->inquilino_id)
        ->whereBetween('fecha', [$request->desde, $request->hasta])
        ->get();

    // Totales
    $totalRegistros = $leche->count();
    $totalLitros = $leche->sum('litros');
    $promedioLitros = $totalRegistros > 0 ? $totalLitros / $totalRegistros : 0;

    return view('reportes.leche.reporte', compact('leche', 'totalRegistros', 'totalLitros', 'promedioLitros'));
}

public function dashboard()
{
    $inquilino = Auth::user()->inquilino_id;

    // Totales rápidos
    $totalCompras = Compra::where('inquilino_id', $inquilino)->count();
    $totalVentas = Venta::where('inquilino_id', $inquilino)->count();
    $totalCarne = ProduccionCarne::where('inquilino_id', $inquilino)->sum('ganancia_diaria');
    $totalLeche = ProduccionLeche::where('inquilino_id', $inquilino)->sum('litros');
    $totalPotreros = Potrero::where('inquilino_id', $inquilino)->count();
    $totalAnimales = Animal::where('inquilino_id', $inquilino)->count();

    // REPORTE COMBINADO: Producción total por animal
    $produccionTotal = Animal::where('inquilino_id', $inquilino)
        ->with([
            'produccionCarne' => function ($q) {
                $q->select('animal_id', DB::raw('SUM(ganancia_diaria) as total_carne'))
                  ->groupBy('animal_id');
            },
            'produccionLeche' => function ($q) {
                $q->select('animal_id', DB::raw('SUM(litros) as total_leche'))
                  ->groupBy('animal_id');
            }
        ])
        ->get()
        ->map(function ($animal) {
            return [
                'codigo' => $animal->codigo_interno,
                'total_carne' => $animal->produccionCarne->first()->total_carne ?? 0,
                'total_leche' => $animal->produccionLeche->first()->total_leche ?? 0,
                'total_general' => ($animal->produccionCarne->first()->total_carne ?? 0) +
                                   ($animal->produccionLeche->first()->total_leche ?? 0),
            ];
        })
        ->sortByDesc('total_general')
        ->take(10); // Top 10 animales

    return view('reportes.dashboard', compact(
        'totalCompras',
        'totalVentas',
        'totalCarne',
        'totalLeche',
        'totalPotreros',
        'produccionTotal',
        'totalAnimales'
    ));
}

}



