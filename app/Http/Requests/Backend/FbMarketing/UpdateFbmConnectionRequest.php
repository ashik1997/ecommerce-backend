<?php

namespace App\Http\Requests\Backend\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFbmConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $connection = $this->route('connection');
        $connectionId = $connection instanceof FbmConnection ? $connection->id : $connection;

        return [
            'connection_name' => ['required', 'string', 'max:120', Rule::unique('fbm_connections', 'connection_name')->ignore($connectionId)],
            'app_id' => ['nullable', 'regex:/^[0-9]+$/', 'max:120'],
            'credential_mode' => ['required', Rule::in(FbmConnection::CREDENTIAL_MODES)],
            'graph_api_version' => ['nullable', 'regex:/^v\d+\.\d+$/', 'max:20', Rule::in(config('fb_marketing.graph_api.allowed_versions', ['v25.0']))],
            'app_secret' => ['nullable', 'string', 'max:10000'],
            'access_token' => ['nullable', 'string', 'max:20000'],
            'capi_access_token' => ['nullable', 'string', 'max:20000'],
            'capi_test_event_code' => ['nullable', 'string', 'max:10000'],
            'webhook_verify_token' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
