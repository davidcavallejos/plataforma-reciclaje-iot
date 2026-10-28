<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IngredienteBase;
use App\Enums\TipoProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulación de jabón.
 *
 * `calculos` guarda el resultado del cálculo proporcional para que la receta
 * sea reproducible aunque cambien las fórmulas de la aplicación:
 *   {"aceite": 1000, "agua": 380, "sosa": 140}
 */
class Receta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre',
        'tipo_producto',
        'ingrediente_base',
        'peso',
        'tipo_aceite',
        'calculos',
        'aditivos',
    ];

    protected function casts(): array
    {
        return [
            'tipo_producto' => TipoProducto::class,
            'ingrediente_base' => IngredienteBase::class,
            'peso' => 'decimal:2',
            'calculos' => 'array',
            'aditivos' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function scopeDeIngredienteBase(Builder $query, IngredienteBase $ingrediente): Builder
    {
        return $query->where('ingrediente_base', $ingrediente);
    }

    /**
     * Peso calculado de un ingrediente dentro de esta receta.
     */
    public function pesoDe(string $ingrediente): ?float
    {
        $valor = $this->calculos[$ingrediente] ?? null;

        return $valor === null ? null : (float) $valor;
    }
}
