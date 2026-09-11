<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Throwable;

class EncryptedNullableString implements CastsAttributes
{
    /**
     * Decrypt a nullable application secret for server-side consumers only.
     * Browser payloads must use safe projections and never serialize this value.
     *
     * @param  mixed  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (Throwable $exception) {
            throw new RuntimeException('An encrypted FB MARKETING secret could not be decrypted safely.', 0, $exception);
        }
    }

    /**
     * Encrypt a nullable application secret before database persistence.
     *
     * @param  mixed  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
