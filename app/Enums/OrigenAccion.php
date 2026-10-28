<?php

declare(strict_types=1);

namespace App\Enums;

enum OrigenAccion: string
{
    case Web = 'web';
    case Esp32 = 'esp32';
    case Automatizacion = 'automatizacion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Web => 'Panel web',
            self::Esp32 => 'ESP32',
            self::Automatizacion => 'Automatización IR',
        };
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $origen) => $carry + [$origen->value => $origen->etiqueta()],
            []
        );
    }
}
