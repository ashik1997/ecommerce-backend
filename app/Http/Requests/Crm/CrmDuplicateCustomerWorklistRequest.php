<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CrmDuplicateCustomerWorklistRequest extends FormRequest
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
            'lifecycle_stage' => is_string($this->lifecycle_stage) && trim($this->lifecycle_stage) !== '' ? trim($this->lifecycle_stage) : null,
            'match_type' => is_string($this->match_type) && trim($this->match_type) !== '' ? trim($this->match_type) : null,
            'candidate_only' => $this->boolean('candidate_only', true),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'assigned_user_id' => ['nullable', 'integer', 'min:1'],
            'lifecycle_stage' => ['nullable', 'string', 'max:40'],
            'match_type' => ['nullable', 'in:any,phone,email'],
            'candidate_only' => ['nullable', 'boolean'],
        ];
    }
}
