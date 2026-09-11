<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmLeadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmLeadWorklistRequest extends FormRequest
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
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
            'priority' => is_string($this->priority) ? trim($this->priority) : $this->priority,
            'source' => is_string($this->source) ? trim($this->source) : $this->source,
            'follow_up_from' => $this->input('follow_up_from') ?: null,
            'follow_up_to' => $this->input('follow_up_to') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'status' => ['nullable', Rule::in(CrmLeadService::STATUSES)],
            'priority' => ['nullable', Rule::in(CrmLeadService::PRIORITIES)],
            'source' => ['nullable', Rule::in(CrmLeadService::SOURCES)],
            'follow_up_from' => ['nullable', 'date_format:Y-m-d'],
            'follow_up_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:follow_up_from'],
        ];
    }
}
