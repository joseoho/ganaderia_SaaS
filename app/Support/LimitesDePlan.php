<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\Inquilino;
use App\Models\User;

/**
 * Topes del plan contratado: cuántos animales y cuántos usuarios caben.
 *
 * Un limite nulo significa "sin limite" (planes grandes). Se comprueba ANTES de
 * crear, para no dejar el dato a medias, y con un mensaje que diga qué pasa y
 * qué hacer (subir de plan), no un "no permitido" seco.
 */
final class LimitesDePlan
{
    public static function animalesUsados(Inquilino $inquilino): int
    {
        return Animal::query()->where('inquilino_id', $inquilino->id)->count();
    }

    public static function usuariosUsados(Inquilino $inquilino): int
    {
        return User::query()->where('inquilino_id', $inquilino->id)->count();
    }

    /** Cuántos animales más caben; null si el plan no tiene tope. */
    public static function animalesDisponibles(Inquilino $inquilino): ?int
    {
        $limite = $inquilino->planActual()?->limite_animales;

        return $limite === null ? null : max(0, $limite - self::animalesUsados($inquilino));
    }

    /** Cuántos usuarios más caben; null si el plan no tiene tope. */
    public static function usuariosDisponibles(Inquilino $inquilino): ?int
    {
        $limite = $inquilino->planActual()?->limite_usuarios;

        return $limite === null ? null : max(0, $limite - self::usuariosUsados($inquilino));
    }

    public static function puedeCrearAnimales(Inquilino $inquilino, int $cuantos = 1): bool
    {
        $disponibles = self::animalesDisponibles($inquilino);

        return $disponibles === null || $disponibles >= $cuantos;
    }

    public static function puedeCrearUsuarios(Inquilino $inquilino, int $cuantos = 1): bool
    {
        $disponibles = self::usuariosDisponibles($inquilino);

        return $disponibles === null || $disponibles >= $cuantos;
    }

    public static function mensajeAnimales(Inquilino $inquilino): string
    {
        $plan = $inquilino->planActual();
        $limite = $plan?->limite_animales;

        return sprintf(
            'Tu plan %s admite %s animales y ya los tienes todos. Para añadir más, cambia de plan o borra animales vendidos.',
            $plan->nombre ?? 'actual',
            $limite ?? 'sin límite'
        );
    }

    public static function mensajeUsuarios(Inquilino $inquilino): string
    {
        $plan = $inquilino->planActual();
        $limite = $plan?->limite_usuarios;

        return sprintf(
            'Tu plan %s admite %s usuarios. Para dar de alta a otra persona, cambia de plan o desactiva una cuenta que ya no se use.',
            $plan->nombre ?? 'actual',
            $limite ?? 'sin límite'
        );
    }

    /** Texto corto para el panel: "12 de 60 animales". */
    public static function resumen(Inquilino $inquilino): string
    {
        $plan = $inquilino->planActual();

        if ($plan === null) {
            return 'Sin plan activo';
        }

        return sprintf(
            '%d de %s animales · %d de %s usuarios',
            self::animalesUsados($inquilino),
            $plan->limite_animales ?? '∞',
            self::usuariosUsados($inquilino),
            $plan->limite_usuarios ?? '∞'
        );
    }
}
