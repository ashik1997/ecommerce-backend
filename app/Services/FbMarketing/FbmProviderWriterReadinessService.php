<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionHealthCheck;
use Illuminate\Support\Facades\Schema;

class FbmProviderWriterReadinessService
{
    public function currentSummary(): array
    {
        $schemaReady = $this->schemaReady();
        $connections = $schemaReady ? $this->connectionSummaries() : [];
        $readyConnections = array_values(array_filter($connections, fn(array $row): bool => (bool) $row['ready_for_provider_writer']));
        $selectedAdAccounts = $schemaReady ? FbmAdAccount::query()->where('is_available', true)->where('is_selected', true)->count() : 0;
        $publishEnabled = (bool) config('fb_marketing.campaign_publish.provider_writes_enabled', false);
        $actionsEnabled = (bool) config('fb_marketing.operational_actions.provider_writes_enabled', false);
        $ready = $schemaReady && count($readyConnections) > 0 && $selectedAdAccounts > 0;

        return [
            'schema_ready' => $schemaReady,
            'ready' => $ready,
            'status' => $this->status($schemaReady, $ready, $publishEnabled, $actionsEnabled),
            'message' => $this->message($schemaReady, $ready, $publishEnabled, $actionsEnabled, $connections, $selectedAdAccounts),
            'provider_writes_enabled' => $publishEnabled || $actionsEnabled,
            'campaign_publish_enabled' => $publishEnabled,
            'operational_actions_enabled' => $actionsEnabled,
            'required_scopes' => $this->requiredScopes(),
            'active_connection_count' => $schemaReady ? FbmConnection::query()->where('is_active', true)->count() : 0,
            'ready_connection_count' => count($readyConnections),
            'selected_ad_account_count' => $selectedAdAccounts,
            'connections' => $connections,
            'safety_policy' => [
                'max_single_daily_budget_amount' => (float) config('fb_marketing.provider_writer.max_single_daily_budget_amount', 5000),
                'max_single_lifetime_budget_amount' => (float) config('fb_marketing.provider_writer.max_single_lifetime_budget_amount', 50000),
                'idempotency_ttl_hours' => max(1, (int) config('fb_marketing.provider_writer.idempotency_ttl_hours', 72)),
                'max_retry_attempts' => max(0, (int) config('fb_marketing.provider_writer.max_retry_attempts', 3)),
            ],
            'allowed_publish_edges' => $this->safeList(config('fb_marketing.provider_writer.allowed_publish_edges', [])),
            'allowed_operational_targets' => $this->safeList(config('fb_marketing.provider_writer.allowed_operational_targets', [])),
            'allowed_operational_actions' => $this->safeList(config('fb_marketing.provider_writer.allowed_operational_actions', [])),
        ];
    }

    public function assertReadyFor(string $operation): array
    {
        $summary = $this->currentSummary();
        $enabled = $operation === 'campaign_publish'
            ? (bool) $summary['campaign_publish_enabled']
            : (bool) $summary['operational_actions_enabled'];

        if (!$enabled) {
            return [
                'ready' => false,
                'reason' => 'provider_writes_disabled',
                'message' => 'Provider writes are disabled by configuration.',
                'summary' => $summary,
            ];
        }

        if (!$summary['ready']) {
            return [
                'ready' => false,
                'reason' => 'provider_writer_not_ready',
                'message' => $summary['message'],
                'summary' => $summary,
            ];
        }

        return [
            'ready' => true,
            'reason' => 'provider_writer_ready',
            'message' => 'Provider writer foundation is ready for the controlled worker.',
            'summary' => $summary,
        ];
    }

