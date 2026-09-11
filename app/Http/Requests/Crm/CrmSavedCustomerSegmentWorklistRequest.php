<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmSavedCustomerSegmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrmSavedCustomerSegmentWorklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->input('status')) && trim($this->input('status')) !== '' ? trim($this->input('status')) : null,
            'visibility' => is_string($this->input('visibility')) && trim($this->input('visibility')) !== '' ? trim($this->input('visibility')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(CrmSavedCustomerSegmentService::STATUSES)],
            'visibility' => ['nullable', Rule::in(CrmSavedCustomerSegmentService::VISIBILITIES)],
        ];
    }
}
