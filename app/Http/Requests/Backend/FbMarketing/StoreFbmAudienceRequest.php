<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmAudienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'audience_type' => strtolower(trim((string) $this->input('audience_type', 'saved'))),
            'status' => strtolower(trim((string) $this->input('status', 'draft'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'audience_type' => ['required', 'in:saved,custom,lookalike,local_segment'],
            'audience_name' => ['required', 'string', 'max:255'],
            'subtype' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'approximate_count' => ['nullable', 'integer', 'min:0', 'max:4000000000'],
            'status' => ['required', 'in:draft,ready,paused,archived'],
            'planned_use' => ['nullable', 'string', 'max:120'],
            'consent_basis' => ['nullable', 'string', 'max:120'],
            'consent_note' => ['nullable', 'string', 'max:500'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
