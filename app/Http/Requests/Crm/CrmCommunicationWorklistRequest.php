<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmCommunicationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmCommunicationWorklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'channel' => is_string($this->channel) && trim($this->channel) !== '' ? trim($this->channel) : null,
            'direction' => is_string($this->direction) && trim($this->direction) !== '' ? trim($this->direction) : null,
            'status' => is_string($this->status) && trim($this->status) !== '' ? trim($this->status) : null,
            'source_module' => is_string($this->source_module) && trim($this->source_module) !== '' ? trim($this->source_module) : null,
            'sent_from' => $this->input('sent_from') ?: null,
            'sent_to' => $this->input('sent_to') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'channel' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', Rule::in(CrmCommunicationService::DIRECTIONS)],
            'status' => ['nullable', 'string', 'max:40'],
            'source_module' => ['nullable', 'string', 'max:80'],
            'sent_from' => ['nullable', 'date_format:Y-m-d'],
            'sent_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:sent_from'],
        ];
    }
}
