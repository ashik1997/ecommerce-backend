<?php

namespace App\Console\Commands;

use App\Services\Hrat\AttendanceSummaryService;
use Illuminate\Console\Command;

class GenerateHratDailySummary extends Command
{
    protected $signature = 'hrat:generate-daily-summary {--date=} {--from=} {--to=} {--employee_id=}';
    protected $description = 'Generate HR attendance daily summaries from raw attendance logs.';

    public function handle(AttendanceSummaryService $service): int
    {
        $employeeId = $this->option('employee_id') ? (int) $this->option('employee_id') : null;

        if ($this->option('from') && $this->option('to')) {
            $service->generateForRange($this->option('from'), $this->option('to'), $employeeId);
            $this->info('Attendance summaries generated for range.');
            return self::SUCCESS;
        }

        $service->generateForDate($this->option('date') ?: now()->toDateString(), $employeeId);
        $this->info('Attendance summary generated.');

        return self::SUCCESS;
    }
}
