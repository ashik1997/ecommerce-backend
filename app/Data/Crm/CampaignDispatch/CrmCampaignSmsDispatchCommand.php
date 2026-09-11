<?php

namespace App\Data\Crm\CampaignDispatch;

class CrmCampaignSmsDispatchCommand
{
    public function __construct(
        protected BulkSmsBdCampaignGatewayConfig $gateway,
        protected string $destination,
        protected string $message
    ) {
    }

    public function gateway(): BulkSmsBdCampaignGatewayConfig
    {
        return $this->gateway;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public function message(): string
    {
        return $this->message;
    }
}
