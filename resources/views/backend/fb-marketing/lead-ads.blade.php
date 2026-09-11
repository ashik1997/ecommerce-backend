@extends('backend.master')

@section('title')
    FB Marketing Lead Ads
@endsection

@section('content')
    @php
        $label = fn($value) => ucwords(str_replace('_', ' ', strtolower((string) $value)));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Lead Ads</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Lead Ads</li>
                        </ul>
                    </div>
                </div>
            </div>

            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach
            @if (!$report['crm_ready'])
                <div class="alert alert-warning">CRM leads table is not available. Lead Ads events can be logged, but CRM lead creation is disabled.</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Webhook endpoint</h5>
                    <p class="text-muted mb-0">{{ url($report['webhook_path']) }}</p>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Events', number_format($report['summary']['event_count'])],
                    ['Mapped to CRM', number_format($report['summary']['mapped_count'])],
                    ['Pending retrieval', number_format($report['summary']['pending_count'])],
                    ['Duplicates', number_format($report['summary']['duplicate_count'])],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">{{ $card[0] }}</p><h4 class="mb-0">{{ $card[1] }}</h4></div></div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Lead events</h5>
                    @if (empty($report['events']))
                        <p class="text-muted mb-0">No Lead Ads event has been received.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Event</th><th>Fields</th><th>CRM</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach ($report['events'] as $event)
                                        <tr>
                                            <td>#{{ $event['id'] }}<br><small class="text-muted">{{ $event['received_at'] }}</small></td>
                                            <td>{{ implode(', ', $event['field_keys']) ?: 'No field data' }}<br><small class="text-muted">{{ json_encode($event['mapped_field_flags']) }}</small></td>
                                            <td>{{ $event['crm_lead_id'] ? 'CRM lead #' . $event['crm_lead_id'] : 'Not mapped' }}</td>
                                            <td><span class="badge badge-{{ $event['status'] === 'mapped_to_crm' ? 'success' : 'secondary' }}">{{ $label($event['status']) }}</span><br><small class="text-muted">{{ $event['redacted_message'] }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Webhook logs</h5>
                    @forelse ($report['webhook_logs'] as $log)
                        <p class="mb-2">{{ $label($log['event_type']) }} · {{ $label($log['status']) }}<br><small class="text-muted">{{ $log['redacted_message'] }} · {{ $log['created_at'] }}</small></p>
                    @empty
                        <p class="text-muted mb-0">No webhook log has been recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
