<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class FbMarketingAudienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'audience_type' => $this->filled('audience_type') ? strtolower(trim((string) $this->input('audience_type'))) : null,
            'status' => $this->filled('status') ? strtolower(trim((string) $this->input('status'))) : null,
            'selection' => $this->filled('selection') ? strtolower(trim((string) $this->input('selection'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'audience_type' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'status' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'selection' => ['nullable', 'in:selected,unselected'],
        ];
    }
}
