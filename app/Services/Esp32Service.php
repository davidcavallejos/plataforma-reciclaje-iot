<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Puente HTTP con el ESP32.
 *
 * El panel nunca habla directo con la placa desde el navegador: así las
 * credenciales del ESP32 no viajan al cliente y todo comando queda auditado.
 */
class Esp32Service
{
    /**
     * Comandos aceptados por el firmware. Se valida aquí para que un error de
     * programación no termine enviando texto arbitrario a la máquina.
     *
     * @var list<string>
     */
    private const PATRONES = [
        '/^(START|STOP|ALLSTOP|FSTOP|PSTOP|HOUR|STATUS|SENSORS|TEMP|HELP|IR1|IR2)$/',
        '/^V (?:25[0-5]|2[0-4]\d|1?\d?\d)$/',
        '/^(WATER|OIL|FILTER1|FILTER2|PUMP4) (?:25[0-5]|2[0-4]\d|1?\d?\d)$/',
        '/^(WATER|OIL|FILTER1|FILTER2|MOTOR3|PUMP4) (ON|OFF)$/',
        '/^MOTOR3 SPEED (?:25[0-5]|2[0-4]\d|1?\d?\d)$/',
        '/^MOTOR3 (CW|CCW)$/',
    ];

    /** @var array<string, string> */
    public const COMPONENTES = [
        'MOTORES' => 'Motores 1 y 2 (L298N #1)',
        'WATER' => 'Bomba de agua',
        'OIL' => 'Bomba de aceite',
        'FILTER1' => 'Filtro 1',
        'FILTER2' => 'Filtro 2',
        'MOTOR3' => 'Motoreductor 3',
        'PUMP4' => 'Bomba 4',
    ];

    public function __construct(private readonly array $config) {}

    public static function fromConfig(): self
    {
        return new self((array) config('services.esp32'));
    }

    public function normalizar(string $comando): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $comando) ?? ''));
    }

    public function comandoValido(string $comando): bool
    {
        $comando = $this->normalizar($comando);

        if ($comando === '') {
            return false;
        }

        foreach (self::PATRONES as $patron) {
            if (preg_match($patron, $comando) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{ok: bool, mensaje: string}
     */
    public function enviar(string $comando): array
    {
        $comando = $this->normalizar($comando);

        if (! $this->comandoValido($comando)) {
            return ['ok' => false, 'mensaje' => "Comando no permitido: {$comando}"];
        }

        try {
            $respuesta = $this->http()->get($this->url('/cmd'), ['c' => $comando]);

            if ($respuesta->failed()) {
                return ['ok' => false, 'mensaje' => 'El ESP32 respondió con error '.$respuesta->status().'.'];
            }

            return ['ok' => true, 'mensaje' => trim($respuesta->body())];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'mensaje' => 'Sin conexión con el ESP32 ('.$e->getMessage().').'];
        }
    }

    /**
     * Estado de la placa: JSON de /status + lectura de sensores IR.
     *
     * @return array<string, mixed>
     */
    public function estado(bool $conSensores = true): array
    {
        $vacio = [
            'conectado' => false,
            'temperatura' => null,
            'motores' => false,
            'motor3' => 'OFF',
            'bomba4' => 0,
            'agua' => 0,
            'ir1' => null,
            'ir2' => null,
            'error' => null,
        ];

        try {
            $status = $this->http()->acceptJson()->get($this->url('/status'));

            if ($status->failed()) {
                return array_merge($vacio, ['error' => 'El ESP32 respondió '.$status->status().'.']);
            }

            /** @var array<string, mixed> $datos */
            $datos = $status->json() ?? [];

            $estado = [
                'conectado' => true,
                'temperatura' => isset($datos['temp']) ? (float) $datos['temp'] : null,
                'motores' => (bool) ($datos['motors'] ?? false),
                'motor3' => (string) ($datos['m3'] ?? 'OFF'),
                'bomba4' => (int) ($datos['p4'] ?? 0),
                'agua' => (int) ($datos['water'] ?? 0),
                'ir1' => null,
                'ir2' => null,
                'error' => null,
            ];

            if ($conSensores) {
                $sensores = $this->leerSensores();
                $estado['ir1'] = $sensores['ir1'];
                $estado['ir2'] = $sensores['ir2'];

                if ($sensores['temperatura'] !== null) {
                    $estado['temperatura'] = $sensores['temperatura'];
                }
            }

            return $estado;
        } catch (ConnectionException $e) {
            return array_merge($vacio, ['error' => 'Sin conexión con el ESP32.']);
        }
    }

    /**
     * Lee IR1, IR2 y temperatura con el comando SENSORS del firmware.
     *
     * @return array{ir1: ?bool, ir2: ?bool, temperatura: ?float}
     */
    public function leerSensores(): array
    {
        $resultado = ['ir1' => null, 'ir2' => null, 'temperatura' => null];

        try {
            $respuesta = $this->http()->get($this->url('/cmd'), ['c' => 'SENSORS']);

            if ($respuesta->failed()) {
                return $resultado;
            }

            $cuerpo = $respuesta->body();

            if (preg_match('/IR1:\s*(DETECCION ACTIVA|SIN DETECCION)/i', $cuerpo, $m) === 1) {
                $resultado['ir1'] = str_contains(strtoupper($m[1]), 'ACTIVA');
            }

            if (preg_match('/IR2:\s*(DETECCION ACTIVA|SIN DETECCION)/i', $cuerpo, $m) === 1) {
                $resultado['ir2'] = str_contains(strtoupper($m[1]), 'ACTIVA');
            }

            if (preg_match('/Temperatura:\s*(-?\d+(?:\.\d+)?)/i', $cuerpo, $m) === 1) {
                $resultado['temperatura'] = (float) $m[1];
            }
        } catch (ConnectionException) {
            return $resultado;
        }

        return $resultado;
    }

    public function url(string $ruta): string
    {
        return rtrim((string) ($this->config['url'] ?? ''), '/').$ruta;
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $timeout = max(1, (int) ($this->config['timeout'] ?? 2));

        return Http::withBasicAuth($this->config['username'] ?? '', $this->config['password'] ?? '')
            ->connectTimeout(min(1, $timeout))
            ->timeout($timeout);
    }
}
