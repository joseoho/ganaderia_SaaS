<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ImportService
{
    /**
     * Tope de filas por importacion.
     *
     * Protege las escrituras a base de datos y el guardado en sesion. No
     * protege la memoria durante el parseo: eso lo acota el limite de 10 MB
     * del archivo, validado en el controlador.
     */
    public const MAX_FILAS = 5000;

    /**
     * Lee un archivo (CSV, XLSX, XLS) y devuelve un Collection de filas.
     */
    public function leerArchivo($file): Collection
    {
        // Si no hay archivo, devolver colección vacía
        if (! $file || ! $file->isValid()) {
            return collect([]);
        }

        try {
            $coleccion = Excel::toCollection(null, $file);
        } catch (ValidationException $e) {
            // Un error de validacion nunca debe silenciarse.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error ImportService@leerArchivo: '.$e->getMessage());

            // Antes se devolvia una coleccion vacia y el usuario solo veia
            // "el archivo esta vacio o no se pudo leer", sin saber por que.
            throw ValidationException::withMessages([
                'archivo' => 'No se pudo leer el archivo. Comprueba que es un CSV o Excel válido y que no está dañado.',
            ]);
        }

        // La hoja 0 es la primera pestaña
        if ($coleccion->isEmpty() || ! isset($coleccion[0])) {
            return collect([]);
        }

        $filas = $coleccion[0];

        if ($filas->count() > self::MAX_FILAS) {
            throw ValidationException::withMessages([
                'archivo' => sprintf(
                    'El archivo tiene %d filas y el máximo por importación es %d. Divídelo en varios archivos.',
                    $filas->count(),
                    self::MAX_FILAS
                ),
            ]);
        }

        return $filas;
    }

    /**
     * Obtiene los encabezados (primera fila) como array simple.
     */
    public function obtenerEncabezados(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $primeraFila = $rows->first();

        // Si es un array asociativo (tipo Collection con keys)
        if ($primeraFila instanceof \Illuminate\Support\Collection) {
            // Quitar espacios y comillas a los nombres de columna
            return $primeraFila->keys()->map(function ($key) {
                return trim($key, " \t\n\r\0\x0B\"'");
            })->toArray();
        }

        // Si es un array simple
        if (is_array($primeraFila)) {
            return array_map(function ($item) {
                return trim((string) $item, " \t\n\r\0\x0B\"'");
            }, $primeraFila);
        }

        return [];
    }

    /**
     * Limpia filas vacías y normaliza los datos.
     */
    public function limpiarFilas(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        // Saltar la primera fila (encabezados) para limpiar solo los datos
        $headers = $rows->first();
        $dataRows = $rows->slice(1);

        // Filtrar filas vacías (todas las celdas null o vacías)
        $dataRows = $dataRows->filter(function ($row) {
            if ($row instanceof \Illuminate\Support\Collection) {
                return $row->filter(function ($value) {
                    return $value !== null && $value !== '';
                })->isNotEmpty();
            }

            return ! empty(array_filter((array) $row));
        });

        // Volver a unir encabezados + datos limpios
        return $dataRows->prepend($headers)->values();
    }
}
