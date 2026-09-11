<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmTaskService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmTaskCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start' => $this->input('start') ?: null,
            'end' => $this->input('end') ?: null,
            'customer_id' => $this->input('customer_id') ?: null,
            'assigned_user_id' => $this->input('assigned_user_id') ?: null,
            'priority' => is_string($this->priority) ? trim($this->priority) : $this->priority,
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
        ]);
    }

    public function rules(): array
    {
        return [
            'start' => ['bail', 'required', 'date_format:Y-m-d'],
            'end' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:start'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'priority' => ['nullable', Rule::in(CrmTaskService::PRIORITIES)],
            'status' => ['nullable', Rule::in(array_merge(CrmTaskService::STATUSES, ['overdue']))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('start') || $validator->errors()->has('end')) {
                return;
            }

            $start = Carbon::createFromFormat('Y-m-d', (string) $this->input('start'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', (string) $this->input('end'))->startOfDay();

            if ($start->diffInDays($end) > 92) {
                $validator->errors()->add('end', 'The calendar date window may not exceed 93 days.');
            }
        });
    }
}
