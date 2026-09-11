<?php

namespace App\Data\Crm\CampaignDispatch;

/**
 * Provider-neutral future CRM Email result shape. Raw SMTP exception messages,
 * recipients, payloads, credentials, and transport configuration must never be
 * stored in or returned through this DTO.
 */
class CrmCampaignEmailDispatchResult
{
    public function __construct(
        protected string $status,
        protected ?string $failureCategory = null,
        protected ?string $failureSummary = null,
        protected ?string $providerMessageId = null
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

    public function terminalEventType(): string
    {
        return match ($this->status) {
            'succeeded' => 'recipient_succeeded',
            'failed' => 'recipient_failed',
            default => 'recipient_unknown',
        };
    }
}
