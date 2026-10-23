<?php

namespace App\Support;

use App\Enums\Permiso;
use App\Enums\Rol;

/**
 * La matriz de permisos: UNA tabla, en un solo sitio, que se lee de un vistazo.
 *
 * Todo el control de acceso de la aplicacion sale de aqui. No hay condiciones
 * repartidas por controladores ni vistas comparando nombres de rol: si manana
 * hay que abrir o cerrar algo, se cambia en esta funcion y se ejecutan las
 * pruebas.
 *
 * Regla de oro: lo que no este concedido, esta negado. Un rol nuevo que se
 * olvide añadir aqui no hereda nada.
 */
final class MatrizDePermisos
{
    /**
     * @return array<int, Permiso>
     */
    public static function permisosDe(Rol $rol): array
    {
        return match ($rol) {
            // El administrador es el dueno de la ganaderia: puede con todo lo de
            // SU finca. Los permisos de plataforma quedan fuera a proposito: los
            // pagos de las demas ganaderias no son asunto suyo.
            Rol::Administrador => array_values(array_filter(
                Permiso::cases(),
                fn (Permiso $permiso): bool => ! $permiso->esDePlataforma()
            )),

            // El usuario hace el trabajo del dia a dia con los animales:
            // registra produccion y sanidad, y consulta informes. No ve dinero,
            // no borra, no importa y no toca a la gente.
            Rol::Usuario => [Permiso::ReportesVer],

            // El operador de la plataforma cobra: ve los comprobantes de todas
            // las fincas y los aprueba o rechaza. Nada mas.
            Rol::Operador => [Permiso::PlataformaVerPagos, Permiso::PlataformaGestionarPagos],
        };
    }

    public static function concede(Rol $rol, Permiso $permiso): bool
    {
        return in_array($permiso, self::permisosDe($rol), true);
    }
}
