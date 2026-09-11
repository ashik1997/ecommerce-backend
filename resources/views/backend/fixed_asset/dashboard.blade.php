@extends('backend.master')
@section('page_title','Fixed Asset Dashboard')
@section('page_heading','Fixed Asset Dashboard')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
<div class="row">
@foreach([['Total Assets',$summary['total_assets'] ?? 0],['Capitalized Cost',number_format($summary['capitalized_cost'] ?? 0,2)],['Book Value',number_format($summary['book_value'] ?? 0,2)],['Maintenance Open',$summary['open_maintenance'] ?? 0],['Disposed',$summary['disposed_assets'] ?? 0]] as $item)
<div class="col-md-3 col-xl-2 mb-3"><div class="fa-kpi p-3"><div class="label">{{ $item[0] }}</div><div class="value">{{ $item[1] }}</div></div></div>
@endforeach
</div>
<div class="row">
 <div class="col-lg-7"><div class="card"><div class="card-body"><h4 class="fa-card-title mb-3">Warehouse Wise Summary</h4><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Warehouse</th><th>Total</th><th>Capitalized</th><th>Book Value</th></tr></thead><tbody>@forelse($warehouseSummaries as $row)<tr><td>{{ $row->warehouse_name ?? 'N/A' }}</td><td>{{ $row->total_assets }}</td><td>{{ number_format($row->total_cost,2) }}</td><td>{{ number_format($row->book_value,2) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No asset data found.</td></tr>@endforelse</tbody></table></div></div></div></div>
 <div class="col-lg-5"><div class="card"><div class="card-body"><h4 class="fa-card-title mb-3">Recent Assets</h4><div class="list-group">@forelse($recentAssets as $asset)<a class="list-group-item list-group-item-action" href="{{ route('fixed-assets.assets.show',$asset) }}"><strong>{{ $asset->asset_code }}</strong> — {{ $asset->asset_name }}<br><small>{{ optional($asset->warehouse)->title }} / {{ optional($asset->category)->name }}</small></a>@empty<div class="text-muted">No recent asset.</div>@endforelse</div></div></div></div>
</div>
</div>
@endsection
