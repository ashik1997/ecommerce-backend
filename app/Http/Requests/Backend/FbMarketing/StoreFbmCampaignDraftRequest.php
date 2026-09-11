<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmCampaignDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'objective' => strtoupper(trim((string) $this->input('objective', 'OUTCOME_SALES'))),
            'budget_type' => strtolower(trim((string) $this->input('budget_type', 'daily'))),
            'special_ad_categories' => array_values(array_filter((array) $this->input('special_ad_categories', []))),
        ]);
    }

    public function rules(): array
    {
        return [
            'fbm_connection_id' => ['nullable', 'integer', 'min:1'],
            'fbm_ad_account_id' => ['nullable', 'integer', 'min:1'],
            'fbm_page_id' => ['nullable', 'integer', 'min:1'],
            'draft_name' => ['required', 'string', 'max:255'],
            'objective' => ['required', 'in:OUTCOME_SALES,OUTCOME_TRAFFIC,OUTCOME_ENGAGEMENT,OUTCOME_LEADS,OUTCOME_AWARENESS,OUTCOME_APP_PROMOTION'],
            'special_ad_categories' => ['array', 'max:4'],
            'special_ad_categories.*' => ['string', 'in:NONE,CREDIT,EMPLOYMENT,HOUSING,ISSUES_ELECTIONS_POLITICS'],
            'budget_type' => ['required', 'in:daily,lifetime'],
            'budget_amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
            'currency' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{3,10}$/'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'optimization_goal' => ['nullable', 'string', 'max:120'],
            'billing_event' => ['nullable', 'string', 'max:120'],
            'destination_url' => ['nullable', 'url', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'utm_medium' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'utm_campaign' => ['nullable', 'string', 'max:160', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'creative_asset_id' => ['nullable', 'integer', 'min:1'],
            'audience_id' => ['nullable', 'integer', 'min:1'],
            'product_set_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
