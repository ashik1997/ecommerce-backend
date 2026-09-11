<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CrmActivityWorklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'performed_by' => $this->input('performed_by') ?: null,
            'activity_type' => is_string($this->activity_type) && trim($this->activity_type) !== '' ? trim($this->activity_type) : null,
            'source_module' => is_string($this->source_module) && trim($this->source_module) !== '' ? trim($this->source_module) : null,
            'occurred_from' => $this->input('occurred_from') ?: null,
            'occurred_to' => $this->input('occurred_to') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'performed_by' => ['nullable', 'integer', 'min:1'],
            'activity_type' => ['nullable', 'string', 'max:50'],
            'source_module' => ['nullable', 'string', 'max:80'],
            'occurred_from' => ['nullable', 'date_format:Y-m-d'],
            'occurred_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:occurred_from'],
        ];
    }
}
