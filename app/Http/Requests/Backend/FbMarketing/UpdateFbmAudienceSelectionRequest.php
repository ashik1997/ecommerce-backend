<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFbmAudienceSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_selected' => ['nullable', 'boolean'],
            'planned_use' => ['nullable', 'string', 'max:120'],
            'consent_note' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
