<?php

namespace App\Services\Crm;

use App\Contracts\Crm\CampaignDispatch\CrmCampaignDispatchProviderAdapter;
use App\Services\Crm\CampaignDispatch\BulkSmsBdCampaignDispatchReadinessAdapter;
use App\Services\Crm\CampaignDispatch\EmailCampaignDispatchReadinessAdapter;

class CrmCampaignDispatchProviderRegistry
{
    /** @var array<int, CrmCampaignDispatchProviderAdapter> */
    protected array $adapters;

    public function __construct(
        BulkSmsBdCampaignDispatchReadinessAdapter $bulkSmsBdAdapter,
        EmailCampaignDispatchReadinessAdapter $emailAdapter
    ) {
        $this->adapters = [$bulkSmsBdAdapter, $emailAdapter];
    }

    public function readiness(string $channel, ?int $productWebsiteId): array
    {
        $channel = strtolower(trim($channel));
        foreach ($this->adapters as $adapter) {
            if ($adapter->supportsChannel($channel)) {
                return $adapter->readiness($productWebsiteId);
            }
        }

        return [
            'channel' => $channel,
            'provider_key' => null,
            'ready' => false,
            'ready_label' => 'Channel adapter is not available',
            'message' => 'This campaign channel remains deferred. Local readiness adapters are available only for BulkSMSBD SMS and Email.',
            'checks' => [[
                'key' => 'channel_adapter',
                'label' => 'Channel adapter boundary',
                'passed' => false,
                'message' => 'No supported CRM campaign readiness adapter is registered for this channel.',
            ]],
            'local_configuration_only' => true,
            'remote_connectivity_checked' => false,
            'credential_fields_exposed' => false,
            'provider_endpoint_exposed' => false,
            'raw_gateway_record_exposed' => false,
            'send_available' => false,
        ];
    }
}
