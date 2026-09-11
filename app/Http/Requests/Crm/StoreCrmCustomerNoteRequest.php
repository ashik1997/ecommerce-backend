<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrmCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => is_string($this->note) ? trim($this->note) : $this->note,
            'is_private' => filter_var($this->input('is_private', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'note' => ['bail', 'required', 'string', 'max:5000'],
            'is_private' => ['required', 'boolean'],
        ];
    }
}
