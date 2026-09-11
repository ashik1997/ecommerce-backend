<?php

namespace App\Services\Crm\CampaignDispatch;

use App\Contracts\Crm\CampaignDispatch\CrmCampaignDispatchProviderAdapter;

class EmailCampaignDispatchReadinessAdapter implements CrmCampaignDispatchProviderAdapter
{
    public function __construct(
        protected EmailCampaignSmtpReadinessResolver $smtpResolver,
        protected EmailCampaignSmtpProtocol $protocol
    ) {
    }

    public function channel(): string
    {
        return 'email';
    }

    public function providerKey(): string
    {
        return EmailCampaignSmtpReadinessResolver::PROVIDER_KEY;
    }

    public function supportsChannel(string $channel): bool
    {
        return strtolower(trim($channel)) === $this->channel();
    }

    public function readiness(?int $productWebsiteId): array
    {
        $smtp = $this->smtpResolver->readiness($productWebsiteId);
        $protocol = $this->protocol->readiness();
        $checks = array_merge($smtp['checks'], $protocol['checks']);
        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'channel' => $this->channel(),
            'provider_key' => $this->providerKey(),
            'ready' => $ready,
            'ready_label' => $ready ? 'Local SMTP and future Email policy prerequisites are configured' : 'Local SMTP or future Email policy prerequisites are incomplete',
            'message' => $ready
                ? 'Local application SMTP prerequisites and non-sending Email protocol-hardening checks passed. No remote SMTP connectivity probe or mail transport call was performed. Real CRM Email dispatch remains unavailable until a separate bounded execution stage is implemented.'
                : 'Resolve the failed local SMTP or policy checks before a future bounded CRM Email execution stage is enabled. Campaign planning and independent approval remain available.',
            'checks' => $checks,
            'local_configuration_only' => true,
            'protocol_hardening_ready' => !empty($protocol['ready']),
            'execution_transport_available' => false,
            'remote_connectivity_checked' => false,
            'credential_fields_exposed' => false,
            'provider_endpoint_exposed' => false,
            'raw_gateway_record_exposed' => false,
            'provider_payload_exposed' => false,
            'provider_response_exposed' => false,
            'password_selected' => false,
            'planning_available' => true,
            'approval_available' => true,
            'future_recipient_cap' => $protocol['future_recipient_cap'],
            'future_max_subject_length' => $protocol['future_max_subject_length'],
            'future_max_message_body_length' => $protocol['future_max_message_body_length'],
            'future_max_sender_name_length' => $protocol['future_max_sender_name_length'],
            'future_encryption_modes' => $protocol['future_encryption_modes'],
            'future_result_categories' => $protocol['future_result_categories'],
            'single_recipient_requests_only' => true,
            'automatic_retry_available' => false,
            'browser_resend_available' => false,
            'send_available' => false,
            'execute_available' => false,
        ];
    }
}
