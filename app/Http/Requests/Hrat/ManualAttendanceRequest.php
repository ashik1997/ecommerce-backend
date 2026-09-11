<?php

namespace App\Http\Requests\Hrat;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'attendance_date' => ['required', 'date'],
            'attendance_time' => ['required', 'date_format:H:i'],
            'punch_type' => ['required', 'in:entry,exit'],
            'note' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
