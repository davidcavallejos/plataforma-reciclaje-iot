<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Serie temporal de temperatura leída del DS18B20.
 */
class HistorialTemperatura extends Model
{
    use HasFactory;

    protected $table = 'historial_temperaturas';

    public $timestamps = false;

    protected $fillable = [
        'temperatura',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'temperatura' => 'decimal:2',
            'fecha_hora' => 'datetime',
        ];
    }

    public function scopeEntreFechas(Builder $query, \DateTimeInterface $desde, \DateTimeInterface $hasta): Builder
    {
        return $query->whereBetween('fecha_hora', [$desde, $hasta]);
    }

    public function scopeUltimas(Builder $query, int $cantidad = 60): Builder
    {
        return $query->orderByDesc('fecha_hora')->limit($cantidad);
    }
}
