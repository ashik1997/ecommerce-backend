<?php

namespace App\Services\Crm;

use App\Contracts\Crm\CampaignDispatch\CrmCampaignSmsDispatchAdapter;
use App\Services\Crm\CampaignDispatch\BulkSmsBdCampaignSmsDispatchAdapter;
use Illuminate\Validation\ValidationException;

class CrmCampaignSmsDispatchAdapterRegistry
{
    /** @var array<int, CrmCampaignSmsDispatchAdapter> */
    protected array $adapters;

    public function __construct(BulkSmsBdCampaignSmsDispatchAdapter $bulkSmsBdAdapter)
    {
        $this->adapters = [$bulkSmsBdAdapter];
    }

    public function resolve(string $channel, string $providerKey): CrmCampaignSmsDispatchAdapter
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($channel, $providerKey)) {
                return $adapter;
            }
        }

        throw ValidationException::withMessages([
            'dispatch_attempt' => ['Only the hardened BulkSMSBD SMS execution adapter is enabled.'],
        ]);
    }
}
