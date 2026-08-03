<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Reduz as variações de grafia de um local a uma chave única.
 *
 * O SAP recebe o mesmo lugar escrito de formas diferentes — "ARM-MACAE",
 * "ARM-MACAÉ", "ARM MACAE" — e sem unificá-las o agrupamento por trecho
 * espalharia o mesmo fluxo em várias linhas.
 *
 * A regra é deliberadamente conservadora: só encosta em caixa, acentuação e
 * separadores. Não tenta adivinhar que "ARM-MACAE (SUCATA)" é o mesmo que
 * "ARM-MACAE", porque pode ser um pátio distinto — esses casos ficam para o
 * ajuste manual de rota, feito por quem conhece a operação.
 */
class NormalizadorLocal
{
    /**
     * Chave canônica usada para agrupar e comparar locais.
     *
     * "ARM-MACAÉ" e "arm macae" viram "ARM MACAE".
     */
    public static function canonizar(?string $local): ?string
    {
        $local = trim((string) $local);

        if ($local === '') {
            return null;
        }

        // Str::ascii remove a acentuação; hífen, barra e sublinhado viram
        // espaço para que os separadores deixem de diferenciar a grafia.
        $local = Str::ascii($local);
        $local = preg_replace('/[\s\-_\/]+/', ' ', $local) ?? $local;

        return mb_strtoupper(trim($local));
    }

    /**
     * Rótulo de um trecho para exibição.
     */
    public static function trecho(?string $origem, ?string $destino): string
    {
        return (self::canonizar($origem) ?? 'SEM ORIGEM').' → '.(self::canonizar($destino) ?? 'SEM DESTINO');
    }
}
