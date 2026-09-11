<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmCampaignOperationalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action_type' => strtolower(trim((string) $this->input('action_type', 'pause'))),
            'target_type' => strtolower(trim((string) $this->input('target_type', 'campaign'))),
            'budget_type' => $this->filled('budget_type') ? strtolower(trim((string) $this->input('budget_type'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'action_type' => ['required', 'in:pause,resume,update_budget,update_schedule,safe_edit'],
            'target_type' => ['required', 'in:campaign,ad_set,ad'],
            'budget_type' => ['nullable', 'in:daily,lifetime'],
            'budget_amount' => ['nullable', 'numeric', 'min:1', 'max:999999999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
