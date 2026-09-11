<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmCreativeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asset_type' => strtolower(trim((string) $this->input('asset_type', 'image'))),
            'status' => strtolower(trim((string) $this->input('status', 'draft'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['required', 'in:image,video,carousel,text,product'],
            'title' => ['required', 'string', 'max:180'],
            'primary_text' => ['nullable', 'string', 'max:1000'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'call_to_action' => ['nullable', 'string', 'max:80'],
            'media_file_id' => ['nullable', 'integer', 'min:1'],
            'external_asset_url' => ['nullable', 'url', 'max:1000'],
            'landing_url' => ['nullable', 'url', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'utm_medium' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'utm_campaign' => ['nullable', 'string', 'max:160', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'status' => ['required', 'in:draft,ready,archived'],
        ];
    }
}
