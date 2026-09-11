<?php

namespace App\Http\Requests\Hrat;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceConfigRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'office_entry_time' => ['required', 'date_format:H:i'],
            'entry_safe_time' => ['required', 'date_format:H:i', 'after_or_equal:office_entry_time'],
            'office_exit_time' => ['required', 'date_format:H:i'],
            'early_exit_safe_time' => ['required', 'date_format:H:i', 'before_or_equal:office_exit_time'],
            'overtime_after_minutes' => ['required', 'numeric', 'min:0'],
            'overtime_calculation_method' => ['required', 'in:from_exit_time,from_threshold_time'],
            'late_calculation_method' => ['required', 'in:from_entry_time,from_safe_time'],
            'month_start_day' => ['required', 'integer', 'between:1,31'],
            'month_end_day' => ['nullable', 'integer', 'between:1,31'],
            'entry_button_start_time' => ['required', 'date_format:H:i'],
            'entry_button_end_time' => ['required', 'date_format:H:i'],
            'exit_button_start_time' => ['required', 'date_format:H:i'],
            'exit_button_end_time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'working_days' => ['nullable', 'array'],
            'weekly_holidays' => ['nullable', 'array'],
        ];
    }
}
