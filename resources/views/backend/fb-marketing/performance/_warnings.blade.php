@if ($errors->any())
    <div class="alert alert-danger">
        <strong>The performance request could not be completed.</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@foreach ($performance['warnings'] as $warning)
    <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
@endforeach
