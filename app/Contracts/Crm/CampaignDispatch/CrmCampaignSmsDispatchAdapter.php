<?php

namespace App\Contracts\Crm\CampaignDispatch;

use App\Data\Crm\CampaignDispatch\CrmCampaignSmsDispatchCommand;
use App\Data\Crm\CampaignDispatch\CrmCampaignSmsDispatchResult;

interface CrmCampaignSmsDispatchAdapter
{
    public function channel(): string;

    public function providerKey(): string;

    public function supports(string $channel, string $providerKey): bool;

    /**
     * Execute exactly one hardened SMS provider request for one immutable
     * recipient command. Implementations must never persist or log plaintext
     * destinations, credentials, raw request bodies, or raw response bodies.
     */
    public function dispatch(CrmCampaignSmsDispatchCommand $command): CrmCampaignSmsDispatchResult;
}
