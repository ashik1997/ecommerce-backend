<div class="fa-page-head">
    <div>
        <h3 class="fa-page-title">{{ $title ?? 'Fixed Asset Management' }}</h3>
        @if(!empty($subtitle))
            <p class="fa-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($actions))
        <div class="fa-toolbar">{!! $actions !!}</div>
    @endif
</div>
