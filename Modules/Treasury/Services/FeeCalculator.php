<?php

namespace Modules\Treasury\Services;

use Illuminate\Validation\ValidationException;

/**
 * Cálculo de comisiones, IGV e ITF de Tesorería.
 *
 * - Comisión simple: fijo + monto * porcentaje (+ IGV opcional sobre la comisión).
 * - Fórmula: expresión aritmética con las variables permitidas
 *   (monto, fijo, porcentaje, igv). Se evalúa con un parser propio, sin eval().
 * - ITF: 0.005 % del monto de la operación, solo si el monto alcanza el umbral
 *   configurado (por defecto S/ 1000). Se redondea a 2 decimales.
 */
class FeeCalculator
{
    /** Variables que pueden usarse en una fórmula de comisión. */
    public const VARIABLES = ['monto', 'fijo', 'porcentaje', 'igv'];

    /** Tasa del ITF según SUNAT: 0.005 % = 0.00005. */
    public const ITF_RATE = 0.00005;

    /** IGV vigente en Perú. */
    public const IGV_RATE = 0.18;

    /**
     * Calcula la comisión de una operación.
     *
     * @return array{comision: float, igv: float, total: float}
     */
    public function commission(
        float $amount,
        float $fixedFee,
        float $percentFee,
        bool $igvApplies,
        ?string $formula = null,
    ): array {
        if (filled($formula)) {
            $commission = $this->evaluate($formula, [
                'monto' => $amount,
                'fijo' => $fixedFee,
                'porcentaje' => $percentFee,
                'igv' => self::IGV_RATE,
            ]);
            // En fórmula, el IGV ya va incluido en la expresión si se desea.
            $igv = 0.0;
        } else {
            $commission = $fixedFee + ($amount * $percentFee / 100);
            $igv = $igvApplies ? $commission * self::IGV_RATE : 0.0;
        }

        $commission = round(max(0, $commission), 2);
        $igv = round(max(0, $igv), 2);

        return [
            'comision' => $commission,
            'igv' => $igv,
            'total' => round($commission + $igv, 2),
        ];
    }

    /**
     * ITF de una operación: 0.005 % sobre el monto si alcanza el umbral.
     */
    public function itf(float $amount, float $threshold = 1000.0): float
    {
        if ($amount < $threshold) {
            return 0.0;
        }

        return round($amount * self::ITF_RATE, 2);
    }

    /**
     * Evalúa una fórmula con + - * / y paréntesis. Solo acepta las variables
     * permitidas y números; cualquier otro carácter o variable se rechaza.
     */
    public function evaluate(string $formula, array $values): float
    {
        $tokens = $this->tokenize($formula);

        $parser = new class($tokens, $values) {
            private int $pos = 0;

            public function __construct(private array $tokens, private array $values)
            {
            }

            public function parse(): float
            {
                $result = $this->expression();

                if ($this->pos < count($this->tokens)) {
                    throw new \InvalidArgumentException('Fórmula inválida.');
                }

                return $result;
            }

            private function expression(): float
            {
                $value = $this->term();

                while (in_array($this->peek(), ['+', '-'], true)) {
                    $op = $this->tokens[$this->pos++];
                    $right = $this->term();
                    $value = $op === '+' ? $value + $right : $value - $right;
                }

                return $value;
            }

            private function term(): float
            {
                $value = $this->factor();

                while (in_array($this->peek(), ['*', '/'], true)) {
                    $op = $this->tokens[$this->pos++];
                    $right = $this->factor();

                    if ($op === '/' && $right == 0.0) {
                        throw new \InvalidArgumentException('División entre cero en la fórmula.');
                    }

                    $value = $op === '*' ? $value * $right : $value / $right;
                }

                return $value;
            }

            private function factor(): float
            {
                $token = $this->tokens[$this->pos] ?? null;

                if ($token === '-') {
                    $this->pos++;

                    return -$this->factor();
                }

                if ($token === '(') {
                    $this->pos++;
                    $value = $this->expression();

                    if (($this->tokens[$this->pos] ?? null) !== ')') {
                        throw new \InvalidArgumentException('Falta cerrar un paréntesis.');
                    }

                    $this->pos++;

                    return $value;
                }

                if ($token === null) {
                    throw new \InvalidArgumentException('Fórmula incompleta.');
                }

                $this->pos++;

                if (is_numeric($token)) {
                    return (float) $token;
                }

                if (array_key_exists($token, $this->values)) {
                    return (float) $this->values[$token];
                }

                throw new \InvalidArgumentException("Variable no permitida: {$token}.");
            }

            private function peek(): ?string
            {
                return $this->tokens[$this->pos] ?? null;
            }
        };

        try {
            return $parser->parse();
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['commission_formula' => $e->getMessage()]);
        }
    }

    /**
     * Valida una fórmula sin calcular con datos reales (para el formulario).
     */
    public function validateFormula(string $formula): void
    {
        $this->evaluate($formula, [
            'monto' => 100,
            'fijo' => 1,
            'porcentaje' => 1,
            'igv' => self::IGV_RATE,
        ]);
    }

    /**
     * Separa la fórmula en tokens. Rechaza caracteres fuera de la gramática.
     *
     * @return array<int, string>
     */
    private function tokenize(string $formula): array
    {
        $formula = trim($formula);

        if ($formula === '') {
            throw ValidationException::withMessages(['commission_formula' => 'La fórmula está vacía.']);
        }

        preg_match_all('/\s*(\d+(?:\.\d+)?|[a-z_]+|[-+*\/()])\s*/i', $formula, $matches, PREG_OFFSET_CAPTURE);

        $tokens = [];
        $consumed = 0;

        foreach ($matches[1] as [$token, $offset]) {
            // Detecta caracteres no permitidos entre tokens consecutivos.
            $gap = substr($formula, $consumed, $offset - $consumed);

            if (trim($gap) !== '') {
                throw ValidationException::withMessages([
                    'commission_formula' => 'La fórmula contiene caracteres no permitidos.',
                ]);
            }

            $tokens[] = strtolower($token);
            $consumed = $offset + strlen($token);
        }

        if (trim(substr($formula, $consumed)) !== '') {
            throw ValidationException::withMessages([
                'commission_formula' => 'La fórmula contiene caracteres no permitidos.',
            ]);
        }

        return $tokens;
    }
}
