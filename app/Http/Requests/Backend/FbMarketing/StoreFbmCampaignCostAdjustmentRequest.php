<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmCampaignCostAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper(trim((string) $this->input('currency', 'BDT'))),
            'cost_type' => strtolower(trim((string) $this->input('cost_type', 'other'))),
            'status' => strtolower(trim((string) $this->input('status', 'approved'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'effective_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'ad_set_id' => ['nullable', 'integer', 'min:1'],
            'ad_id' => ['nullable', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'cost_type' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:160'],
            'base_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'vat_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'service_charge_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', 'in:approved,pending,void'],
            'safe_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
