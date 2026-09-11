<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmCustomerTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'slug' => is_string($this->slug) && trim($this->slug) !== '' ? trim($this->slug) : null,
            'color' => is_string($this->color) && trim($this->color) !== '' ? trim($this->color) : null,
            'status' => is_string($this->status) && trim($this->status) !== '' ? trim($this->status) : 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120', Rule::unique('crm_customer_tags', 'name')],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash', Rule::unique('crm_customer_tags', 'slug')],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
