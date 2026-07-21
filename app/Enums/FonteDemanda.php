<?php

namespace App\Enums;

enum FonteDemanda: string
{
    case SapLt = 'sap_lt';
    case SapTm = 'sap_tm';

    public function label(): string
    {
        return match ($this) {
            self::SapLt => 'SAP LT',
            self::SapTm => 'SAP TM',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SapLt => 'cyan',
            self::SapTm => 'violet',
        };
    }

    /**
     * Resolve a fonte pelo prefixo do número da demanda: 50 → SAP LT, 61 → SAP TM.
     */
    public static function fromNumeroDemanda(int|string|null $numero): ?self
    {
        $numero = (string) $numero;

        return match (substr($numero, 0, 2)) {
            '50' => self::SapLt,
            '61' => self::SapTm,
            default => null,
        };
    }
}
