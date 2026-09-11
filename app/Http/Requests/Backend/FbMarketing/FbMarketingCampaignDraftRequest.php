<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class FbMarketingCampaignDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->filled('status') ? strtolower(trim((string) $this->input('status'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:draft,submitted,approved,rejected,archived'],
        ];
    }
}
