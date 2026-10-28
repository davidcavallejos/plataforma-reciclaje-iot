<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preguntas y respuestas del chatbot del proyecto.
 */
class ChatbotHistorial extends Model
{
    use HasFactory;

    protected $table = 'chatbot_historial';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pregunta',
        'respuesta',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecientes(Builder $query, int $cantidad = 20): Builder
    {
        return $query->orderByDesc('fecha_hora')->limit($cantidad);
    }
}
