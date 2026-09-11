<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\ImportBatch;
use App\Models\Hrat\ImportFailedRow;
use App\Services\Hrat\AttendanceImportService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class CsvImportController extends Controller
{
    public function index()
    {
        $batches = ImportBatch::latest()->paginate(20);
        return view('backend.hrat.csv.index', compact('batches'));
    }

    public function preview(Request $request, AttendanceImportService $service)
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $previewRows = $service->preview($request->file('csv_file'));
        return view('backend.hrat.csv.preview', compact('previewRows'));
    }

    public function store(Request $request, AttendanceImportService $service)
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $batch = $service->import($request->file('csv_file'));
        Toastr::success("CSV import completed. Success: {$batch->success_rows}, Failed: {$batch->failed_rows}, Duplicates: {$batch->duplicate_rows}", 'Success');
        return redirect()->route('hrat.import-batches.index');
    }

    public function batches()
    {
        $batches = ImportBatch::latest()->paginate(20);
        return view('backend.hrat.csv.batches', compact('batches'));
    }

    public function failedRows($batchId)
    {
        $rows = ImportFailedRow::where('import_batch_id', $batchId)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Row Number', 'Error', 'Row Data']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->row_number, $row->error_message, json_encode($row->row_data)]);
            }
            fclose($out);
        }, 'hrat_failed_rows_' . $batchId . '.csv', ['Content-Type' => 'text/csv']);
    }
}
