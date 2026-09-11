<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmLeadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmLeadRequest extends FormRequest
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
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'company_name' => is_string($this->company_name) ? trim($this->company_name) : $this->company_name,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'email' => is_string($this->email) ? trim($this->email) : $this->email,
            'source' => is_string($this->source) ? trim($this->source) : $this->source,
            'priority' => is_string($this->priority) ? trim($this->priority) : $this->priority,
            'score' => $this->input('score') === null || $this->input('score') === '' ? 0 : $this->input('score'),
            'estimated_value' => $this->input('estimated_value') === '' ? null : $this->input('estimated_value'),
            'next_follow_up_at' => $this->input('next_follow_up_at') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', Rule::in(CrmLeadService::PRIORITIES)],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'requirement' => ['nullable', 'string', 'max:5000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ];
    }
}
