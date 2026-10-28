<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Informe PDF/Excel generado y descargado.
 */
class ReporteGenerado extends Model
{
    use HasFactory;

    protected $table = 'reportes_generados';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'tipo',
        'ruta_archivo',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
