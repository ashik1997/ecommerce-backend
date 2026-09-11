<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmLeadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmLeadPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'assigned_user_id' => $this->input('assigned_user_id') ?: null,
            'priority' => is_string($this->priority) ? trim($this->priority) : $this->priority,
            'source' => is_string($this->source) ? trim($this->source) : $this->source,
            'follow_up_from' => $this->input('follow_up_from') ?: null,
            'follow_up_to' => $this->input('follow_up_to') ?: null,
            'search' => is_string($this->search) ? trim($this->search) : $this->search,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'priority' => ['nullable', Rule::in(CrmLeadService::PRIORITIES)],
            'source' => ['nullable', Rule::in(CrmLeadService::SOURCES)],
            'follow_up_from' => ['nullable', 'date'],
            'follow_up_to' => ['nullable', 'date', 'after_or_equal:follow_up_from'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
