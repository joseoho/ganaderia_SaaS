<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AgenteImportacionService
{
    // //     public function sugerirMapeo(array $headers): array
    // //     {
    // //         // ============================================
    // //     // 1. Sugerir mapeo automático
    // //     // ============================================

    // //         $sugerencias = [];

    // //         $patrones = [
    // //             'codigo_interno'   => ['codigo', 'cod', 'id', 'identificacion', 'arete'],
    // //             'raza'             => ['raza', 'breed'],
    // //             'sexo'             => ['sexo', 'gender', 'sex'],
    // //             'fecha_nacimiento' => ['fecha_nac', 'nacimiento', 'birth', 'fnac'],
    // //             'peso_actual'      => ['peso', 'weight', 'kg', 'kgs'],
    // //             'estado'           => ['estado', 'status'],
    // //         ];

    // //         foreach ($patrones as $campo => $posibles) {
    // //             foreach ($headers as $h) {
    // //                 $hLower = strtolower($h);
    // //                 foreach ($posibles as $p) {
    // //                     if (str_contains($hLower, $p)) {
    // //                         $sugerencias[$campo] = $h;
    // //                         break 2;
    // //                     }
    // //                 }
    // //             }
    // //         }

    // //         return $sugerencias;
    // //     }

    // //     // ============================================
    // //     // 2. Limpieza automática de datos
    // //     // ============================================
    // //     public function limpiarFila(array $row): array
    // //     {
    // //         foreach ($row as $key => $value) {

    // //             // Limpiar espacios
    // //             if (is_string($value)) {
    // //                 $value = trim($value);
    // //             }

    // //             // Limpiar peso (ej: "120kg" → 120)
    // //             if (is_string($value) && preg_match('/^\d+(\.\d+)?\s*kg$/i', $value)) {
    // //                 $value = floatval($value);
    // //             }

    // //             // Normalizar fechas
    // //             if ($this->esFecha($value)) {
    // //                 $value = date('Y-m-d', strtotime($value));
    // //             }

    // //             $row[$key] = $value;
    // //         }

    // //         return $row;
    // //     }

    // //     // ============================================
    // //     // 3. Detectar si un valor es fecha
    // //     // ============================================
    // //     private function esFecha($value): bool
    // //     {
    // //         if (!$value) return false;
    // //         return strtotime($value) !== false;
    // //     }

    // //     // ============================================
    // //     // 4. Detectar problemas en el archivo
    // //     // ============================================
    // //     public function detectarProblemas(Collection $rows, array $map): array
    // //     {
    // //         $problemas = [
    // //             'fechas_invalidas' => 0,
    // //             'pesos_invalidos'  => 0,
    // //             'codigos_vacios'   => 0,
    // //             'duplicados'       => 0,
    // //         ];

    // //         $codigos = [];

    // //         foreach ($rows as $row) {

    // //             // Código vacío
    // //             if (empty($row[$map['codigo_interno']] ?? null)) {
    // // $problemas['codigos_vacios']++;
    // //             }

    // //             // Duplicados
    // //             $codigo = $row[$map['codigo_interno']] ?? null;
    // //             if ($codigo) {
    // //                 if (in_array($codigo, $codigos)) {
    // //                     $problemas['duplicados']++;
    // //                 }
    // //                 $codigos[] = $codigo;
    // //             }

    // //             // Peso inválido
    // //             if (isset($map['peso_actual'])) {
    // //                 $peso = $row[$map['peso_actual']] ?? null;
    // //                 if ($peso && !is_numeric($peso)) {
    // //                     $problemas['pesos_invalidos']++;
    // //                 }
    // //             }

    // //             // Fecha inválida
    // //             if (isset($map['fecha_nacimiento'])) {
    // //                 $fecha = $row[$map['fecha_nacimiento']] ?? null;
    // //                 if ($fecha && !$this->esFecha($fecha)) {
    // //                     $problemas['fechas_invalidas']++;
    // //                 }
    // //             }
    // //         }

    // //         return $problemas;
    // //     }

    // //     // ============================================
    // //     // 5. Correcciones automáticas
    // //     // ============================================
    // //     public function corregirAutomaticamente(array $row, array $map): array
    // //     {
    // //         // Peso → si no es numérico, poner 0
    // //         if (isset($map['peso_actual'])) {
    // //             $peso = $row[$map['peso_actual']] ?? null;
    // //             if ($peso && !is_numeric($peso)) {
    // //                 $row[$map['peso_actual']] = 0;
    // //             }
    // //         }

    // //         // Estado → si está vacío, poner "activo"
    // //         if (isset($map['estado'])) {
    // //             if (empty($row[$map['estado']])) {
    // //                 $row[$map['estado']] = 'activo';
    // //             }
    // //         }

    // //         return $row;
    // //     }

    // //     // ============================================
    // //     // 6. Mensajes inteligentes
    // //     // ============================================
    // //     public function generarMensajes(array $problemas, array $sugerencias): array
    // //     {
    // //         $mensajes = [];

    // //         if (!empty($sugerencias)) {
    // //             $mensajes[] = "Detecté automáticamente columnas que coinciden con los campos del sistema. Ya preseleccioné algunas para ayudarte.";
    // //         }

    // //         if ($problemas['duplicados'] > 0) {
    // //             $mensajes[] = "Hay {$problemas['duplicados']} códigos duplicados. Se actualizarán los animales existentes en lugar de crear nuevos.";
    // //         }

    // //         if ($problemas['fechas_invalidas'] > 0) {
    // //             $mensajes[] = "Hay {$problemas['fechas_invalidas']} fechas inválidas. Serán ignoradas o corregidas automáticamente.";
    // //         }

    // //         if ($problemas['pesos_invalidos'] > 0) {
    // //             $mensajes[] = "Hay {$problemas['pesos_invalidos']} pesos no numéricos. Serán convertidos a 0.";
    // //         }

    // //         if ($problemas['codigos_vacios'] > 0) {
    // //             $mensajes[] = "Hay {$problemas['codigos_vacios']} animales sin código. No podrán importarse.";
    // //         }

    // //         if (empty($mensajes)) {
    // //             $mensajes[] = "Todo se ve perfecto. Tu archivo está listo para importar sin problemas.";
    // //         }

    // //         return $mensajes;
    //     }
}
