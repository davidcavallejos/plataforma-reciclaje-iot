<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoLote: string
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Completado = 'completado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En proceso',
            self::Completado => 'Completado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function estaFinalizado(): bool
    {
        return in_array($this, [self::Completado, self::Cancelado], true);
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $estado) => $carry + [$estado->value => $estado->etiqueta()],
            []
        );
    }
}
