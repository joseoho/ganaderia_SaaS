<?php

namespace App\Enums;

/**
 * Los dos roles de una ganaderia.
 *
 * Son solo dos a proposito: un sistema de permisos por accion para una finca
 * pequena es burocracia. Lo que cambia entre ellos es CUANTO dinero manejan:
 * el administrador es el dueno (ve y registra compras, ventas y gastos, y
 * gestiona a la gente) y el usuario es quien trabaja con los animales.
 *
 * La regla que hay que respetar al tocar esto: un rol desconocido NUNCA es
 * administrador. Se cae en el rol de menos privilegio, porque un error de
 * dedo en la base no puede dar permiso para borrar.
 */
enum Rol: string
{
    case Administrador = 'administrador';
    case Usuario = 'usuario';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Usuario => 'Usuario',
        };
    }

    /**
     * @return array<string, string> valor de la base => etiqueta que se muestra
     */
    public static function etiquetas(): array
    {
        $etiquetas = [];

        foreach (self::cases() as $rol) {
            $etiquetas[$rol->value] = $rol->etiqueta();
        }

        return $etiquetas;
    }

    /**
     * Traduce lo que venga de la base o de un formulario.
     *
     * Si el valor no existe (una fila vieja, un POST manipulado), devuelve el
     * rol de menos privilegio en vez de reventar o, peor, dar administrador.
     */
    public static function desde(?string $valor): self
    {
        return self::tryFrom(trim((string) $valor)) ?? self::Usuario;
    }
}
