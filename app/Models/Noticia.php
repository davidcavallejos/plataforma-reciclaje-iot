<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Noticia extends Model
{
    protected $table = 'noticias';

    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'imagen_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imagenUrl(): string
    {
        return Storage::disk('public')->url($this->imagen_path);
    }
}
