<?php

namespace App\Models\FbMarketing;

use App\Casts\EncryptedNullableString;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbmConnection extends Model
{
    use HasFactory;

    public const CREDENTIAL_MODES = [
        'system_user',
        'user_access_token',
        'other',
    ];

    public const SECRET_COLUMNS = [
        'app_secret_ciphertext',
        'access_token_ciphertext',
        'capi_access_token_ciphertext',
        'capi_test_event_code_ciphertext',
        'webhook_verify_token_ciphertext',
    ];

    public const SECRET_LABELS = [
        'app_secret_ciphertext' => 'app_secret',
        'access_token_ciphertext' => 'access_token',
        'capi_access_token_ciphertext' => 'capi_access_token',
        'capi_test_event_code_ciphertext' => 'capi_test_event_code',
        'webhook_verify_token_ciphertext' => 'webhook_verify_token',
    ];

    protected $table = 'fbm_connections';

    protected $fillable = [
        'connection_name',
        'app_id',
        'credential_mode',
        'graph_api_version',
        'app_secret_ciphertext',
        'access_token_ciphertext',
        'capi_access_token_ciphertext',
        'capi_test_event_code_ciphertext',
        'webhook_verify_token_ciphertext',
        'is_active',
        'notes',
        'secret_version',
        'credential_updated_at',
        'created_by',
        'updated_by',
        'disabled_by',
        'disabled_at',
    ];

    protected $casts = [
        'app_secret_ciphertext' => EncryptedNullableString::class,
        'access_token_ciphertext' => EncryptedNullableString::class,
        'capi_access_token_ciphertext' => EncryptedNullableString::class,
        'capi_test_event_code_ciphertext' => EncryptedNullableString::class,
        'webhook_verify_token_ciphertext' => EncryptedNullableString::class,
        'is_active' => 'boolean',
        'secret_version' => 'integer',
        'credential_updated_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    protected $hidden = [
        'app_secret_ciphertext',
        'access_token_ciphertext',
        'capi_access_token_ciphertext',
        'capi_test_event_code_ciphertext',
        'webhook_verify_token_ciphertext',
    ];

    public function audits()
    {
        return $this->hasMany(FbmConnectionSecretAudit::class, 'fbm_connection_id');
    }

    public function healthChecks()
    {
        return $this->hasMany(FbmConnectionHealthCheck::class, 'fbm_connection_id');
    }

    public function latestHealthCheck()
    {
        return $this->hasOne(FbmConnectionHealthCheck::class, 'fbm_connection_id')
            ->orderByDesc('checked_at')
            ->orderByDesc('id');
    }

    public function assetDiscoveryRuns()
    {
        return $this->hasMany(FbmAssetDiscoveryRun::class, 'fbm_connection_id');
    }

    public function latestAssetDiscoveryRun()
    {
        return $this->hasOne(FbmAssetDiscoveryRun::class, 'fbm_connection_id')
            ->orderByDesc('completed_at')
            ->orderByDesc('id');
    }


    public function syncRuns()
    {
        return $this->hasMany(FbmSyncRun::class, 'fbm_connection_id');
    }

    public function latestSyncRun()
    {
        return $this->hasOne(FbmSyncRun::class, 'fbm_connection_id')
            ->orderByDesc('requested_at')
            ->orderByDesc('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function disabler()
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }

    public function configuredSecretFields(): array
    {
        $configured = [];

        foreach (self::SECRET_COLUMNS as $column) {
            if ($this->rawSecretIsConfigured($column)) {
                $configured[] = self::SECRET_LABELS[$column];
            }
        }

        return $configured;
    }

    public function hasConfiguredSecret(string $label): bool
    {
        $column = array_search($label, self::SECRET_LABELS, true);

        return is_string($column) && $this->rawSecretIsConfigured($column);
    }

    /**
     * Return an allow-listed browser-safe projection. Ciphertext and decrypted
     * values are deliberately excluded, including masked suffix fragments.
     */
    public function toSafeSummary(): array
    {
        $configured = array_fill_keys($this->configuredSecretFields(), true);

        return [
            'id' => (int) $this->id,
            'connection_name' => (string) $this->connection_name,
            'app_id' => $this->app_id,
            'credential_mode' => (string) $this->credential_mode,
            'graph_api_version' => $this->graph_api_version,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'secret_version' => (int) $this->secret_version,
            'credential_updated_at' => optional($this->credential_updated_at)->toDateTimeString(),
            'disabled_at' => optional($this->disabled_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'configured_secrets' => [
                'app_secret' => !empty($configured['app_secret']),
                'access_token' => !empty($configured['access_token']),
                'capi_access_token' => !empty($configured['capi_access_token']),
                'capi_test_event_code' => !empty($configured['capi_test_event_code']),
                'webhook_verify_token' => !empty($configured['webhook_verify_token']),
            ],
            'health' => $this->relationLoaded('latestHealthCheck') && $this->latestHealthCheck
                ? $this->latestHealthCheck->toSafeSummary()
                : null,
            'discovery' => $this->relationLoaded('latestAssetDiscoveryRun') && $this->latestAssetDiscoveryRun
                ? $this->latestAssetDiscoveryRun->toSafeSummary()
                : null,
            'sync' => $this->relationLoaded('latestSyncRun') && $this->latestSyncRun
                ? $this->latestSyncRun->toSafeSummary()
                : null,
        ];
    }

    protected function rawSecretIsConfigured(string $column): bool
    {
        $value = $this->getRawOriginal($column);

        return is_string($value) && trim($value) !== '';
    }
}
