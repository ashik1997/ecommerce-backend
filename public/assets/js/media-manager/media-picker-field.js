(function (window, document) {
    'use strict';

    function initField(field) {
        if (field.getAttribute('data-media-picker-ready') === '1') return;
        field.setAttribute('data-media-picker-ready', '1');

        var hidden = field.querySelector('[data-media-picker-value]');
        var pathHidden = field.querySelector('[data-media-picker-path]');
        var preview = field.querySelector('[data-media-picker-preview]');
        var choose = field.querySelector('[data-media-picker-choose]');
        var remove = field.querySelector('[data-media-picker-remove]');

        if (!hidden || !preview || !choose) return;

        choose.addEventListener('click', function () {
            if (!window.MediaManager || typeof window.MediaManager.open !== 'function') {
                notify('Media manager is not available.');
                return;
            }

            window.MediaManager.open({
                mode: field.getAttribute('data-mode') || 'single',
                selected: selectedIds(hidden.value),
                max: numberOrNull(field.getAttribute('data-max')),
                directory: field.getAttribute('data-directory') || null,
                width: numberOrNull(field.getAttribute('data-width')),
                height: numberOrNull(field.getAttribute('data-height')),
                purpose: field.getAttribute('data-purpose') || null,
                onSelect: function (files) {
                    applySelection(field, files || []);
                }
            });
        });

        if (remove) {
            remove.addEventListener('click', function () {
                clearField(field);
            });
        }
    }

    function applySelection(field, files) {
        var mode = field.getAttribute('data-mode') || 'single';
        var hidden = field.querySelector('[data-media-picker-value]');
        var pathHidden = field.querySelector('[data-media-picker-path]');
        var selected = mode === 'multiple' ? files : files.slice(0, 1);

        hidden.value = selected.map(function (file) { return file.id; }).join(',');
        if (pathHidden) {
            pathHidden.value = selected.map(function (file) { return file.path || ''; }).filter(Boolean).join(',');
        }

        renderPreview(field, selected);
        field.dispatchEvent(new CustomEvent('media-picker:change', {
            bubbles: true,
            detail: { files: selected }
        }));
    }

    function clearField(field) {
        var hidden = field.querySelector('[data-media-picker-value]');
        var pathHidden = field.querySelector('[data-media-picker-path]');

        hidden.value = '';
        if (pathHidden) pathHidden.value = '';
        renderPreview(field, []);
        field.dispatchEvent(new CustomEvent('media-picker:change', {
            bubbles: true,
            detail: { files: [] }
        }));
    }

    function renderPreview(field, files) {
        var preview = field.querySelector('[data-media-picker-preview]');
        var remove = field.querySelector('[data-media-picker-remove]');
        var placeholder = field.querySelector('[data-media-picker-placeholder]');

        preview.innerHTML = '';
        if (!files.length) {
            preview.style.display = 'none';
            if (placeholder) placeholder.style.display = '';
            if (remove) remove.disabled = true;
            return;
        }

        files.forEach(function (file) {
            var item = document.createElement('div');
            item.className = 'media-picker-field__item';
            item.innerHTML = [
                '<div class="media-picker-field__thumb">',
                file.url ? '<img src="' + escapeAttr(file.url) + '" alt="">' : '<i class="fas fa-file-image"></i>',
                '</div>',
                '<div class="media-picker-field__meta">',
                '<strong>' + escapeHtml(file.original_name || file.file_name || file.path || 'Selected file') + '</strong>',
                '<span>' + escapeHtml(file.width && file.height ? file.width + ' x ' + file.height : (file.path || '')) + '</span>',
                '</div>'
            ].join('');
            preview.appendChild(item);
        });

        preview.style.display = '';
        if (placeholder) placeholder.style.display = 'none';
        if (remove) remove.disabled = false;
    }

    function selectedIds(value) {
        return String(value || '').split(',').map(function (id) {
            return id.trim();
        }).filter(Boolean);
    }

    function numberOrNull(value) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? null : parsed;
    }

    function notify(message) {
        if (window.toastr) {
            window.toastr.error(message);
            return;
        }
        alert(message);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    function init() {
        document.querySelectorAll('[data-media-picker-field]').forEach(initField);
    }

    window.MediaPickerField = {
        init: init,
        initField: initField,
        clearField: clearField,
        applySelection: applySelection
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
