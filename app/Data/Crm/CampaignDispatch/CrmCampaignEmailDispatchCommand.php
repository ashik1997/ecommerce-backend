<?php

namespace App\Data\Crm\CampaignDispatch;

/**
 * Future single-recipient CRM Email command. Stage 30 deliberately does not
 * register an execution adapter or invoke a mail transport with this DTO.
 */
class CrmCampaignEmailDispatchCommand
{
    public function __construct(
        protected EmailCampaignSmtpConfig $smtp,
        protected string $destination,
        protected string $subject,
        protected string $messageBody
    ) {
    }

    public function smtp(): EmailCampaignSmtpConfig
    {
        return $this->smtp;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function messageBody(): string
    {
        return $this->messageBody;
    }
}
