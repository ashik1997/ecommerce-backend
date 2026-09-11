<?php

namespace App\Http\Requests\Backend\FbMarketing;

class CreateFbmReportExportRequest extends FbMarketingReportCenterRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'format' => ['nullable', 'in:csv'],
        ]);
    }
}
