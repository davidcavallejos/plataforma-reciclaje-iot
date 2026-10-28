<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Perfil de funcionamiento de la máquina.
 *
 * `configuraciones_json` sigue este contrato:
 * {
 *   "pwm": {"filter1": 250, "water": 200, "oil": 250, "filter2": 250,
 *            "pump4": 250, "l298n1": 150, "motor3": 150},
 *   "pasos": [
 *     {"componente": "WATER", "tiempo_min": 2, "pwm": 200},
 *     {"componente": "OIL", "tiempo_min": 4, "pwm": 250,
 *      "tiempo_on_s": 5, "tiempo_off_s": 10, "repeticiones": 6}
 *   ]
 * }
 */
class Perfil extends Model
{
    use HasFactory;

    /** @var array<string, string> */
    public const MAPA_PWM = [
        'FILTER1' => 'filter1',
        'WATER' => 'water',
        'OIL' => 'oil',
        'FILTER2' => 'filter2',
        'PUMP4' => 'pump4',
        'MOTORES' => 'l298n1',
        'MOTOR3' => 'motor3',
    ];

    /**
     * PWM por defecto del sistema (0..255).
     * El firmware arranca con otros valores, por eso el panel siempre debe
     * enviar el PWM explícito en lugar de confiar en los del ESP32.
     *
     * @var array<string, int>
     */
    public const PWM_POR_DEFECTO = [
        'filter1' => 250,
        'water' => 200,
        'oil' => 250,
        'filter2' => 250,
        'pump4' => 250,
        'l298n1' => 150,
        'motor3' => 150,
    ];

    protected $table = 'perfiles';

    protected $fillable = [
        'user_id',
        'nombre',
        'configuraciones_json',
        'es_predeterminado',
    ];

    protected function casts(): array
    {
        return [
            'configuraciones_json' => 'array',
            'es_predeterminado' => 'boolean',
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

    public function scopePredeterminados(Builder $query): Builder
    {
        return $query->where('es_predeterminado', true);
    }

    /**
     * PWM efectivo: lo guardado en el perfil, completado con los valores por
     * defecto del sistema para cualquier componente que falte.
     *
     * @return array<string, int>
     */
    public function pwm(): array
    {
        return array_merge(
            self::PWM_POR_DEFECTO,
            (array) data_get($this->configuraciones_json, 'pwm', [])
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function automatizacionIr(): array
    {
        return (array) data_get($this->configuraciones_json, 'automatizacion_ir', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function intervalosAceite(): array
    {
        return (array) data_get($this->configuraciones_json, 'intervalos_aceite', []);
    }

    /**
     * Secuencia de componentes en el orden de ejecución.
     *
     * @return list<array<string, mixed>>
     */
    public function pasos(): array
    {
        $pasos = data_get($this->configuraciones_json, 'pasos');

        if (is_array($pasos) && $pasos !== []) {
            return array_values($pasos);
        }

        $pwm = $this->pwm();
        $aceite = $this->intervalosAceite();
        $resultado = [];

        foreach (self::MAPA_PWM as $componente => $clave) {
            $paso = [
                'componente' => $componente,
                'tiempo_min' => 1,
                'pwm' => (int) ($pwm[$clave] ?? self::PWM_POR_DEFECTO[$clave]),
            ];

            if ($componente === 'OIL') {
                $paso['tiempo_on_s'] = (int) ($aceite['tiempo_on_s'] ?? 5);
                $paso['tiempo_off_s'] = (int) ($aceite['tiempo_off_s'] ?? 10);
                $paso['repeticiones'] = (int) ($aceite['repeticiones'] ?? 6);
            }

            $resultado[] = $paso;
        }

        return $resultado;
    }

    /**
     * Configuración completa y normalizada, lista para enviar al ESP32 o
     * congelar dentro de un lote.
     *
     * @return array<string, mixed>
     */
    public function configuracionNormalizada(): array
    {
        return [
            'pwm' => $this->pwm(),
            'pasos' => $this->pasos(),
            'automatizacion_ir' => $this->automatizacionIr(),
            'intervalos_aceite' => $this->intervalosAceite(),
        ];
    }
}
