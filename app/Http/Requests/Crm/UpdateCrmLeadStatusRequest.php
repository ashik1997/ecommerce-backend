<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmLeadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCrmLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
            'customer_id' => $this->input('customer_id') ?: null,
            'lost_reason' => is_string($this->lost_reason) ? trim($this->lost_reason) : $this->lost_reason,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(CrmLeadService::STATUSES)],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'lost_reason' => ['nullable', 'required_if:status,lost', 'string', 'max:2000'],
        ];
    }
}
