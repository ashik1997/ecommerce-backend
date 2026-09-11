<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmBoostingJobCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required', 'integer', 'min:1'],
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'ad_set_id' => ['nullable', 'integer', 'min:1'],
            'ad_id' => ['nullable', 'integer', 'min:1'],
            'allocation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'safe_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
