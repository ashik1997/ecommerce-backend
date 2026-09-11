@php
    $value = $value ?? '';
    $normalized = strtolower(str_replace(' ', '_', (string) $value));
@endphp
<span class="fa-badge {{ $normalized }}">{{ ucwords(str_replace('_',' ', $value)) }}</span>
