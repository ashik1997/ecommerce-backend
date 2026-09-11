<?php

namespace App\Services\Crm\CampaignDispatch;

use App\Contracts\Crm\CampaignDispatch\CrmCampaignDispatchProviderAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BulkSmsBdCampaignDispatchReadinessAdapter implements CrmCampaignDispatchProviderAdapter
{
    public function __construct(
        protected BulkSmsBdCampaignGatewayResolver $gatewayResolver,
        protected BulkSmsBdCampaignSmsProtocol $protocol
    ) {
    }

    public function channel(): string
    {
        return 'sms';
    }

    public function providerKey(): string
    {
        return BulkSmsBdCampaignGatewayResolver::PROVIDER_KEY;
    }

    public function supportsChannel(string $channel): bool
    {
        return strtolower(trim($channel)) === $this->channel();
    }

    public function readiness(?int $productWebsiteId): array
    {
        $gateway = $this->gatewayResolver->readiness($productWebsiteId);
        $protocol = $this->protocol->readiness();
        $eventLedger = $this->eventLedgerReadiness();
        $unsafeLocalMaintenanceRoutesDisabled = !config('app.allow_local_unsafe_web_maintenance_routes', false);

        $checks = array_merge(
            $gateway['checks'],
            $protocol['checks'],
            $eventLedger['checks'],
            [[
                'key' => 'unsafe_web_maintenance_routes_disabled',
                'label' => 'Unsafe web-maintenance route gate',
                'passed' => $unsafeLocalMaintenanceRoutesDisabled,
                'message' => $unsafeLocalMaintenanceRoutesDisabled
                    ? 'Legacy unsafe web-maintenance routes are disabled by default.'
                    : 'Disable local unsafe web-maintenance routes before preparing a CRM campaign provider attempt.',
            ]]
        );

        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'channel' => $this->channel(),
            'provider_key' => $this->providerKey(),
            'ready' => $ready,
            'ready_label' => $ready ? 'Locally ready for bounded BulkSMSBD SMS attempt preparation' : 'Provider hardening prerequisites are incomplete',
            'message' => $ready
                ? 'Local application configuration and bounded SMS protocol-hardening checks passed. No remote connectivity probe or provider request was performed during readiness evaluation. Real execution remains a separate explicitly confirmed attempt action.'
                : 'Resolve the failed local hardening checks before preparing a bounded SMS attempt.',
            'checks' => $checks,
            'local_configuration_only' => true,
            'execution_transport_available' => $ready,
            'remote_connectivity_checked' => false,
            'credential_fields_exposed' => false,
            'provider_endpoint_exposed' => false,
            'raw_gateway_record_exposed' => false,
            'provider_payload_exposed' => false,
            'provider_response_exposed' => false,
            'recipient_event_ledger_ready' => $eventLedger['ready'],
            'future_recipient_cap' => $protocol['future_recipient_cap'],
            'future_connect_timeout_seconds' => $protocol['future_connect_timeout_seconds'],
            'future_total_timeout_seconds' => $protocol['future_total_timeout_seconds'],
            'single_recipient_requests_only' => true,
            'post_only' => true,
            'automatic_retry_available' => false,
            'send_available' => false,
            'execute_available' => false,
        ];
    }

    protected function eventLedgerReadiness(): array
    {
        $requiredColumns = [
            'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_attempt_id',
            'crm_campaign_dispatch_recipient_attempt_id', 'crm_campaign_dispatch_execution_batch_id',
            'event_type', 'provider_key', 'request_sequence', 'terminal_slot', 'status',
            'provider_message_id', 'provider_response_code', 'redacted_response_summary', 'response_hash',
            'request_started_at', 'request_completed_at', 'failed_at', 'failure_category', 'failure_summary',
            'metadata_json', 'created_at',
        ];

        $ready = Schema::hasTable('crm_campaign_dispatch_recipient_attempt_events');
        foreach ($requiredColumns as $column) {
            $ready = $ready && Schema::hasColumn('crm_campaign_dispatch_recipient_attempt_events', $column);
        }

        $requiredIndexes = [
            'crm_campaign_dispatch_rec_events_seq_type_unique',
            'crm_campaign_dispatch_rec_events_seq_terminal_unique',
        ];
        foreach ($requiredIndexes as $index) {
            $ready = $ready && $this->hasIndex('crm_campaign_dispatch_recipient_attempt_events', $index);
        }

        return [
            'ready' => $ready,
            'checks' => [[
                'key' => 'recipient_event_ledger',
                'label' => 'Append-only recipient exchange-ledger foundation',
                'passed' => $ready,
                'message' => $ready
                    ? 'The append-only recipient-attempt event ledger foundation and duplicate-event guards are available.'
                    : 'Run the Stage 26B application migration before preparing a hardened provider attempt.',
            ]],
        ];
    }

    protected function hasIndex(string $table, string $index): bool
    {
        try {
            return count(DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index])) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
