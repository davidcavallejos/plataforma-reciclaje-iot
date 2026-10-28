<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoLote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lote extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'user_id',
        'receta_id',
        'perfil_id',
        'configuracion_aplicada',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'peso_producido',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'estado' => EstadoLote::class,
            'configuracion_aplicada' => 'array',
            'peso_producido' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    public function pedidos(): BelongsToMany
    {
        return $this->belongsToMany(Pedido::class, 'pedido_lote')
            ->withTimestamps();
    }

    public function scopeEnProceso(Builder $query): Builder
    {
        return $query->where('estado', EstadoLote::EnProceso);
    }

    public function scopeFinalizados(Builder $query): Builder
    {
        return $query->whereIn('estado', [EstadoLote::Completado, EstadoLote::Cancelado]);
    }

    /**
     * Configuración de máquina que se usó realmente en este lote.
     * Si no se congeló al iniciar, se toma la del perfil asociado.
     *
     * @return array<string, mixed>
     */
    public function configuracion(): array
    {
        if (! empty($this->configuracion_aplicada)) {
            return $this->configuracion_aplicada;
        }

        return $this->perfil?->configuracionNormalizada() ?? [
            'pwm' => Perfil::PWM_POR_DEFECTO,
            'automatizacion_ir' => [],
            'intervalos_aceite' => [],
        ];
    }

    public function duracionEnSegundos(): ?int
    {
        if ($this->fecha_fin === null) {
            return null;
        }

        return (int) $this->fecha_inicio->diffInSeconds($this->fecha_fin);
    }
}
