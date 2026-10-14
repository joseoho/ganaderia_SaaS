<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Formateo de valores para formularios.
 *
 * Motivo: un `<input type="date">` cuyo `value` no es exactamente `Y-m-d`
 * (por ejemplo "2026-01-01 00:00:00") se muestra VACIO en el navegador. Eso
 * ocurria con las columnas de tipo timestamp y podia ocurrir con cualquier
 * fecha en cuanto el modelo declara un cast.
 *
 * Estas funciones aceptan tanto cadena como DateTimeInterface, de modo que la
 * vista funciona igual antes y despues de declarar los casts en el modelo.
 */
class Formato
{
    /**
     * Fecha para un <input type="date"> (Y-m-d).
     */
    public static function fecha(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        try {
            return Carbon::parse($valor)->format('Y-m-d');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Fecha y hora para <input type="datetime-local"> (Y-m-d\TH:i).
     */
    public static function fechaHora(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        try {
            $fecha = $valor instanceof DateTimeInterface ? $valor : Carbon::parse($valor);
        } catch (Throwable) {
            return '';
        }

        return $fecha->format('Y-m-d\TH:i');
    }
}
