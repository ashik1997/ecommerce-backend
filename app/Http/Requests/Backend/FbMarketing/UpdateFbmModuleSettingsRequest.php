<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFbmModuleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feed_enabled' => ['required', 'boolean'],
            'feed_cache_ttl_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'scheduled_sync_enabled' => ['required', 'boolean'],
            'scheduled_sync_interval_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'landing_attribution_enabled' => ['sometimes', 'boolean'],
            'landing_attribution_retention_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'browser_pixel_mode' => ['sometimes', 'string', 'in:disabled,dry_run,live'],
            'server_capi_mode' => ['sometimes', 'string', 'in:disabled,dry_run,test,live'],
        ];
    }
}
