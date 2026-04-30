<?php

namespace App\Traits;

trait TieneValidacionesInquilino
{
    public function existsInquilino(string $table, string $column = 'id'): string
    {
        $inquilinoId = auth()->user()->inquilino_id ?? null;
        if (!$inquilinoId) {
            return "exists:{$table},{$column}";
        }
        return "exists:{$table},{$column},inquilino_id,{$inquilinoId}";
    }

    public function uniqueInquilino(string $table, string $column = 'NULL', ?int $ignoreId = null): string
    {
        $inquilinoId = auth()->user()->inquilino_id ?? null;
        $rule = "unique:{$table},{$column}";
        if ($ignoreId) {
            $rule .= ",{$ignoreId}";
        }
        if ($inquilinoId) {
            $rule .= ",NULL,id,inquilino_id,{$inquilinoId}";
        }
        return $rule;
    }
}