<?php

if (! function_exists('titulo')) {
    /**
     * Converte uma string para título inteligente:
     * capitaliza a primeira palavra sempre e as demais
     * somente quando tiverem mais de 3 caracteres,
     * mantendo preposições curtas (de, do, da, a, ao, e, em…) em minúsculas.
     */
    function titulo(string $value): string
    {
        $words = explode(' ', mb_strtolower($value, 'UTF-8'));

        foreach ($words as $index => $word) {
            if ($index === 0 || mb_strlen($word, 'UTF-8') >= 3) {
                $parts = explode('-', $word);
                $words[$index] = implode('-', array_map(function (string $part): string {
                    if ($part === '') {
                        return $part;
                    }

                    return mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8')
                        .mb_substr($part, 1, null, 'UTF-8');
                }, $parts));
            }
        }

        return implode(' ', $words);
    }
}
