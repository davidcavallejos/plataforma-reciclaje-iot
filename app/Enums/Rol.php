<?php

declare(strict_types=1);

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Operador = 'operador';
    case Visualizador = 'visualizador';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Operador => 'Operador',
            self::Visualizador => 'Visualizador',
        };
    }

    /**
     * Administrador y operador manejan la máquina y ven reportes.
     * El visualizador solo accede a Inicio y Proyecto/Registros.
     */
    public function puedeControlarMaquina(): bool
    {
        return $this !== self::Visualizador;
    }

    public function puedeVerReportes(): bool
    {
        return $this !== self::Visualizador;
    }

    public function puedeGestionarUsuarios(): bool
    {
        return $this === self::Administrador;
    }

    public function puedeVerRegistros(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $rol) => $carry + [$rol->value => $rol->etiqueta()],
            []
        );
    }
}
