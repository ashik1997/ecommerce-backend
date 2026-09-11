@php
    $fieldId = $id ?? 'media_picker_' . str_replace(['[', ']', '.', '-'], '_', $inputName ?? 'media_id') . '_' . uniqid();
    $mode = $mode ?? 'single';
    $label = $label ?? 'Media';
    $inputName = $inputName ?? 'media_id';
    $value = $value ?? '';
    $pathInputName = $pathInputName ?? null;
    $pathValue = $pathValue ?? '';
    $selectedFiles = collect($selectedFiles ?? []);
    $buttonText = $buttonText ?? ($mode === 'multiple' ? 'Choose Files' : 'Choose File');
@endphp

<div id="{{ $fieldId }}"
    class="media-picker-field"
    data-media-picker-field
    data-mode="{{ $mode }}"
    data-max="{{ $max ?? '' }}"
    data-directory="{{ $directory ?? '' }}"
    data-width="{{ $width ?? '' }}"
    data-height="{{ $height ?? '' }}"
    data-purpose="{{ $purpose ?? '' }}">
    <label class="media-picker-field__label">
        {{ $label }}
        @if (!empty($required))
            <span class="text-danger">*</span>
        @endif
    </label>

    <input type="hidden" name="{{ $inputName }}" value="{{ $value }}" data-media-picker-value>
    @if ($pathInputName)
        <input type="hidden" name="{{ $pathInputName }}" value="{{ $pathValue }}" data-media-picker-path>
    @endif

    <div class="media-picker-field__surface">
        <div class="media-picker-field__placeholder" data-media-picker-placeholder style="{{ $selectedFiles->isNotEmpty() ? 'display:none;' : '' }}">
            <i class="fas fa-images"></i>
            <span>No file selected</span>
        </div>

        <div class="media-picker-field__preview" data-media-picker-preview style="{{ $selectedFiles->isEmpty() ? 'display:none;' : '' }}">
            @foreach ($selectedFiles as $file)
                <div class="media-picker-field__item">
                    <div class="media-picker-field__thumb">
                        @if (!empty(data_get($file, 'url')))
                            <img src="{{ data_get($file, 'url') }}" alt="">
                        @else
                            <i class="fas fa-file-image"></i>
                        @endif
                    </div>
                    <div class="media-picker-field__meta">
                        <strong>{{ data_get($file, 'original_name') ?? data_get($file, 'file_name') ?? data_get($file, 'path') ?? 'Selected file' }}</strong>
                        <span>
                            @if (!empty(data_get($file, 'width')) && !empty(data_get($file, 'height')))
                                {{ data_get($file, 'width') }} x {{ data_get($file, 'height') }}
                            @else
                                {{ data_get($file, 'path') ?? '' }}
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="media-picker-field__actions">
            <button type="button" class="media-picker-field__btn media-picker-field__btn--primary" data-media-picker-choose>
                <i class="fas fa-folder-open"></i>
                {{ $buttonText }}
            </button>
            <button type="button" class="media-picker-field__btn media-picker-field__btn--light" data-media-picker-remove {{ $selectedFiles->isEmpty() && empty($value) ? 'disabled' : '' }}>
                <i class="fas fa-times"></i>
                Remove
            </button>
        </div>
    </div>

    @if (!empty($helpText))
        <small class="form-text text-muted">{{ $helpText }}</small>
    @endif
</div>
