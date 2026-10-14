<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\Venta;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        $importados = session('ultimo_importado', 0);

        return view('import.resultado', compact('importados'));
    }

    // ============================
    // ANIMALES
    // ============================
    public function animalesForm()
    {
        return view('import.animales.form');
    }

    public function animalesUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            $headers = $this->importService->obtenerEncabezados($rows);

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para el mapeo de animales
            $camposSistema = [
                'codigo_interno' => 'Código Interno',
                'categoria' => 'Categoría',
                'raza' => 'Raza',
                'tipo' => 'Tipo',
                'fecha_nacimiento' => 'Fecha de Nacimiento',
                'sexo' => 'Sexo',
                'peso_entrada' => 'Peso Entrada',
                'estado' => 'Estado',
            ];

            // Sugerir mapeo automático
            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            session([
                'rows_animales' => $rows,
                'headers_animales' => $headers,
            ]);

            return view('import.animales.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en animalesUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function animalesPreview(Request $request)
    {
        $map = $request->map;
        $rows = collect(session('rows_animales'));

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_animales' => $map]);

        return view('import.animales.preview', compact('preview'));
    }

    public function animalesImport()
    {
        $rows = collect(session('rows_animales'));
        $map = session('map_animales');
        $inquilino = Auth::user()->inquilino_id;

        $importados = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            if (empty($data['codigo_interno'])) {
                continue;
            }

            Animal::updateOrCreate(
                [
                    'inquilino_id' => $inquilino,
                    'codigo_interno' => $data['codigo_interno'],
                ],
                array_merge($data, ['inquilino_id' => $inquilino])
            );

            $importados++;
        }

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', ['importados' => $importados]);
    }

    // ============================
    // PRODUCCIÓN
    // ============================
    public function produccionForm()
    {
        return view('import.produccion.form');
    }

    public function produccionUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            $headers = $this->importService->obtenerEncabezados($rows);

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para producción
            $camposSistema = [
                'animal_id' => 'ID del Animal',
                'fecha' => 'Fecha',
                'litros' => 'Litros (Leche)',
                'turno' => 'Turno',
                'ganancia_diaria' => 'Ganancia Diaria (Carne)',
                'peso' => 'Peso (Carne)',
                'observaciones' => 'Observaciones',
            ];

            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            session([
                'rows_produccion' => $rows,
                'headers_produccion' => $headers,
            ]);

            return view('import.produccion.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en produccionUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function produccionPreview(Request $request)
    {
        $map = $request->map;
        $rows = collect(session('rows_produccion'));

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_produccion' => $map]);

        return view('import.produccion.preview', compact('preview'));
    }

    public function produccionImport()
    {
        $rows = collect(session('rows_produccion'));
        $map = session('map_produccion');
        $inquilino = Auth::user()->inquilino_id;

        $importados = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            if (empty($data['animal_id'])) {
                continue;
            }

            if (isset($data['litros'])) {
                ProduccionLeche::create($data + ['inquilino_id' => $inquilino]);
                $importados++;
            }

            if (isset($data['ganancia_diaria']) || isset($data['peso'])) {
                ProduccionCarne::create($data + ['inquilino_id' => $inquilino]);
                $importados++;
            }
        }

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', ['importados' => $importados]);
    }

    // ============================
    // COMPRAS
    // ============================
    public function comprasForm()
    {
        return view('import.compras.form');
    }

    public function comprasUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            $headers = $this->importService->obtenerEncabezados($rows);

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para compras
            $camposSistema = [
                'fecha' => 'Fecha',
                'descripcion' => 'Descripción',
                'monto_total' => 'Monto Total',
                'proveedor' => 'Proveedor',
            ];

            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            session([
                'rows_compras' => $rows,
                'headers_compras' => $headers,
            ]);

            return view('import.compras.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en comprasUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function comprasPreview(Request $request)
    {
        $map = $request->map;
        $rows = collect(session('rows_compras'));

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_compras' => $map]);

        return view('import.compras.preview', compact('preview'));
    }

    public function comprasImport()
    {
        $rows = collect(session('rows_compras'));
        $map = session('map_compras');
        $inquilino = Auth::user()->inquilino_id;

        $importados = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            Compra::create($data + ['inquilino_id' => $inquilino]);
            $importados++;
        }

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', ['importados' => $importados]);
    }

    // ============================
    // VENTAS
    // ============================
    public function ventasForm()
    {
        return view('import.ventas.form');
    }

    public function ventasUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            $headers = $this->importService->obtenerEncabezados($rows);

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para ventas
            $camposSistema = [
                'cliente' => 'Cliente',
                'fecha' => 'Fecha',
                'descripcion' => 'Descripción',
                'monto_total' => 'Monto Total',
            ];

            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            session([
                'rows_ventas' => $rows,
                'headers_ventas' => $headers,
            ]);

            return view('import.ventas.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en ventasUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function ventasPreview(Request $request)
    {
        $map = $request->map;
        $rows = collect(session('rows_ventas'));

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_ventas' => $map]);

        return view('import.ventas.preview', compact('preview'));
    }

    public function ventasImport()
    {
        $rows = collect(session('rows_ventas'));
        $map = session('map_ventas');
        $inquilino = Auth::user()->inquilino_id;

        $importados = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            Venta::create($data + ['inquilino_id' => $inquilino]);
            $importados++;
        }

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', ['importados' => $importados]);
    }

    // ============================
    // MÉTODO AUXILIAR PARA SUGERIR MAPEO
    // ============================
    /**
     * Sugiere automáticamente el mapeo de columnas basado en similitud de nombres.
     */
    private function sugerirMapeo(array $headers, array $camposEsperados): array
    {
        $sugerencias = [];

        foreach ($camposEsperados as $campo) {
            // Buscar coincidencia exacta (case-insensitive)
            $match = array_filter($headers, function ($h) use ($campo) {
                return strtolower(trim($h)) === strtolower(trim($campo));
            });

            if (! empty($match)) {
                $sugerencias[$campo] = reset($match);

                continue;
            }

            // Buscar coincidencia parcial
            foreach ($headers as $header) {
                if (stripos($header, $campo) !== false || stripos($campo, $header) !== false) {
                    $sugerencias[$campo] = $header;
                    break;
                }
            }
        }

        return $sugerencias;
    }
}
