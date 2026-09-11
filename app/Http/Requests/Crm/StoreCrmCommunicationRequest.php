<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmCommunicationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'channel' => is_string($this->channel) ? trim($this->channel) : $this->channel,
            'direction' => is_string($this->direction) ? trim($this->direction) : $this->direction,
            'subject' => is_string($this->subject) ? trim($this->subject) : $this->subject,
            'message' => is_string($this->message) ? trim($this->message) : $this->message,
            'interaction_at' => $this->input('interaction_at') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['bail', 'required', 'integer', Rule::exists('customers', 'id')],
            'channel' => ['bail', 'required', Rule::in(CrmCommunicationService::MANUAL_CHANNELS)],
            'direction' => ['bail', 'required', Rule::in(CrmCommunicationService::DIRECTIONS)],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['bail', 'required', 'string', 'max:5000'],
            'interaction_at' => ['nullable', 'date'],
        ];
    }
}
