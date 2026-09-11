<?php

namespace App\Services\Crm\CampaignDispatch;

use App\Data\Crm\CampaignDispatch\EmailCampaignSmtpConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Dormant execution-only secret resolver for a later bounded CRM Email send
 * stage. Stage 30 deliberately does not call this service from controllers,
 * readiness adapters, browser payloads, or any mail transport.
 */
class EmailCampaignSmtpExecutionConfigResolver
{
    public function __construct(protected EmailCampaignSmtpProtocol $protocol)
    {
    }

    public function executionConfig(?int $productWebsiteId): EmailCampaignSmtpConfig
    {
        $requiredColumns = [
            'id', 'product_website_id', 'host', 'port', 'email', 'password',
            'mail_from_name', 'mail_from_email', 'encryption', 'status',
        ];
        if (!Schema::hasTable('email_configures')) {
            $this->throwValidation('smtp', 'SMTP configuration storage is unavailable.');
        }
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('email_configures', $column)) {
                $this->throwValidation('smtp', 'SMTP configuration storage is incomplete.');
            }
        }

        $candidates = DB::table('email_configures')
            ->where('status', 1)
            ->get([
                'id', 'product_website_id', 'host', 'port', 'email', 'password',
                'mail_from_name', 'mail_from_email', 'encryption',
            ]);

        $exact = $candidates->filter(function ($candidate) use ($productWebsiteId) {
            if ($productWebsiteId === null) {
                return $candidate->product_website_id === null;
            }

            return is_numeric($candidate->product_website_id)
                && (int) $candidate->product_website_id === $productWebsiteId;
        })->values();
        $applicationWide = $candidates->filter(fn ($candidate) => $candidate->product_website_id === null)->values();

        $smtp = null;
        if ($exact->count() === 1) {
            $smtp = $exact->first();
        } elseif ($exact->count() > 1) {
            $this->throwValidation('smtp', 'Multiple active website-scoped SMTP configurations exist. Resolve the ambiguous configuration first.');
        } elseif ($productWebsiteId !== null && $applicationWide->count() === 1) {
            $smtp = $applicationWide->first();
        } elseif ($applicationWide->count() > 1) {
            $this->throwValidation('smtp', 'Multiple active application-wide SMTP configurations exist. Resolve the ambiguous configuration first.');
        } elseif ($candidates->count() > 0) {
            $this->throwValidation('smtp', 'The active SMTP configuration website scope does not match this campaign.');
        }

        if (!$smtp) {
            $this->throwValidation('smtp', 'No active SMTP configuration is available for this campaign.');
        }

        $host = trim((string) $smtp->host);
        $username = trim((string) $smtp->email);
        $password = (string) $smtp->password;
        $port = filter_var($smtp->port, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        if ($host === '' || preg_match('/[\r\n]/', $host) === 1) {
            $this->throwValidation('smtp', 'The server-side SMTP host is invalid.');
        }
        if ($port === false) {
            $this->throwValidation('smtp', 'The server-side SMTP port must be between 1 and 65535.');
        }
        if ($username === '' || preg_match('/[\r\n]/', $username) === 1 || trim($password) === '') {
            $this->throwValidation('smtp', 'The server-side SMTP credential configuration is incomplete.');
        }

        $fromName = trim((string) ($smtp->mail_from_name ?? ''));
        if ($fromName === '') {
            $fromName = trim((string) config('app.name', ''));
        }
        $fromEmail = trim((string) ($smtp->mail_from_email ?? ''));
        if ($fromEmail === '') {
            $fromEmail = $username;
        }

        $encryption = $this->protocol->mapEncryptionMode($smtp->encryption);
        $this->protocol->validateSenderMetadata($fromName, $fromEmail);

        return new EmailCampaignSmtpConfig(
            $host,
            (int) $port,
            $username,
            $password,
            $encryption,
            $fromName,
            strtolower($fromEmail)
        );
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
