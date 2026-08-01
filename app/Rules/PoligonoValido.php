<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida o polígono da cerca: no mínimo 3 vértices, coordenadas dentro das
 * faixas geográficas e sem lados que se cruzam (geometria simples).
 */
class PoligonoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Desenhe a área da cerca no mapa.');

            return;
        }

        if (count($value) < 3) {
            $fail('A área precisa de pelo menos 3 vértices.');

            return;
        }

        foreach ($value as $ponto) {
            if (! is_array($ponto) || count($ponto) < 2) {
                $fail('Há vértices em formato inválido — redesenhe a área.');

                return;
            }

            [$lat, $lng] = [$ponto[0] ?? null, $ponto[1] ?? null];

            if (! is_numeric($lat) || ! is_numeric($lng)) {
                $fail('Há coordenadas não numéricas — redesenhe a área.');

                return;
            }

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $fail('Há coordenadas fora do intervalo geográfico válido.');

                return;
            }
        }

        if ($this->possuiAutoIntersecao($value)) {
            $fail('As linhas da área se cruzam. Ajuste os vértices para formar uma área simples.');
        }
    }

    /**
     * Testa se algum par de lados não adjacentes se cruza.
     *
     * @param  array<int, array{0: float, 1: float}>  $pontos
     */
    private function possuiAutoIntersecao(array $pontos): bool
    {
        $n = count($pontos);

        for ($i = 0; $i < $n; $i++) {
            $a1 = $pontos[$i];
            $a2 = $pontos[($i + 1) % $n];

            for ($j = $i + 1; $j < $n; $j++) {
                // Ignora lados vizinhos (compartilham vértice) e o par inicial/final.
                if ($j === $i || ($j + 1) % $n === $i || $i + 1 === $j) {
                    continue;
                }

                if ($this->segmentosCruzam($a1, $a2, $pontos[$j], $pontos[($j + 1) % $n])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array{0: float, 1: float}  $p
     * @param  array{0: float, 1: float}  $q
     * @param  array{0: float, 1: float}  $r
     * @param  array{0: float, 1: float}  $s
     */
    private function segmentosCruzam(array $p, array $q, array $r, array $s): bool
    {
        $d1 = $this->orientacao($r, $s, $p);
        $d2 = $this->orientacao($r, $s, $q);
        $d3 = $this->orientacao($p, $q, $r);
        $d4 = $this->orientacao($p, $q, $s);

        return (($d1 > 0 && $d2 < 0) || ($d1 < 0 && $d2 > 0))
            && (($d3 > 0 && $d4 < 0) || ($d3 < 0 && $d4 > 0));
    }

    /**
     * Produto vetorial: > 0 anti-horário, < 0 horário, 0 colinear.
     *
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     * @param  array{0: float, 1: float}  $c
     */
    private function orientacao(array $a, array $b, array $c): float
    {
        return ($b[0] - $a[0]) * ($c[1] - $a[1]) - ($b[1] - $a[1]) * ($c[0] - $a[0]);
    }
}
