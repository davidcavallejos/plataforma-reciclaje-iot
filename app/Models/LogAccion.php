<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrigenAccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoría de comandos enviados a la máquina.
 *
 * La tabla no tiene created_at/updated_at: la fecha del evento es `fecha_hora`.
 */
class LogAccion extends Model
{
    use HasFactory;

    protected $table = 'logs_acciones';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'accion',
        'componente',
        'origen',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'origen' => OrigenAccion::class,
            'fecha_hora' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDeComponente(Builder $query, string $componente): Builder
    {
        return $query->where('componente', $componente);
    }

    public function scopeEntreFechas(Builder $query, \DateTimeInterface $desde, \DateTimeInterface $hasta): Builder
    {
        return $query->whereBetween('fecha_hora', [$desde, $hasta]);
    }
}
