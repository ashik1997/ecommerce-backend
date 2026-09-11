<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class FbMarketingCreativeLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asset_type' => $this->filled('asset_type') ? strtolower(trim((string) $this->input('asset_type'))) : null,
            'status' => $this->filled('status') ? strtolower(trim((string) $this->input('status'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'status' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/'],
        ];
    }
}
