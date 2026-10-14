<?php

namespace Tests\Concerns;

/**
 * Extrae del HTML los campos que enviaria un formulario.
 *
 * Se usa para reenviar un formulario TAL CUAL se renderiza, en lugar de
 * escribir el payload a mano: asi se detectan solos los desajustes entre lo que
 * el formulario envia y lo que valida o acepta el servidor.
 */
trait ExtraeCamposDeFormulario
{
    /**
     * @return array<string, string>
     */
    protected function camposDelFormulario(string $html): array
    {
        $campos = [];

        // <input ...>
        preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
        foreach ($inputs[0] as $tag) {
            if (! preg_match('/name="([^"]+)"/i', $tag, $m)) {
                continue;
            }
            $nombre = $m[1];
            if (in_array($nombre, ['_token', '_method'], true)) {
                continue;
            }
            $tipo = preg_match('/type="([^"]+)"/i', $tag, $t) ? strtolower($t[1]) : 'text';
            if (in_array($tipo, ['submit', 'button', 'file', 'reset'], true)) {
                continue;
            }
            if ($tipo === 'checkbox' && ! str_contains($tag, 'checked')) {
                continue;
            }
            $campos[$nombre] = preg_match('/value="([^"]*)"/i', $tag, $v) ? $v[1] : '';
        }

        // <select ...> con la opcion marcada
        preg_match_all('/<select\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER);
        foreach ($selects as $select) {
            if (preg_match('/<option[^>]*value="([^"]*)"[^>]*selected/i', $select[2], $o)) {
                $campos[$select[1]] = $o[1];
            }
        }

        // <textarea ...>
        preg_match_all('/<textarea\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER);
        foreach ($areas as $area) {
            $campos[$area[1]] = trim($area[2]);
        }

        return $campos;
    }
}
