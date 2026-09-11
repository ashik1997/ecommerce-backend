<?php

namespace App\Services\Crm\CampaignDispatch;

use App\Contracts\Crm\CampaignDispatch\CrmCampaignSmsDispatchAdapter;
use App\Data\Crm\CampaignDispatch\CrmCampaignSmsDispatchCommand;
use App\Data\Crm\CampaignDispatch\CrmCampaignSmsDispatchResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BulkSmsBdCampaignSmsDispatchAdapter implements CrmCampaignSmsDispatchAdapter
{
    public function __construct(protected BulkSmsBdCampaignSmsProtocol $protocol)
    {
    }

    public function channel(): string
    {
        return 'sms';
    }

    public function providerKey(): string
    {
        return BulkSmsBdCampaignGatewayResolver::PROVIDER_KEY;
    }

    public function supports(string $channel, string $providerKey): bool
    {
        return strtolower(trim($channel)) === $this->channel()
            && strtolower(trim($providerKey)) === $this->providerKey();
    }

    public function dispatch(CrmCampaignSmsDispatchCommand $command): CrmCampaignSmsDispatchResult
    {
        $payload = null;
        try {
            $gateway = $command->gateway();
            $payload = $this->protocol->buildSingleRecipientPostPayload(
                $gateway->apiKey(),
                $gateway->senderId(),
                $command->destination(),
                $command->message()
            );

            $response = Http::withOptions(['allow_redirects' => false])
                ->asForm()
                ->connectTimeout(BulkSmsBdCampaignSmsProtocol::CONNECT_TIMEOUT_SECONDS)
                ->timeout(BulkSmsBdCampaignSmsProtocol::TOTAL_TIMEOUT_SECONDS)
                ->post($gateway->endpoint(), $payload);

            $rawBody = (string) $response->body();
            $decoded = json_decode($rawBody, true);
            $sensitiveValues = [
                $gateway->apiKey(),
                $gateway->senderId(),
                $gateway->endpoint(),
                $payload['number'] ?? null,
                $command->message(),
            ];
            $redacted = $this->protocol->redactRawProviderResponse($rawBody, $decoded, $sensitiveValues);
            $classification = $this->protocol->classifyProviderResponse($decoded, $response->status());
            $category = $classification['failure_category'] ?? null;

            return new CrmCampaignSmsDispatchResult(
                (string) ($classification['status'] ?? 'unknown'),
                $category,
                $category ? $this->protocol->safeFailureSummary((string) $category) : null,
                $this->protocol->providerMessageId($decoded, $sensitiveValues),
                $this->protocol->providerResponseCode($decoded, $sensitiveValues),
                (string) ($redacted['redacted_response_summary'] ?? ''),
                (string) ($redacted['response_hash'] ?? '')
            );
        } catch (ConnectionException $exception) {
            $category = $this->isTimeout($exception) ? 'timeout_unknown' : 'transport_failure';

            return new CrmCampaignSmsDispatchResult(
                $category === 'timeout_unknown' ? 'unknown' : 'failed',
                $category,
                $this->protocol->safeFailureSummary($category)
            );
        } catch (\Throwable $exception) {
            return new CrmCampaignSmsDispatchResult(
                'unknown',
                'internal_unknown',
                $this->protocol->safeFailureSummary('internal_unknown')
            );
        } finally {
            unset($payload);
        }
    }

    protected function isTimeout(ConnectionException $exception): bool
    {
        return preg_match('/(?:timed?\s*out|timeout|cURL\s+error\s+28)/i', $exception->getMessage()) === 1;
    }
}
