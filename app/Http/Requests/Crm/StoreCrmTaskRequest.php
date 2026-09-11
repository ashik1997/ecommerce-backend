<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->title) ? trim($this->title) : $this->title,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
            'priority' => $this->input('priority') ?: null,
            'status' => $this->input('status') ?: 'pending',
            'due_at' => $this->input('due_at') ?: null,
            'customer_id' => $this->input('customer_id') ?: null,
            'assigned_user_id' => $this->input('assigned_user_id') ?: null,
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
            'title' => ['bail', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'status' => ['required', Rule::in(['pending', 'in_progress'])],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
