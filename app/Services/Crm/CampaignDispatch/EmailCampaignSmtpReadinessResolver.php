<?php

namespace App\Services\Crm\CampaignDispatch;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EmailCampaignSmtpReadinessResolver
{
    public const PROVIDER_KEY = 'smtp';

    /**
     * Resolve safe local application SMTP readiness only. This method deliberately
     * never selects, returns, logs, or exposes the stored SMTP password.
     */
    public function readiness(?int $productWebsiteId): array
    {
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks): void {
            $checks[] = compact('key', 'label', 'passed', 'message');
        };

        $requiredColumns = [
            'id', 'product_website_id', 'host', 'port', 'email', 'password',
            'mail_from_name', 'mail_from_email', 'encryption', 'status',
        ];
        $storageReady = Schema::hasTable('email_configures');
        foreach ($requiredColumns as $column) {
            $storageReady = $storageReady && Schema::hasColumn('email_configures', $column);
        }
        $addCheck(
            'smtp_storage',
            'SMTP configuration storage',
            $storageReady,
            $storageReady ? 'SMTP configuration storage is available.' : 'SMTP configuration storage is unavailable or incomplete.'
        );

        $smtpConfig = null;
        $scopeReady = false;
        $scopeMessage = 'No active SMTP configuration is available.';
        if ($storageReady) {
            try {
                $candidates = DB::table('email_configures')
                    ->where('status', 1)
                    ->get([
                        'id', 'product_website_id', 'host', 'port', 'email',
                        'mail_from_name', 'mail_from_email', 'encryption',
                        DB::raw("CASE WHEN TRIM(COALESCE(password, '')) <> '' THEN 1 ELSE 0 END AS password_present"),
                    ]);

                $exact = $candidates->filter(function ($candidate) use ($productWebsiteId) {
                    if ($productWebsiteId === null) {
                        return $candidate->product_website_id === null;
                    }

                    return is_numeric($candidate->product_website_id)
                        && (int) $candidate->product_website_id === $productWebsiteId;
                })->values();

                $applicationWide = $candidates->filter(fn ($candidate) => $candidate->product_website_id === null)->values();

                if ($exact->count() === 1) {
                    $smtpConfig = $exact->first();
                    $scopeReady = true;
                    $scopeMessage = 'One active website-scoped SMTP configuration is available.';
                } elseif ($exact->count() > 1) {
                    $scopeMessage = 'Multiple active website-scoped SMTP configurations exist. Resolve the ambiguous configuration before enabling a future Email execution stage.';
                } elseif ($productWebsiteId !== null && $applicationWide->count() === 1) {
                    $smtpConfig = $applicationWide->first();
                    $scopeReady = true;
                    $scopeMessage = 'One active application-wide SMTP configuration is available for this website context.';
                } elseif ($applicationWide->count() > 1) {
                    $scopeMessage = 'Multiple active application-wide SMTP configurations exist. Resolve the ambiguous configuration before enabling a future Email execution stage.';
                } elseif ($candidates->count() > 0) {
                    $scopeMessage = 'An active SMTP configuration exists, but its website scope does not match this campaign.';
                }
            } catch (\Throwable $exception) {
                Log::warning('CRM Email campaign local SMTP readiness query failed.', [
                    'exception_class' => get_class($exception),
                ]);
                $scopeMessage = 'SMTP configuration could not be inspected safely.';
            }
        }
        $addCheck('smtp_scope', 'SMTP configuration scope', $scopeReady, $scopeMessage);

        $host = trim((string) ($smtpConfig->host ?? ''));
        $hostPresent = $scopeReady && $host !== '' && preg_match('/[\r\n]/', $host) !== 1;
        $addCheck(
            'smtp_host_present',
            'Server-side SMTP host',
            $hostPresent,
            $hostPresent ? 'A server-side SMTP host is configured.' : 'A safe server-side SMTP host is missing.'
        );

        $port = filter_var($smtpConfig->port ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        $portValid = $scopeReady && $port !== false;
        $addCheck(
            'smtp_port_valid',
            'SMTP port',
            $portValid,
            $portValid ? 'The configured SMTP port is valid.' : 'Configure a valid SMTP port between 1 and 65535.'
        );

        $username = trim((string) ($smtpConfig->email ?? ''));
        $usernamePresent = $scopeReady && $username !== '' && preg_match('/[\r\n]/', $username) !== 1;
        $addCheck(
            'smtp_username_present',
            'Server-side SMTP username',
            $usernamePresent,
            $usernamePresent ? 'A server-side SMTP username is configured.' : 'A safe server-side SMTP username is missing.'
        );

        $passwordPresent = $scopeReady && (int) ($smtpConfig->password_present ?? 0) === 1;
        $addCheck(
            'smtp_password_present',
            'Server-side SMTP password',
            $passwordPresent,
            $passwordPresent ? 'A server-side SMTP password is configured and remains hidden.' : 'A server-side SMTP password is missing.'
        );

        $encryption = filter_var($smtpConfig->encryption ?? null, FILTER_VALIDATE_INT);
        $encryptionRecognized = $scopeReady && $encryption !== false && in_array($encryption, [0, 1, 2], true);
        $addCheck(
            'smtp_encryption_recognized',
            'SMTP encryption mode',
            $encryptionRecognized,
            $encryptionRecognized ? 'The configured SMTP encryption mode is recognized.' : 'Configure a recognized SMTP encryption mode: none, TLS, or SSL.'
        );

        $fromEmail = trim((string) ($smtpConfig->mail_from_email ?? ''));
        $effectiveFromEmail = $fromEmail !== '' ? $fromEmail : $username;
        $fromEmailValid = $scopeReady
            && preg_match('/[\r\n]/', $effectiveFromEmail) !== 1
            && filter_var($effectiveFromEmail, FILTER_VALIDATE_EMAIL) !== false;
        $addCheck(
            'smtp_from_email_valid',
            'Future Email sender address',
            $fromEmailValid,
            $fromEmailValid
                ? ($fromEmail === '' ? 'The valid server-side SMTP identity remains the future sender-address fallback.' : 'The configured future sender address is valid.')
                : 'Configure a valid sender address or a valid SMTP-identity fallback without header line breaks.'
        );

        $fromName = trim((string) ($smtpConfig->mail_from_name ?? ''));
        $fallbackName = trim((string) config('app.name', ''));
        $effectiveFromName = $fromName !== '' ? $fromName : $fallbackName;
        $nameLength = function_exists('mb_strlen') ? mb_strlen($effectiveFromName) : strlen($effectiveFromName);
        $fromNameReady = $scopeReady
            && $effectiveFromName !== ''
            && preg_match('/[\r\n]/', $effectiveFromName) !== 1
            && $nameLength <= EmailCampaignSmtpProtocol::MAX_SENDER_NAME_LENGTH;
        $addCheck(
            'smtp_from_name_ready',
            'Future Email sender name',
            $fromNameReady,
            $fromNameReady
                ? ($fromName === '' ? 'The safe application-name fallback remains the future sender name.' : 'A safe future sender name is configured.')
                : 'Configure a non-empty sender-name value without header line breaks and within the server-side length limit.'
        );

        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'ready' => $ready,
            'checks' => $checks,
            'local_configuration_only' => true,
            'remote_connectivity_checked' => false,
            'credential_fields_exposed' => false,
            'provider_endpoint_exposed' => false,
            'raw_gateway_record_exposed' => false,
            'password_selected' => false,
        ];
    }
}
