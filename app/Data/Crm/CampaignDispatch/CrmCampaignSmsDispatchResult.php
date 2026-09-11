<?php

namespace App\Data\Crm\CampaignDispatch;

class CrmCampaignSmsDispatchResult
{
    public function __construct(
        protected string $status,
        protected ?string $failureCategory = null,
        protected ?string $failureSummary = null,
        protected ?string $providerMessageId = null,
        protected ?string $providerResponseCode = null,
        protected ?string $redactedResponseSummary = null,
        protected ?string $responseHash = null
    ) {
    }

    public function status(): string
    {
        return $this->status;
    }

    public function failureCategory(): ?string
    {
        return $this->failureCategory;
    }

    public function failureSummary(): ?string
    {
        return $this->failureSummary;
    }

    public function providerMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    public function providerResponseCode(): ?string
    {
        return $this->providerResponseCode;
    }

    public function redactedResponseSummary(): ?string
    {
        return $this->redactedResponseSummary;
    }

    public function responseHash(): ?string
    {
        return $this->responseHash;
    }

    public function terminalEventType(): string
    {
        return match ($this->status) {
            'succeeded' => 'recipient_succeeded',
            'failed' => 'recipient_failed',
            default => 'recipient_unknown',
        };
    }
}
