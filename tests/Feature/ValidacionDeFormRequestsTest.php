<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Un FormRequest usado con `$request->validated()` tiene que declarar reglas.
 *
 * Este fallo ya se dio una vez y era grave: StoreNotificacionRequest y
 * UpdateNotificacionRequest tenian `rules()` vacio, asi que `validated()`
 * devolvia `[]`. Crear una notificacion daba un error 500 (el INSERT salia sin
 * datos y la columna NOT NULL reventaba) y editarla no guardaba NADA mientras
 * la interfaz decia "actualizada correctamente".
 *
 * Es un fallo silencioso y facil de reintroducir al crear un controlador nuevo:
 * esta prueba lo convierte en un error de la suite.
 */
class ValidacionDeFormRequestsTest extends TestCase
{
    public function test_los_form_requests_usados_con_validated_declaran_reglas(): void
    {
        $problemas = [];

        foreach (glob(app_path('Http/Controllers/*.php')) as $archivo) {
            $codigo = (string) file_get_contents($archivo);

            if (! str_contains($codigo, 'validated()')) {
                continue;
            }

            $controlador = basename($archivo, '.php');

            // Cada metodo publico con su firma y su cuerpo.
            preg_match_all(
                '/public function (\w+)\(([^)]*)\)(.*?)(?=\n    (?:public|protected|private) function |\n\})/s',
                $codigo,
                $metodos,
                PREG_SET_ORDER
            );

            foreach ($metodos as $metodo) {
                [, $nombre, $firma, $cuerpo] = $metodo;

                if (! str_contains($cuerpo, 'validated()')) {
                    continue;
                }

                if (! preg_match('/(\w+Request) \$/', $firma, $coincidencia)) {
                    $problemas[] = $controlador.'::'.$nombre.' usa validated() sin un FormRequest que lo respalde.';

                    continue;
                }

                $clase = 'App\\Http\\Requests\\'.$coincidencia[1];

                if (! class_exists($clase)) {
                    $problemas[] = $controlador.'::'.$nombre.' usa '.$coincidencia[1].', que no existe.';

                    continue;
                }

                if ((new $clase)->rules() === []) {
                    $problemas[] = $controlador.'::'.$nombre.' usa '.$coincidencia[1]
                        .' cuyas reglas estan VACIAS: validated() devolvera [] y el dato se perdera en silencio.';
                }
            }
        }

        $this->assertSame(
            [],
            $problemas,
            "FormRequests sin reglas usados con validated():\n- ".implode("\n- ", $problemas)
        );
    }
}
