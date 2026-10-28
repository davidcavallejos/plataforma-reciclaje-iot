<?php

declare(strict_types=1);

namespace App\Enums;

enum IngredienteBase: string
{
    case Aceite = 'aceite';
    case Agua = 'agua';
    case Sosa = 'sosa';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aceite => 'Aceite',
            self::Agua => 'Agua',
            self::Sosa => 'Sosa',
        };
    }

    /**
     * Los tres ingredientes se calculan proporcionalmente entre sí.
     * El factor es la parte que corresponde a cada uno por unidad de base.
     */
    public function factor(): float
    {
        return match ($this) {
            self::Aceite => 1.0,
            self::Agua => 0.38,
            self::Sosa => 0.14,
        };
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $ing) => $carry + [$ing->value => $ing->etiqueta()],
            []
        );
    }
}
