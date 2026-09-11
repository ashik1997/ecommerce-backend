<?php

namespace App\Http\Requests\Backend\FbMarketing;

use App\Services\FbMarketing\FbmConversionEventReadinessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFbmCapiDiagnosticRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'connection' => ['required', 'integer', 'min:1'],
            'diagnostic_mode' => [
                'required',
                'string',
                Rule::in([
                    FbmConversionEventReadinessService::MODE_DRY_RUN,
                    FbmConversionEventReadinessService::MODE_TEST,
                ]),
            ],
            'confirm_test_delivery' => [
                Rule::requiredIf(fn(): bool => $this->input('diagnostic_mode') === FbmConversionEventReadinessService::MODE_TEST),
                'nullable',
                'accepted',
            ],
        ];
    }
}
