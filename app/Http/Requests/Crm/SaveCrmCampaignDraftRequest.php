<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmCampaignDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SaveCrmCampaignDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
            'planned_channel' => is_string($this->input('planned_channel')) && trim($this->input('planned_channel')) !== '' ? trim($this->input('planned_channel')) : null,
            'subject' => is_string($this->input('subject')) ? trim($this->input('subject')) : $this->input('subject'),
            'message_body' => is_string($this->input('message_body')) ? trim($this->input('message_body')) : $this->input('message_body'),
            'visibility' => is_string($this->input('visibility')) ? trim($this->input('visibility')) : $this->input('visibility'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'planned_channel' => ['nullable', Rule::in(CrmCampaignDraftService::ACTIVE_PLANNED_CHANNELS)],
            'subject' => ['nullable', 'string', 'max:255'],
            'message_body' => ['nullable', 'string', 'max:10000'],
            'audience_saved_segment_id' => ['required', 'integer', 'min:1'],
            'visibility' => ['required', Rule::in(CrmCampaignDraftService::VISIBILITIES)],
        ];
    }
}
