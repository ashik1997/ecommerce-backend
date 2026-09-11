<?php

namespace App\Data\Crm\CampaignDispatch;

class BulkSmsBdCampaignGatewayConfig
{
    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected string $senderId
    ) {
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function senderId(): string
    {
        return $this->senderId;
    }
}
