<?php

namespace App\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait HasEncryptedRouteKey
{
    public function getRouteKey(): string
    {
        return Crypt::encryptString((string) $this->getKey());
    }

    public function resolveRouteBinding(mixed $value, $field = null)
    {
        try {
            $id = Crypt::decryptString($value);
        } catch (DecryptException) {
            abort(404);
        }

        return $this->where($field ?? $this->getRouteKeyName(), $id)->firstOrFail();
    }
}
