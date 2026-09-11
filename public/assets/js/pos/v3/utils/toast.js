(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    var container = null;
    var DEFAULT_DURATION = 2800;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function ensureContainer() {
        if (container && document.body.contains(container)) {
            return container;
        }

        container = document.createElement('div');
        container.className = 'pos-v3-toast-stack';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);

        return container;
    }

    function removeToast(element) {
        if (!element) {
            return;
        }

        element.classList.remove('is-visible');
        element.classList.add('is-leaving');

        global.setTimeout(function () {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 320);
    }

    global.PosV3.toast = {
        show: function show(message, options) {
            options = options || {};
            var stack = ensureContainer();
            var toast = document.createElement('div');
            var title = options.title || message || 'Product added to the list';
            var subtitle = options.subtitle || options.productName || '';

            toast.className = 'pos-v3-toast';
            toast.innerHTML =
                '<div class="pos-v3-toast__bell" aria-hidden="true">' +
                    '<i class="fas fa-bell"></i>' +
                '</div>' +
                '<div class="pos-v3-toast__body">' +
                    '<div class="pos-v3-toast__title">' + escapeHtml(title) + '</div>' +
                    (subtitle
                        ? '<div class="pos-v3-toast__subtitle">' + escapeHtml(subtitle) + '</div>'
                        : '') +
                '</div>' +
                '<button type="button" class="pos-v3-toast__close" aria-label="Dismiss">' +
                    '<i class="fas fa-times" aria-hidden="true"></i>' +
                '</button>';

            var closeBtn = toast.querySelector('.pos-v3-toast__close');
            var hideTimer = null;

            function scheduleHide() {
                if (hideTimer) {
                    global.clearTimeout(hideTimer);
                }

                hideTimer = global.setTimeout(function () {
                    removeToast(toast);
                }, options.duration || DEFAULT_DURATION);
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    if (hideTimer) {
                        global.clearTimeout(hideTimer);
                    }

                    removeToast(toast);
                });
            }

            stack.appendChild(toast);

            global.requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });

            scheduleHide();

            return toast;
        },
        productAdded: function productAdded(productName) {
            return this.show('Product added to the list', {
                productName: productName,
            });
        },
    };
})(window);
