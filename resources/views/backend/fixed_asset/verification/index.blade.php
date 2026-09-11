@extends('backend.master')
@section('page_title','Asset Verification')
@section('page_heading','Asset Verification')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between mb-3"><h4 class="fa-card-title">Physical Verification Sessions</h4><a href="{{ route('fixed-assets.verification.create') }}" class="btn btn-fa-primary btn-sm">New Session</a></div>
    <div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Session</th><th>Warehouse</th><th>Started</th><th>Status</th><th>Action</th></tr></thead><tbody>
    @forelse($sessions as $session)<tr><td><strong>{{ $session->session_no }}</strong><br><small>{{ $session->title }}</small></td><td>{{ optional($session->warehouse)->title }}</td><td>{{ optional($session->started_at)->format('d M Y') }}</td><td><span class="fa-badge {{ $session->status === 'completed' ? 'green' : 'amber' }}">{{ $session->status }}</span></td><td><a class="btn btn-info btn-sm" href="{{ route('fixed-assets.verification.show',$session) }}">Open</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No verification sessions found.</td></tr>@endforelse
    </tbody></table></div>{{ $sessions->links() }}
</div></div>
</div>
@endsection
