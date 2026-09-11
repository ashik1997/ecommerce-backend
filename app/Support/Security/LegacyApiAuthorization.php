<?php

namespace App\Support\Security;

use Illuminate\Http\Request;

class LegacyApiAuthorization
{
    /**
     * Return the server-side legacy API token. An empty value deliberately
     * fails closed so deployments cannot accidentally expose protected APIs.
     */
    public static function token(): string
    {
        return (string) config('services.legacy_api.authorization_token', '');
    }

    /**
     * Compare the caller token without placing the configured value in source,
     * browser payloads, application logs or exception output.
     */
    public static function matches(Request $request): bool
    {
        $configuredToken = self::token();
        $providedToken = (string) $request->header('Authorization', '');

        return $configuredToken !== ''
            && $providedToken !== ''
            && hash_equals($configuredToken, $providedToken);
    }
}
