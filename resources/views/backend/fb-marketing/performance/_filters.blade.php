<form method="GET" action="{{ $filterAction }}" class="border rounded p-3 mb-3">
    <div class="row align-items-end">
        <div class="col-md-2 mb-2">
            <label class="mb-1">From date</label>
            <input class="form-control" type="date" name="from_date" value="{{ $performance['filters']['from_date'] }}" required>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">To date</label>
            <input class="form-control" type="date" name="to_date" value="{{ $performance['filters']['to_date'] }}" required>
        </div>
        @if (!empty($showAccountFilter))
            <div class="col-md-3 mb-2">
                <label class="mb-1">Selected Ad Account</label>
                <select class="form-control" name="ad_account_id">
                    <option value="">All selected accounts</option>
                    @foreach ($performance['account_options'] as $account)
                        <option value="{{ $account['id'] }}" {{ (int) ($performance['filters']['ad_account_id'] ?? 0) === (int) $account['id'] ? 'selected' : '' }}>{{ $account['asset_name'] }} · {{ $account['currency'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2 mb-2">
            <label class="mb-1">Status</label>
            <input class="form-control" type="text" name="status" maxlength="80" value="{{ $performance['filters']['status'] }}" placeholder="ACTIVE or PAUSED">
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">Search local name</label>
            <input class="form-control" type="text" name="search" maxlength="120" value="{{ $performance['filters']['search'] }}" placeholder="Name contains...">
        </div>
        <div class="col-md-1 mb-2">
            <button class="btn btn-primary btn-block" type="submit">Apply</button>
        </div>
    </div>
    <small class="text-muted">Stored application-local snapshots only. Loading this report does not call Meta.</small>
</form>
