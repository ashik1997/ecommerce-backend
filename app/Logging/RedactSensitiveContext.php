<?php

namespace App\Logging;

use App\Support\Security\SecretRedactor;

class RedactSensitiveContext
{
    /**
     * Apply a fail-safe processor to configured Monolog channels.
     *
     * @param mixed $logger
     * @return void
     */
    public function __invoke($logger): void
    {
        $logger->pushProcessor(function (array $record): array {
            if (isset($record['message']) && is_string($record['message'])) {
                $record['message'] = SecretRedactor::redactString($record['message']);
            }

            if (isset($record['context'])) {
                $record['context'] = SecretRedactor::redact($record['context']);
            }

            if (isset($record['extra'])) {
                $record['extra'] = SecretRedactor::redact($record['extra']);
            }

            return $record;
        });
    }
}
