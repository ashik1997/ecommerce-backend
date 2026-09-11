<?php

namespace Database\Seeders;

use App\Models\Hrat\AttendanceConfig;
use Illuminate\Database\Seeder;

class HratAttendanceConfigSeeder extends Seeder
{
    public function run()
    {
        AttendanceConfig::firstOrCreate(
            ['is_active' => true],
            [
                'office_entry_time' => '10:00:00',
                'entry_safe_time' => '10:15:00',
                'office_exit_time' => '19:00:00',
                'early_exit_safe_time' => '18:55:00',
                'overtime_after_minutes' => 30,
                'overtime_calculation_method' => 'from_exit_time',
                'late_calculation_method' => 'from_entry_time',
                'month_start_day' => 1,
                'entry_button_start_time' => '09:45:00',
                'entry_button_end_time' => '10:15:00',
                'exit_button_start_time' => '18:50:00',
                'exit_button_end_time' => '19:30:00',
                'working_days' => [0, 1, 2, 3, 4, 5],
                'weekly_holidays' => [6],
                'timezone' => 'Asia/Dhaka',
            ]
        );
    }
}
