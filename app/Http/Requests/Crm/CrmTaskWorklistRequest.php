<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmTaskService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmTaskWorklistRequest extends FormRequest
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
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
            'due_from' => $this->input('due_from') ?: null,
            'due_to' => $this->input('due_to') ?: null,
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
            'priority' => ['nullable', Rule::in(CrmTaskService::PRIORITIES)],
            'status' => ['nullable', Rule::in(array_merge(CrmTaskService::STATUSES, ['overdue']))],
            'due_from' => ['nullable', 'date_format:Y-m-d'],
            'due_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_from'],
        ];
    }
}
