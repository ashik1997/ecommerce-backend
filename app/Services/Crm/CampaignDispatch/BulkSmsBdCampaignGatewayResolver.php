<?php

namespace App\Services\Crm\CampaignDispatch;

use App\Data\Crm\CampaignDispatch\BulkSmsBdCampaignGatewayConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BulkSmsBdCampaignGatewayResolver
{
    public const PROVIDER_KEY = 'bulksmsbd';

    /**
     * Resolve local application configuration readiness without returning any secret,
     * endpoint URL, or raw gateway record to a controller or browser payload.
     */
    public function readiness(?int $productWebsiteId): array
    {
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks): void {
            $checks[] = compact('key', 'label', 'passed', 'message');
        };

        $requiredColumns = ['id', 'product_website_id', 'provider_name', 'api_endpoint', 'api_key', 'sender_id', 'status'];
        $storageReady = Schema::hasTable('sms_gateways');
        foreach ($requiredColumns as $column) {
            $storageReady = $storageReady && Schema::hasColumn('sms_gateways', $column);
        }
        $addCheck(
            'gateway_storage',
            'SMS gateway storage',
            $storageReady,
            $storageReady ? 'SMS gateway storage is available.' : 'SMS gateway storage is unavailable or incomplete.'
        );

        $gateway = null;
        $scopeReady = false;
        $scopeMessage = 'No active BulkSMSBD gateway is configured.';
        if ($storageReady) {
            $candidates = DB::table('sms_gateways')
                ->whereRaw('LOWER(TRIM(provider_name)) = ?', [self::PROVIDER_KEY])
                ->where('status', 1)
                ->get(['id', 'product_website_id', 'api_endpoint', 'api_key', 'sender_id']);

            $exact = $candidates->filter(function ($candidate) use ($productWebsiteId) {
                if ($productWebsiteId === null) {
                    return $candidate->product_website_id === null;
                }

                return is_numeric($candidate->product_website_id)
                    && (int) $candidate->product_website_id === $productWebsiteId;
            })->values();

            $applicationWide = $candidates->filter(fn ($candidate) => $candidate->product_website_id === null)->values();

            if ($exact->count() === 1) {
                $gateway = $exact->first();
                $scopeReady = true;
                $scopeMessage = 'One active website-scoped BulkSMSBD gateway is available.';
            } elseif ($exact->count() > 1) {
                $scopeMessage = 'Multiple active website-scoped BulkSMSBD gateways exist. Resolve the ambiguous configuration before preparing an attempt.';
            } elseif ($productWebsiteId !== null && $applicationWide->count() === 1) {
                $gateway = $applicationWide->first();
                $scopeReady = true;
                $scopeMessage = 'One active application-wide BulkSMSBD gateway is available for this website context.';
            } elseif ($applicationWide->count() > 1) {
                $scopeMessage = 'Multiple active application-wide BulkSMSBD gateways exist. Resolve the ambiguous configuration before preparing an attempt.';
            } elseif ($candidates->count() > 0) {
                $scopeMessage = 'An active BulkSMSBD gateway exists, but its website scope does not match this campaign.';
            }
        }
        $addCheck('gateway_scope', 'BulkSMSBD gateway scope', $scopeReady, $scopeMessage);

        $endpoint = $gateway ? trim((string) $gateway->api_endpoint) : '';
        $endpointPresent = $scopeReady && $endpoint !== '';
        $addCheck(
            'endpoint_present',
            'Configured provider endpoint',
            $endpointPresent,
            $endpointPresent ? 'A provider endpoint is configured server-side.' : 'A provider endpoint is missing.'
        );

        $parsedScheme = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME));
        $parsedHost = trim((string) parse_url($endpoint, PHP_URL_HOST));
        $endpointHttps = $endpointPresent && $parsedScheme === 'https' && $parsedHost !== '';
        $addCheck(
            'endpoint_https',
            'HTTPS endpoint policy',
            $endpointHttps,
            $endpointHttps ? 'The configured provider endpoint uses HTTPS.' : 'The configured provider endpoint must be an absolute HTTPS URL before CRM campaign execution can be enabled.'
        );

        $endpointHasNoQueryOrFragment = $endpointHttps
            && parse_url($endpoint, PHP_URL_QUERY) === null
            && parse_url($endpoint, PHP_URL_FRAGMENT) === null;
        $addCheck(
            'endpoint_shape',
            'Provider endpoint shape',
            $endpointHasNoQueryOrFragment,
            $endpointHasNoQueryOrFragment ? 'The configured endpoint contains no query string or fragment.' : 'The configured endpoint must not contain a query string or fragment.'
        );

        $apiKeyPresent = $scopeReady && trim((string) ($gateway->api_key ?? '')) !== '';
        $addCheck(
            'api_key_present',
            'Server-side API key',
            $apiKeyPresent,
            $apiKeyPresent ? 'A server-side API key is configured.' : 'A server-side API key is missing.'
        );

        $senderPresent = $scopeReady && trim((string) ($gateway->sender_id ?? '')) !== '';
        $addCheck(
            'sender_id_present',
            'Server-side sender ID',
            $senderPresent,
            $senderPresent ? 'A server-side sender ID is configured.' : 'A server-side sender ID is missing.'
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
        ];
    }

    /**
     * Resolve one execution-only server-side gateway DTO. Secrets and endpoint
     * data must never be returned by controllers, logs, activities, or browser
     * payloads.
     */
    public function executionConfig(?int $productWebsiteId): BulkSmsBdCampaignGatewayConfig
    {
        $requiredColumns = ['id', 'product_website_id', 'provider_name', 'api_endpoint', 'api_key', 'sender_id', 'status'];
        if (!Schema::hasTable('sms_gateways')) {
            $this->throwValidation('gateway', 'SMS gateway storage is unavailable.');
        }
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('sms_gateways', $column)) {
                $this->throwValidation('gateway', 'SMS gateway storage is incomplete.');
            }
        }

        $candidates = DB::table('sms_gateways')
            ->whereRaw('LOWER(TRIM(provider_name)) = ?', [self::PROVIDER_KEY])
            ->where('status', 1)
            ->get(['id', 'product_website_id', 'api_endpoint', 'api_key', 'sender_id']);

        $exact = $candidates->filter(function ($candidate) use ($productWebsiteId) {
            if ($productWebsiteId === null) {
                return $candidate->product_website_id === null;
            }

            return is_numeric($candidate->product_website_id)
                && (int) $candidate->product_website_id === $productWebsiteId;
        })->values();
        $applicationWide = $candidates->filter(fn ($candidate) => $candidate->product_website_id === null)->values();

        $gateway = null;
        if ($exact->count() === 1) {
            $gateway = $exact->first();
        } elseif ($exact->count() > 1) {
            $this->throwValidation('gateway', 'Multiple active website-scoped BulkSMSBD gateways exist. Resolve the ambiguous configuration first.');
        } elseif ($productWebsiteId !== null && $applicationWide->count() === 1) {
            $gateway = $applicationWide->first();
        } elseif ($applicationWide->count() > 1) {
            $this->throwValidation('gateway', 'Multiple active application-wide BulkSMSBD gateways exist. Resolve the ambiguous configuration first.');
        } elseif ($candidates->count() > 0) {
            $this->throwValidation('gateway', 'The active BulkSMSBD gateway website scope does not match this campaign.');
        }

        if (!$gateway) {
            $this->throwValidation('gateway', 'No active BulkSMSBD gateway is configured for this campaign.');
        }

        $endpoint = trim((string) $gateway->api_endpoint);
        $scheme = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME));
        $host = trim((string) parse_url($endpoint, PHP_URL_HOST));
        if ($endpoint === '' || $scheme !== 'https' || $host === '') {
            $this->throwValidation('gateway', 'The configured BulkSMSBD endpoint must be an absolute HTTPS URL.');
        }
        if (parse_url($endpoint, PHP_URL_QUERY) !== null || parse_url($endpoint, PHP_URL_FRAGMENT) !== null) {
            $this->throwValidation('gateway', 'The configured BulkSMSBD endpoint must not contain a query string or fragment.');
        }

        $apiKey = trim((string) $gateway->api_key);
        $senderId = trim((string) $gateway->sender_id);
        if ($apiKey === '' || $senderId === '') {
            $this->throwValidation('gateway', 'The server-side BulkSMSBD credential configuration is incomplete.');
        }

        return new BulkSmsBdCampaignGatewayConfig($endpoint, $apiKey, $senderId);
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

}
