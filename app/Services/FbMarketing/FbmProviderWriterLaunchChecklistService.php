<?php

namespace App\Services\FbMarketing;

class FbmProviderWriterLaunchChecklistService
{
    public function build(array $providerWriterSummary, array $queueReadiness): array
    {
        $items = [
            $this->item(
                'provider_writer_ready',
                'Provider writer readiness',
                (bool) ($providerWriterSummary['ready'] ?? false),
                (string) ($providerWriterSummary['message'] ?? 'Provider writer readiness has not been evaluated.')
            ),
            $this->item(
                'dedicated_queue_ready',
                'Dedicated FB MARKETING queue',
                (bool) ($queueReadiness['ready'] ?? false),
                (string) ($queueReadiness['message'] ?? 'Queue readiness has not been evaluated.')
            ),
            $this->item(
                'writes_disabled_by_default',
                'Live writes disabled by default',
                $this->writesDisabledByDefault(),
                'Both campaign publish and operational action provider writes must stay disabled until signed production approval.'
            ),
            $this->item(
                'writer_scopes_required',
                'Writer scopes required',
                in_array('ads_management', (array) ($providerWriterSummary['required_scopes'] ?? []), true)
                    && in_array('ads_read', (array) ($providerWriterSummary['required_scopes'] ?? []), true),
                'Writer readiness must require ads_read and ads_management from token health metadata.'
            ),
            $this->item(
                'budget_caps_configured',
                'Budget safety caps configured',
                $this->budgetCapsConfigured($providerWriterSummary),
                'Daily and lifetime budget caps must be positive and bounded before live writes.'
            ),
            $this->item(
                'rollback_reconciliation_required',
                'Rollback and reconciliation required',
                (bool) config('fb_marketing.provider_writer.rollback_plan_required', false)
                    && (bool) config('fb_marketing.provider_writer.post_write_reconciliation_required', false),
                'Every provider-write worker result must carry a safe rollback plan and reconciliation summary.'
            ),
            $this->item(
                'broad_targeting_fail_closed',
                'Broad targeting fail-closed',
                (array) config('fb_marketing.provider_writer.default_targeting_countries', []) === [],
                'Default country targeting should remain empty unless a written targeting policy is approved.'
            ),
            $this->item(
                'safe_edit_blocked',
                'Field-level safe edit blocked',
                true,
                'FBM-33 still blocks safe_edit provider writes until a later field allow-list is approved.'
            ),
        ];

        $blocking = array_values(array_filter($items, fn(array $item): bool => !$item['ready']));

        return [
            'status' => $blocking === [] ? 'ready_for_signed_enablement' : 'blocked',
            'ready_for_signed_enablement' => $blocking === [],
            'blocking_count' => count($blocking),
            'items' => $items,
            'message' => $blocking === []
                ? 'Final provider-writer launch gate is clean. Enable live writes only through a signed configuration release.'
                : 'Final provider-writer launch gate is blocked until the listed items are resolved.',
        ];
    }

    private function item(string $key, string $label, bool $ready, string $message): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ready' => $ready,
            'message' => $message,
        ];
    }

    private function writesDisabledByDefault(): bool
    {
        return !(bool) config('fb_marketing.campaign_publish.provider_writes_enabled', false)
            && !(bool) config('fb_marketing.operational_actions.provider_writes_enabled', false);
    }

    private function budgetCapsConfigured(array $providerWriterSummary): bool
    {
        $policy = (array) ($providerWriterSummary['safety_policy'] ?? []);

        return (float) ($policy['max_single_daily_budget_amount'] ?? 0) > 0
            && (float) ($policy['max_single_lifetime_budget_amount'] ?? 0) > 0
            && (float) ($policy['max_single_daily_budget_amount'] ?? 0) <= (float) ($policy['max_single_lifetime_budget_amount'] ?? 0);
    }
}
