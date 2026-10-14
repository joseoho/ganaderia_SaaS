<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportService
{
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

            // La hoja 0 es la primera pestaña
            if ($coleccion->isEmpty() || ! isset($coleccion[0])) {
                return collect([]);
            }

            return $coleccion[0];

        } catch (\Exception $e) {
            Log::error('Error ImportService@leerArchivo: '.$e->getMessage());

            return collect([]);
        }
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
