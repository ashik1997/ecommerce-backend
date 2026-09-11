<div class="form-group">
    <label>Job</label>
    <select class="form-control" name="job_id" required>
        @foreach ($rows as $row)
            <option value="{{ $row['job_id'] }}">{{ $row['job_code'] }} - {{ $row['title'] }}</option>
        @endforeach
    </select>
</div>
