<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoProducto: string
{
    case Liquido = 'liquido';
    case Barra = 'barra';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Liquido => 'Líquido',
            self::Barra => 'Barra',
        };
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $tipo) => $carry + [$tipo->value => $tipo->etiqueta()],
            []
        );
    }
}
