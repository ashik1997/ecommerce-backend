<?php

namespace App\Data\Crm\CampaignDispatch;

/**
 * Execution-only SMTP configuration DTO for a future CRM Email transport.
 * This object contains secrets and must never be serialized, logged, persisted,
 * returned by controllers, or exposed to browser payloads.
 */
class EmailCampaignSmtpConfig
{
    public function __construct(
        protected string $host,
        protected int $port,
        protected string $username,
        protected string $password,
        protected ?string $encryption,
        protected string $fromName,
        protected string $fromEmail
    ) {
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function encryption(): ?string
    {
        return $this->encryption;
    }

    public function fromName(): string
    {
        return $this->fromName;
    }

    public function fromEmail(): string
    {
        return $this->fromEmail;
    }
}
