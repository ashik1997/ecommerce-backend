<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteCrmCampaignDispatchAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmation_phrase' => is_string($this->input('confirmation_phrase'))
                ? trim($this->input('confirmation_phrase'))
                : $this->input('confirmation_phrase'),
        ]);
    }

    public function rules(): array
    {
        return [
            'confirmation_phrase' => ['required', 'string', 'in:SEND SMS'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_phrase.required' => 'Type SEND SMS exactly to confirm the bounded real SMS execution.',
            'confirmation_phrase.in' => 'Type SEND SMS exactly to confirm the bounded real SMS execution.',
        ];
    }
}
