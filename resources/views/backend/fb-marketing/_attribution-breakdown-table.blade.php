@if (empty($rows))
    <p class="text-muted mb-0">No rows available.</p>
@else
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead><tr><th>Group</th><th>Orders</th><th>Revenue</th></tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>{{ $money($row['revenue']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
