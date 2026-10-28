<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\IngredienteBase;
use App\Enums\TipoProducto;

/**
 * Cálculo de jabón a partir de valores SAP publicados (SoapCalc / tablas
 * clásicas de saponificación): NaOH por gramo de aceite, KOH = NaOH × 1.4025.
 *
 * Barra: agua ≈ 38 % del aceite (proceso en frío habitual).
 * Líquido: pasta de KOH con agua = 3 × KOH, luego se diluye.
 * Sobreengrasado: 5 % barra, 3 % líquido.
 */
class FormulacionService
{
    public const FACTOR_KOH = 1.4025;

    /**
     * @return array<string, mixed>
     */
    public function catalogo(): array
    {
        return [
            'aceites' => config('panel.aceites', []),
            'barra' => [
                'alcali' => 'naoh',
                'etiqueta_alcali' => 'Sosa cáustica (NaOH)',
                'sobreengrasado' => 5.0,
                'agua_sobre_aceite' => 0.38,
            ],
            'liquido' => [
                'alcali' => 'koh',
                'etiqueta_alcali' => 'Potasa cáustica (KOH)',
                'sobreengrasado' => 3.0,
                'agua_sobre_alcali' => 3.0,
            ],
            'aditivos' => [
                'barra' => [
                    ['nombre' => 'Aceite esencial', 'porcentaje_aceite' => 3.0],
                    ['nombre' => 'Glicerina vegetal', 'porcentaje_aceite' => 2.0],
                ],
                'liquido' => [
                    ['nombre' => 'Glicerina vegetal', 'porcentaje_aceite' => 5.0],
                    ['nombre' => 'Fragancia', 'porcentaje_aceite' => 1.5],
                ],
            ],
        ];
    }

    /**
     * @return array<string, float>
     */
    public function factores(): array
    {
        return [
            'aceite' => 1.0,
            'agua' => 0.38,
            'sosa' => 0.14,
        ];
    }

    /**
     * @return array<string, float|string>
     */
    public function calcular(
        TipoProducto $tipo,
        string $tipoAceite,
        IngredienteBase $base,
        float $peso,
    ): array {
        $aceite = $this->aceite($tipoAceite);

        if ($aceite === null || $peso <= 0) {
            return [];
        }

        $sapNaoh = (float) $aceite['sap_naoh'];
        $esBarra = $tipo === TipoProducto::Barra;
        $sobreengrasado = $esBarra ? 5.0 : 3.0;
        $sap = $esBarra ? $sapNaoh : $sapNaoh * self::FACTOR_KOH;
        $factorAlcali = $sap * (1 - $sobreengrasado / 100);

        if ($factorAlcali <= 0) {
            return [];
        }

        $aceites = match ($base) {
            IngredienteBase::Aceite => $peso,
            IngredienteBase::Sosa => $peso / $factorAlcali,
            IngredienteBase::Agua => $esBarra
                ? $peso / 0.38
                : $peso / (3.0 * $factorAlcali),
        };

        if ($aceites <= 0) {
            return [];
        }

        $alcali = round($aceites * $factorAlcali, 2);
        $agua = $esBarra
            ? round($aceites * 0.38, 2)
            : round($alcali * 3.0, 2);
        $aceites = round($aceites, 2);

        $aditivos = [];
        foreach ($this->catalogo()['aditivos'][$tipo->value] ?? [] as $aditivo) {
            $gramos = round($aceites * ((float) $aditivo['porcentaje_aceite'] / 100), 2);
            $aditivos[] = [
                'nombre' => $aditivo['nombre'],
                'gramos' => $gramos,
            ];
        }

        return [
            'aceite' => $aceites,
            'agua' => $agua,
            'sosa' => $alcali,
            'alcali_nombre' => $esBarra ? 'NaOH' : 'KOH',
            'sobreengrasado' => $sobreengrasado,
            'sap' => round($sap, 4),
            'tipo_aceite' => $aceite['nombre'],
            'aditivos_sugeridos' => $aditivos,
        ];
    }

    /**
     * @param  array<string, float|int>  $calculos
     */
    public function total(array $calculos): float
    {
        return round(
            (float) ($calculos['aceite'] ?? 0)
            + (float) ($calculos['agua'] ?? 0)
            + (float) ($calculos['sosa'] ?? 0),
            2
        );
    }

    /**
     * @return array{clave: string, nombre: string, sap_naoh: float}|null
     */
    public function aceite(string $clave): ?array
    {
        $aceites = config('panel.aceites', []);
        $datos = $aceites[$clave] ?? null;

        if (! is_array($datos)) {
            return null;
        }

        return [
            'clave' => $clave,
            'nombre' => (string) ($datos['nombre'] ?? $clave),
            'sap_naoh' => (float) ($datos['sap_naoh'] ?? 0),
        ];
    }
}
