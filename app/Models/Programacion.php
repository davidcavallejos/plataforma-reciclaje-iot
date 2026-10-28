<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temporizador (ejecutar_en) o programación diaria (hora) de un comando.
 */
class Programacion extends Model
{
    use HasFactory;

    protected $table = 'programaciones';

    protected $fillable = [
        'user_id',
        'componente',
        'comando',
        'ejecutar_en',
        'hora',
        'activa',
        'ultima_ejecucion',
    ];

    protected function casts(): array
    {
        return [
            'ejecutar_en' => 'datetime',
            'ultima_ejecucion' => 'datetime',
            'activa' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function esDiaria(): bool
    {
        return $this->hora !== null;
    }

    /**
     * ¿Debe ejecutarse en este instante?
     */
    public function estaVencida(): bool
    {
        if (! $this->activa) {
            return false;
        }

        $ahora = now();

        if ($this->ejecutar_en !== null) {
            return $this->ultima_ejecucion === null && $this->ejecutar_en->lte($ahora);
        }

        if ($this->hora !== null) {
            $hoy = $ahora->copy()->setTimeFromTimeString((string) $this->hora);

            // Todavía no llega la hora de hoy.
            if ($hoy->gt($ahora)) {
                return false;
            }

            // Si se creó después de la hora programada, su primera ejecución
            // corresponde al día siguiente (no debe dispararse al crearla).
            if ($this->created_at !== null && $this->created_at->gte($hoy)) {
                return false;
            }

            return $this->ultima_ejecucion === null || ! $this->ultima_ejecucion->isSameDay($ahora);
        }

        return false;
    }

    public function descripcion(): string
    {
        if ($this->esDiaria()) {
            return 'Todos los días a las '.substr((string) $this->hora, 0, 5);
        }

        return 'Una vez el '.$this->ejecutar_en?->format('d/m/Y H:i:s');
    }
}
