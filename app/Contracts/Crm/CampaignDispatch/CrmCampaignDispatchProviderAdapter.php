<?php

namespace App\Contracts\Crm\CampaignDispatch;

interface CrmCampaignDispatchProviderAdapter
{
    public function channel(): string;

    public function providerKey(): string;

    public function supportsChannel(string $channel): bool;

    /**
     * Return safe local readiness metadata only. Implementations must not call
     * a remote provider or expose credentials, endpoints, or raw gateway rows.
     */
    public function readiness(?int $productWebsiteId): array;
}
