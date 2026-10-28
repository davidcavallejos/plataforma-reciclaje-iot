<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoPedido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente',
        'cantidad_peso',
        'cantidad_unidades',
        'estado',
        'fecha_entrega',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoPedido::class,
            'fecha_entrega' => 'date',
            'cantidad_peso' => 'decimal:2',
            'cantidad_unidades' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lotes(): BelongsToMany
    {
        return $this->belongsToMany(Lote::class, 'pedido_lote')
            ->withTimestamps();
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', EstadoPedido::Pendiente);
    }

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereIn('estado', [EstadoPedido::Pendiente, EstadoPedido::EnProceso]);
    }

    public function scopeParaEntregarAntesDe(Builder $query, \DateTimeInterface $fecha): Builder
    {
        return $query->whereDate('fecha_entrega', '<=', $fecha);
    }

    /**
     * Texto listo para mostrar en las tarjetas y listados.
     */
    public function descripcionCantidad(): string
    {
        $partes = [];

        if ($this->cantidad_peso !== null) {
            $partes[] = number_format((float) $this->cantidad_peso, 2).' kg';
        }

        if ($this->cantidad_unidades !== null) {
            $partes[] = $this->cantidad_unidades.' '.
                ($this->cantidad_unidades === 1 ? 'unidad' : 'unidades');
        }

        return implode(' · ', $partes);
    }
}
