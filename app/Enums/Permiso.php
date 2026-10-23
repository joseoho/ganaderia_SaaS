<?php

namespace App\Enums;

/**
 * Las acciones que se pueden permitir o negar.
 *
 * Se nombran "dominio.accion" y NO "pantalla": un permiso tiene que sobrevivir a
 * un rediseño del menu. `dinero.ver` sigue significando lo mismo aunque manana
 * la pantalla se llame de otra forma.
 *
 * Fase 1 (esta entrega) son las criticas: dinero, borrados, importacion y
 * gestion de usuarios. Las de la fase 2 (lectura/escritura por modulo) se
 * anaden aqui cuando se vayan a usar de verdad; declarar permisos que nadie
 * comprueba es peor que no tenerlos, porque da una falsa sensacion de control.
 */
enum Permiso: string
{
    /** Ver y registrar compras, ventas, gastos y el balance: es dinero. */
    case DineroVer = 'dinero.ver';

    /** Borrar cualquier registro (animales, produccion, ventas...). */
    case RegistrosEliminar = 'registros.eliminar';

    /** Importar archivos CSV/Excel (escritura masiva: puede pisar datos). */
    case DatosImportar = 'datos.importar';

    /** Crear usuarios, cambiarles el rol y desactivarlos. */
    case UsuariosGestionar = 'usuarios.gestionar';

    /** Ver los informes de produccion, potreros y animales. */
    case ReportesVer = 'reportes.ver';

    // --- Plataforma (el operador que cobra), fuera de las ganaderias ---

    /** Ver los comprobantes de pago de todas las fincas. */
    case PlataformaVerPagos = 'plataforma.ver_pagos';

    /** Aprobar o rechazar un pago: es lo que da luz verde al servicio. */
    case PlataformaGestionarPagos = 'plataforma.gestionar_pagos';

    /** Editar planes y precios. */
    case PlataformaEditarPlanes = 'plataforma.editar_planes';

    /**
     * Los permisos de plataforma no son de una finca: un administrador de
     * ganaderia no debe ver los pagos de las demas.
     */
    public function esDePlataforma(): bool
    {
        return match ($this) {
            self::PlataformaVerPagos, self::PlataformaGestionarPagos, self::PlataformaEditarPlanes => true,
            default => false,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::DineroVer => 'Ver y registrar dinero',
            self::RegistrosEliminar => 'Eliminar registros',
            self::DatosImportar => 'Importar archivos',
            self::UsuariosGestionar => 'Gestionar usuarios',
            self::ReportesVer => 'Ver reportes',
            self::PlataformaVerPagos => 'Ver pagos de las fincas',
            self::PlataformaGestionarPagos => 'Aprobar o rechazar pagos',
            self::PlataformaEditarPlanes => 'Editar planes y precios',
        };
    }
}
