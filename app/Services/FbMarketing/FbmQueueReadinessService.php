<?php

namespace App\Services\FbMarketing;

use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class FbmQueueReadinessService
{
    public function centralSummary(): array
    {
        $queueConnection = trim((string) config('fb_marketing.queue.connection', 'fb-marketing'));
        $queueName = trim((string) config('fb_marketing.queue.name', 'fb-marketing'));
        $queueConfig = (array) config('queue.connections.' . $queueConnection, []);
        $storageConnection = trim((string) ($queueConfig['connection'] ?? ''));
        $databaseConnections = (array) config('database.connections', []);

        $checks = [
            'queue_connection_configured' => $queueConnection !== '' && $queueConfig !== [],
            'queue_driver_database_backed' => ($queueConfig['driver'] ?? null) === 'database',
            'queue_name_configured' => $queueName !== '',
            'queue_storage_connection_configured' => $storageConnection !== '' && array_key_exists($storageConnection, $databaseConnections),
            'jobs_table_ready' => $this->hasTable($storageConnection, 'jobs'),
            'failed_jobs_table_ready' => $this->hasTable($storageConnection, 'failed_jobs'),
        ];

        return [
            'ready' => !in_array(false, $checks, true),
            'queue_connection' => $queueConnection,
            'queue_name' => $queueName,
            'queue_storage_connection' => $storageConnection,
            'checks' => $checks,
            'message' => $this->summaryMessage($checks, 'FB MARKETING queue infrastructure is ready.'),
        ];
    }

    public function applicationSummary(): array
    {
        $checks = [
            'sync_runs_table_ready' => $this->hasTable(null, 'fbm_sync_runs'),
            'api_request_logs_table_ready' => $this->hasTable(null, 'fbm_api_request_logs'),
            'module_settings_table_ready' => $this->hasTable(null, 'fbm_module_settings'),
            'scheduled_sync_enabled_column_ready' => $this->hasColumn('fbm_module_settings', 'scheduled_sync_enabled'),
            'scheduled_sync_interval_column_ready' => $this->hasColumn('fbm_module_settings', 'scheduled_sync_interval_minutes'),
            'scheduled_sync_last_dispatched_column_ready' => $this->hasColumn('fbm_module_settings', 'last_scheduled_sync_dispatched_at'),
            'health_ledger_ready' => $this->hasTable(null, 'fbm_connection_health_checks'),
            'asset_discovery_schema_ready' => $this->assetDiscoverySchemaReady(),
        ];

        return [
            'ready' => !in_array(false, $checks, true),
            'checks' => $checks,
            'message' => $this->summaryMessage($checks, 'Application FB MARKETING sync infrastructure is ready.'),
        ];
    }

    public function currentSummary(): array
    {
        $central = $this->centralSummary();
        $application = $this->applicationSummary();

        return [
            'ready' => $central['ready'] && $application['ready'],
            'queue' => $central,
            'application' => $application,
            'message' => $central['ready'] && $application['ready']
                ? 'FB MARKETING queue, scheduler and sync infrastructure are ready.'
                : trim($central['message'] . ' ' . $application['message']),
        ];
    }

    public function assertCurrentReady(): void
    {
        $summary = $this->currentSummary();
        if (!$summary['ready']) {
            throw new RuntimeException('FB MARKETING sync dispatch is not ready. Run queue-readiness and apply the required application migrations.');
        }
    }

    protected function hasTable(?string $connection, string $table): bool
    {
        if ($connection === '') {
            return false;
        }

        try {
            return $connection === null
                ? Schema::hasTable($table)
                : Schema::connection($connection)->hasTable($table);
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function assetDiscoverySchemaReady(): bool
    {
        try {
            return FbmAssetDiscoveryService::schemaReady();
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function summaryMessage(array $checks, string $readyMessage): string
    {
        $missing = array_keys(array_filter($checks, fn($ready) => !$ready));
        if ($missing === []) {
            return $readyMessage;
        }

        return 'Missing readiness checks: ' . implode(', ', $missing) . '.';
    }
}
