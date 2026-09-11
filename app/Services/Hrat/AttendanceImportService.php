<?php

namespace App\Services\Hrat;

use App\Models\Hrat\AttendanceLog;
use App\Models\Hrat\ImportBatch;
use App\Models\Hrat\ImportFailedRow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AttendanceImportService
{
    public function __construct(private AttendanceLogService $logService)
    {
    }

    public function preview(UploadedFile $file): array
    {
        return $this->parseFile($file, 50);
    }

    public function import(UploadedFile $file): ImportBatch
    {
        $path = $file->store('uploads/hrat_attendance_imports', 'public');
        $rows = $this->parseFile($file);

        return DB::transaction(function () use ($file, $path, $rows) {
            $batch = ImportBatch::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'source' => 'csv',
                'total_rows' => count($rows),
                'status' => 'processing',
                'uploaded_by' => auth()->id(),
            ]);

            $success = 0;
            $failed = 0;
            $duplicates = 0;

            foreach ($rows as $row) {
                if (!$row['valid']) {
                    $failed++;
                    $this->failed($batch, $row);
                    continue;
                }

                $exists = AttendanceLog::where('employee_id', $row['employee_id'])
                    ->where('attendance_datetime', $row['attendance_datetime'])
                    ->where('punch_type', $row['punch_type'])
                    ->exists();

                if ($exists) {
                    $duplicates++;
                    continue;
                }

                try {
                    $this->logService->create([
                        'employee_id' => $row['employee_id'],
                        'employee_code' => $row['employee_code'],
                        'attendance_date' => $row['attendance_date'],
                        'attendance_time' => $row['attendance_time'],
                        'punch_type' => $row['punch_type'],
                        'source' => 'csv',
                        'device_id' => $row['device_id'] ?? null,
                        'import_batch_id' => $batch->id,
                        'note' => 'CSV import batch #' . $batch->id,
                        'created_by' => auth()->id(),
                    ], false);
                    $success++;
                } catch (\Throwable $e) {
                    $failed++;
                    $row['error'] = $e->getMessage();
                    $this->failed($batch, $row);
                }
            }

            $batch->update([
                'success_rows' => $success,
                'failed_rows' => $failed,
                'duplicate_rows' => $duplicates,
                'status' => 'completed',
            ]);

            return $batch;
        });
    }

    private function parseFile(UploadedFile $file, ?int $limit = null): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($item) => strtolower(trim($item)), $headers);
        $rows = [];
        $rowNumber = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($limit && count($rows) >= $limit) {
                break;
            }
            $raw = array_combine($headers, array_pad($line, count($headers), null));
            $rows[] = $this->normalizeRow($raw ?: [], $rowNumber);
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeRow(array $raw, int $rowNumber): array
    {
        $employeeCode = trim((string) ($raw['employee_code'] ?? $raw['code'] ?? ''));
        $date = trim((string) ($raw['attendance_date'] ?? $raw['date'] ?? ''));
        $time = trim((string) ($raw['attendance_time'] ?? $raw['time'] ?? ''));
        $punchType = $this->normalizePunchType($raw['punch_type'] ?? $raw['type'] ?? '');
        $employee = $this->findEmployee($employeeCode);

        $row = [
            'row_number' => $rowNumber,
            'raw' => $raw,
            'employee_code' => $employeeCode,
            'employee_id' => $employee->id ?? null,
            'attendance_date' => null,
            'attendance_time' => null,
            'attendance_datetime' => null,
            'punch_type' => $punchType,
            'device_id' => $raw['device_id'] ?? null,
            'valid' => false,
            'error' => null,
        ];

        try {
            if (!$employee) {
                throw new \Exception('Employee code not found.');
            }
            if (!$punchType) {
                throw new \Exception('Invalid punch type.');
            }

            $dateValue = Carbon::parse($date)->toDateString();
            $timeValue = Carbon::parse($time)->format('H:i:s');
            $row['attendance_date'] = $dateValue;
            $row['attendance_time'] = $timeValue;
            $row['attendance_datetime'] = Carbon::parse($dateValue . ' ' . $timeValue);
            $row['valid'] = true;
        } catch (\Throwable $e) {
            $row['error'] = $e->getMessage();
        }

        return $row;
    }

    private function normalizePunchType($value): ?string
    {
        $value = strtolower(trim((string) $value));
        if (in_array($value, ['in', 'entry', 'enter', '0'], true)) {
            return 'entry';
        }
        if (in_array($value, ['out', 'exit', '1'], true)) {
            return 'exit';
        }

        return null;
    }

    private function findEmployee(string $code): ?User
    {
        if ($code === '') {
            return null;
        }

        return User::where('id', $code)
            ->orWhere('email', $code)
            ->orWhere('phone', $code)
            ->first();
    }

    private function failed(ImportBatch $batch, array $row): void
    {
        ImportFailedRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => $row['row_number'],
            'row_data' => $row['raw'] ?? $row,
            'error_message' => $row['error'] ?? 'Import failed.',
        ]);
    }
}
