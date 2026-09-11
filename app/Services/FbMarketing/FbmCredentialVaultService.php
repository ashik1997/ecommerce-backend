<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionSecretAudit;
use App\Models\User;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FbmCredentialVaultService
{
    public const SECRET_INPUT_MAP = [
        'app_secret' => 'app_secret_ciphertext',
        'access_token' => 'access_token_ciphertext',
        'capi_access_token' => 'capi_access_token_ciphertext',
        'capi_test_event_code' => 'capi_test_event_code_ciphertext',
        'webhook_verify_token' => 'webhook_verify_token_ciphertext',
    ];

    private const SAFE_INPUTS = [
        'connection_name',
        'app_id',
        'credential_mode',
        'graph_api_version',
        'notes',
    ];

    public function create(array $input, User $actor, ?string $ipAddress = null): FbmConnection
    {
        return DB::transaction(function () use ($input, $actor, $ipAddress) {
            $connection = new FbmConnection();
            $connection->fill($this->safeAttributes($input));
            $connection->is_active = (bool) ($input['is_active'] ?? true);
            $connection->secret_version = 0;
            $connection->created_by = $actor->id;
            $connection->updated_by = $actor->id;

            $secretInputs = $this->replacementSecrets($input);
            $this->applySecretReplacements($connection, $secretInputs);
            $connection->save();

            $this->writeAudit(
                $connection,
                'created',
                $actor,
                [],
                $this->safeProjection($connection),
                array_merge(array_keys($this->safeAttributes($input)), $this->secretChangeLabels($secretInputs)),
                $secretInputs,
                $ipAddress,
                $input['change_reason'] ?? null
            );

            return $connection->fresh();
        });
    }

    public function update(FbmConnection $connection, array $input, User $actor, ?string $ipAddress = null): FbmConnection
    {
        return DB::transaction(function () use ($connection, $input, $actor, $ipAddress) {
            $before = $this->safeProjection($connection);
            $safeAttributes = $this->safeAttributes($input);
            $connection->fill($safeAttributes);
            $connection->updated_by = $actor->id;

            $secretInputs = $this->replacementSecrets($input);
            $secretChanged = $this->applySecretReplacements($connection, $secretInputs);
            $connection->save();

            $after = $this->safeProjection($connection);
            $changedFields = array_values(array_unique(array_merge(
                $this->changedSafeFields($before, $after),
                $this->secretChangeLabels($secretInputs)
            )));

            $this->writeAudit(
                $connection,
                $secretChanged ? 'rotated' : 'updated',
                $actor,
                $before,
                $after,
                $changedFields,
                $secretInputs,
                $ipAddress,
                $input['change_reason'] ?? null
            );

            return $connection->fresh();
        });
    }

    public function setActive(FbmConnection $connection, bool $isActive, User $actor, ?string $ipAddress = null, ?string $reason = null): FbmConnection
    {
        return DB::transaction(function () use ($connection, $isActive, $actor, $ipAddress, $reason) {
            $before = $this->safeProjection($connection);

            $connection->is_active = $isActive;
            $connection->updated_by = $actor->id;
            $connection->disabled_at = $isActive ? null : now();
            $connection->disabled_by = $isActive ? null : $actor->id;
            $connection->save();

            $after = $this->safeProjection($connection);

            $this->writeAudit(
                $connection,
                $isActive ? 'enabled' : 'disabled',
                $actor,
                $before,
                $after,
                ['is_active'],
                [],
                $ipAddress,
                $reason
            );

            return $connection->fresh();
        });
    }

    private function safeAttributes(array $input): array
    {
        $attributes = Arr::only($input, self::SAFE_INPUTS);

        foreach (['connection_name', 'app_id', 'credential_mode', 'graph_api_version'] as $key) {
            if (array_key_exists($key, $attributes) && is_string($attributes[$key])) {
                $attributes[$key] = trim($attributes[$key]);
            }
        }

        foreach (['app_id', 'graph_api_version'] as $nullableKey) {
            if (array_key_exists($nullableKey, $attributes) && $attributes[$nullableKey] === '') {
                $attributes[$nullableKey] = null;
            }
        }

        if (array_key_exists('notes', $attributes)) {
            $notes = trim((string) ($attributes['notes'] ?? ''));
            $attributes['notes'] = $notes === '' ? null : SecretRedactor::redactString($notes);
        }

        return $attributes;
    }

    private function replacementSecrets(array $input): array
    {
        $secrets = [];

        foreach (self::SECRET_INPUT_MAP as $inputKey => $column) {
            $value = $input[$inputKey] ?? null;

            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $secrets[$inputKey] = $value;
        }

        return $secrets;
    }

    private function applySecretReplacements(FbmConnection $connection, array $secretInputs): bool
    {
        if ($secretInputs === []) {
            return false;
        }

        foreach ($secretInputs as $inputKey => $plaintext) {
            $column = self::SECRET_INPUT_MAP[$inputKey];
            $connection->{$column} = $plaintext;
        }

        $connection->secret_version = ((int) $connection->secret_version) + 1;
        $connection->credential_updated_at = now();

        return true;
    }

    private function safeProjection(FbmConnection $connection): array
    {
        return $connection->toSafeSummary();
    }

    private function changedSafeFields(array $before, array $after): array
    {
        $changed = [];

        foreach (['connection_name', 'app_id', 'credential_mode', 'graph_api_version', 'notes', 'is_active'] as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    private function secretChangeLabels(array $secretInputs): array
    {
        return array_map(fn(string $field): string => 'secret:' . $field, array_keys($secretInputs));
    }

    private function writeAudit(
        FbmConnection $connection,
        string $action,
        User $actor,
        array $before,
        array $after,
        array $changedFields,
        array $secretInputs,
        ?string $ipAddress,
        ?string $reason
    ): void {
        FbmConnectionSecretAudit::create([
            'fbm_connection_id' => $connection->id,
            'action' => $action,
            'actor_user_id' => $actor->id,
            'changed_fields' => array_values(array_unique($changedFields)),
            'configured_secret_fields' => $connection->configuredSecretFields(),
            'secret_fingerprints' => $this->secretFingerprints($secretInputs),
            'before_state' => $before === [] ? null : $before,
            'after_state' => $after,
            'request_ip_hash' => $this->hashOptionalValue($ipAddress),
            'change_reason' => $this->sanitizeReason($reason),
            'created_at' => now(),
        ]);
    }

    private function secretFingerprints(array $secretInputs): array
    {
        $fingerprints = [];

        foreach ($secretInputs as $field => $plaintext) {
            $fingerprints[$field] = hash_hmac('sha256', $plaintext, $this->applicationKeyMaterial());
        }

        return $fingerprints;
    }

    private function hashOptionalValue(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, $this->applicationKeyMaterial());
    }

    private function applicationKeyMaterial(): string
    {
        return (string) config('app.key', '');
    }

    private function sanitizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : SecretRedactor::redactString($reason);
    }
}
