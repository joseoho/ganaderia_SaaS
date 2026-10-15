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
            // La libreria no tipa $import en el codigo (solo en su docblock) y lo
            // comprueba con instanceof, asi que null es valido: significa "sin
            // clase de importacion", que es justo lo que se quiere para leer las
            // filas en crudo.
            // @phpstan-ignore argument.type (el docblock de la libreria es mas estricto que su implementacion)
            $coleccion = Excel::toCollection(null, $file);
        } catch (\Throwable $e) {
            // Un error de validacion no se reescribe: se propaga tal cual para
            // que el usuario vea el motivo real.
            if ($e instanceof ValidationException) {
                throw $e;
            }

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

        if ($primeraFila instanceof \Illuminate\Support\Collection) {
            $claves = $primeraFila->keys();

            // Laravel Excel devuelve las filas con claves NUMERICAS cuando el
            // archivo no declara fila de encabezado. En ese caso los nombres de
            // columna son los VALORES de la primera fila; devolver las claves
            // hacia que la pantalla de mapeo ofreciera "0", "1", "2"... y que
            // las sugerencias automaticas no coincidieran nunca.
            $numericas = $claves->every(fn ($clave) => is_int($clave) || ctype_digit((string) $clave));

            $valores = $numericas ? $primeraFila->values() : $claves;

            return $valores->map(function ($valor) {
                return trim((string) $valor, " \t\n\r\0\x0B\"'");
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
     * Limpia filas vacías y nombra las columnas con los encabezados.
     *
     * La primera fila son los encabezados: se usa para poner nombre a cada
     * columna y NO se devuelve como dato.
     *
     * Antes hacia justo lo contrario: filtraba las filas vacias y volvia a
     * insertar la fila de encabezados al principio. Con eso la importacion
     * creaba un registro de mas, con los propios nombres de columna como
     * contenido (un animal llamado "codigo_interno"), y las filas quedaban con
     * claves numericas, asi que el mapeo del usuario apuntaba a "0", "1", "2"...
     * en lugar de a los nombres de columna.
     *
     * @return Collection<int, Collection<string, mixed>>
     */
    public function limpiarFilas(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $nombres = collect($rows->first())
            ->map(fn ($valor) => trim((string) $valor, " \t\n\r\0\x0B\"'"))
            ->values()
            ->all();

        $filas = collect();

        foreach ($rows->slice(1) as $fila) {
            $valores = collect($fila)->values()->all();

            // Se descartan las filas vacias.
            if (collect($valores)->filter(fn ($valor) => $valor !== null && $valor !== '')->isEmpty()) {
                continue;
            }

            // Un CSV puede traer filas con mas celdas que encabezados: se
            // nombran las sobrantes para no perder el dato.
            $nombresFila = $nombres;
            for ($i = count($nombresFila); $i < count($valores); $i++) {
                $nombresFila[] = 'columna_'.$i;
            }

            $filas->push(collect(array_combine(
                $nombresFila,
                array_pad($valores, count($nombresFila), null)
            )));
        }

        return $filas->values();
    }
}
