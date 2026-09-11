<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmCampaignDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmCampaignDraftWorklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->input('status')) && trim($this->input('status')) !== '' ? trim($this->input('status')) : null,
            'visibility' => is_string($this->input('visibility')) && trim($this->input('visibility')) !== '' ? trim($this->input('visibility')) : null,
            'planned_channel' => is_string($this->input('planned_channel')) && trim($this->input('planned_channel')) !== '' ? trim($this->input('planned_channel')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(CrmCampaignDraftService::STATUSES)],
            'visibility' => ['nullable', Rule::in(CrmCampaignDraftService::VISIBILITIES)],
            'planned_channel' => ['nullable', Rule::in(CrmCampaignDraftService::PLANNED_CHANNELS)],
        ];
    }
}