    private function connectionSummaries(): array
    {
        return FbmConnection::query()
            ->with('latestHealthCheck')
            ->orderByDesc('is_active')
            ->orderBy('connection_name')
            ->get()
            ->map(function (FbmConnection $connection): array {
                $health = $connection->latestHealthCheck;
                $scopes = $health instanceof FbmConnectionHealthCheck && is_array($health->scopes) ? $health->scopes : [];
                $missingWriterScopes = array_values(array_diff($this->requiredScopes(), $scopes));
                $selectedAccounts = FbmAdAccount::query()
                    ->where('fbm_connection_id', (int) $connection->id)
                    ->where('is_available', true)
                    ->where('is_selected', true)
                    ->count();
                $healthStatus = $health instanceof FbmConnectionHealthCheck ? (string) $health->status : 'never_tested';
                $ready = (bool) $connection->is_active
                    && $connection->hasConfiguredSecret('access_token')
                    && in_array($healthStatus, [FbmConnectionHealthCheck::STATUS_HEALTHY, FbmConnectionHealthCheck::STATUS_WARNING], true)
                    && $missingWriterScopes === []
                    && $selectedAccounts > 0;

                return [
                    'id' => (int) $connection->id,
                    'connection_name' => (string) $connection->connection_name,
                    'is_active' => (bool) $connection->is_active,
                    'graph_api_version' => $connection->graph_api_version,
                    'access_token_configured' => $connection->hasConfiguredSecret('access_token'),
                    'latest_health_status' => $healthStatus,
                    'latest_health_checked_at' => $health instanceof FbmConnectionHealthCheck ? optional($health->checked_at)->toDateTimeString() : null,
                    'missing_writer_scopes' => $missingWriterScopes,
                    'selected_ad_account_count' => $selectedAccounts,
                    'ready_for_provider_writer' => $ready,
                    'readiness_message' => $this->connectionMessage($connection, $healthStatus, $missingWriterScopes, $selectedAccounts),
                ];
            })
            ->values()
            ->all();
    }

    private function connectionMessage(FbmConnection $connection, string $healthStatus, array $missingScopes, int $selectedAdAccounts): string
    {
        if (!$connection->is_active) {
            return 'Connection is disabled.';
        }
        if (!$connection->hasConfiguredSecret('access_token')) {
            return 'Encrypted Meta access token is not configured.';
        }
        if (!in_array($healthStatus, [FbmConnectionHealthCheck::STATUS_HEALTHY, FbmConnectionHealthCheck::STATUS_WARNING], true)) {
            return 'Run a successful read-only health check before enabling provider writes.';
        }
        if ($missingScopes !== []) {
            return 'Missing writer scope(s): ' . implode(', ', $missingScopes) . '.';
        }
        if ($selectedAdAccounts < 1) {
            return 'Select at least one available Ad Account for this connection.';
        }

        return 'Connection passes the local provider-writer readiness checks.';
    }

    private function schemaReady(): bool
    {
        foreach ([
            'fbm_connections',
            'fbm_connection_health_checks',
            'fbm_ad_accounts',
            'fbm_campaign_publish_attempts',
            'fbm_campaign_publish_steps',
            'fbm_campaign_operational_actions',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function status(bool $schemaReady, bool $ready, bool $publishEnabled, bool $actionsEnabled): string
    {
        if (!$schemaReady) {
            return 'migration_required';
        }
        if (!$ready) {
            return 'not_ready';
        }
        if ($publishEnabled || $actionsEnabled) {
            return 'ready_enabled';
        }

        return 'ready_disabled';
    }

    private function message(bool $schemaReady, bool $ready, bool $publishEnabled, bool $actionsEnabled, array $connections, int $selectedAdAccounts): string
    {
        if (!$schemaReady) {
            return 'Apply prior FB MARKETING migrations before provider writer readiness can be evaluated.';
        }
        if ($connections === []) {
            return 'Add an active encrypted Meta connection and run a read-only health check.';
        }
        if ($selectedAdAccounts < 1) {
            return 'Select at least one available Meta Ad Account before enabling provider writes.';
        }
        if (!$ready) {
            return 'One or more active connections still need writer scopes, health validation or selected Ad Account coverage.';
        }
        if ($publishEnabled || $actionsEnabled) {
            return 'Provider writer foundation is ready and at least one write surface is enabled.';
        }

        return 'Provider writer foundation is ready, but live writes remain disabled by configuration.';
    }

    private function requiredScopes(): array
    {
        return $this->safeList(config('fb_marketing.provider_writer.required_scopes', ['ads_management']));
    }

    private function safeList($values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $safe = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '' && preg_match('/^[A-Za-z0-9_{}\/.-]+$/', $value)) {
                $safe[] = $value;
            }
        }

        return array_values(array_unique($safe));
    }
}
