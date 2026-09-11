<div class="card">
    <div class="card-body">
        <h4>Import History</h4>
        <table class="table table-bordered table-sm">
            <tr><th>File</th><th>Total</th><th>Success</th><th>Failed</th><th>Duplicate</th><th>Status</th><th>Action</th></tr>
            @foreach ($batches as $batch)
                <tr>
                    <td>{{ $batch->file_name }}</td>
                    <td>{{ $batch->total_rows }}</td>
                    <td>{{ $batch->success_rows }}</td>
                    <td>{{ $batch->failed_rows }}</td>
                    <td>{{ $batch->duplicate_rows }}</td>
                    <td>{{ ucfirst($batch->status) }}</td>
                    <td>
                        @if ($batch->failed_rows > 0)
                            <a href="{{ route('hrat.import-batches.failed-rows', $batch->id) }}" class="btn btn-sm btn-danger">Failed CSV</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        {{ $batches->links() }}
    </div>
</div>
