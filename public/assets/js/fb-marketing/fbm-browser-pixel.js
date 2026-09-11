(function (window, document) {
    'use strict';

    var CONFIG_PATH = '/api/fb-marketing/pixel/browser-config';
    var DIAGNOSTIC_EVENT = 'fbm:pixel-contract-event';
    var PIXEL_SCRIPT_ID = 'fbm-meta-pixel-script';
    var MAX_CONTENT_ITEMS = 100;
    var MAX_STRING_LENGTH = 255;
    var MAX_EVENT_ID_LENGTH = 100;
    var MAX_VALUE = 1000000000000;
    var MODES = ['disabled', 'dry_run', 'live'];
    var SUPPORTED_EVENTS = ['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase'];
    var initializedPixelIds = {};
    var state = {
        apiBaseUrl: '',
        consentGranted: false,
        config: null,
        configPromise: null,
        onDiagnostic: null
    };

    function joinUrl(baseUrl, path) {
        return String(baseUrl || '').replace(/\/$/, '') + path;
    }

    function isObject(value) {
        return value !== null && typeof value === 'object' && !Array.isArray(value);
    }

    function boundedString(value, maxLength) {
        if (typeof value !== 'string' && typeof value !== 'number') { return null; }
        var normalized = String(value).replace(/[\u0000-\u001F\u007F]/g, '').trim();
        if (!normalized) { return null; }
        return normalized.substring(0, maxLength);
    }

    function boundedNumber(value, maxValue) {
        if (value === '' || value === null || typeof value === 'boolean') { return null; }
        var normalized = Number(value);
        if (!isFinite(normalized) || normalized < 0 || normalized > maxValue) { return null; }
        return normalized;
    }

    function positiveInteger(value, maxValue) {
        var normalized = boundedNumber(value, maxValue);
        if (normalized === null || Math.floor(normalized) !== normalized || normalized < 1) { return null; }
        return normalized;
    }

    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            var bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);
            bytes[6] = (bytes[6] & 15) | 64;
            bytes[8] = (bytes[8] & 63) | 128;
            var hex = [];
            for (var index = 0; index < bytes.length; index += 1) {
                hex.push((bytes[index] + 256).toString(16).slice(1));
            }
            return hex.slice(0, 4).join('') + '-' + hex.slice(4, 6).join('') + '-' + hex.slice(6, 8).join('') + '-' + hex.slice(8, 10).join('') + '-' + hex.slice(10, 16).join('');
        }
        return 'fbm-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 18);
    }

    function normalizeEventId(value) {
        var eventId = boundedString(value, MAX_EVENT_ID_LENGTH);
        if (!eventId || !/^[A-Za-z0-9._:-]+$/.test(eventId)) { return uuid(); }
        return eventId;
    }

    function emit(detail) {
        var eventDetail = Object.assign({ contract_version: 'v1' }, detail || {});
        if (typeof state.onDiagnostic === 'function') {
            try { state.onDiagnostic(eventDetail); } catch (error) {}
        }
        try {
            window.dispatchEvent(new CustomEvent(DIAGNOSTIC_EVENT, { detail: eventDetail }));
        } catch (error) {
            try {
                var fallbackEvent = document.createEvent('CustomEvent');
                fallbackEvent.initCustomEvent(DIAGNOSTIC_EVENT, false, false, eventDetail);
                window.dispatchEvent(fallbackEvent);
            } catch (fallbackError) {}
        }
        return eventDetail;
    }

    function normalizeContents(value, errors) {
        if (!Array.isArray(value)) { errors.push('contents must be an array.'); return null; }
        var normalized = [];
        value.slice(0, MAX_CONTENT_ITEMS).forEach(function (item) {
            if (!isObject(item)) { return; }
            var id = boundedString(item.id, MAX_STRING_LENGTH);
            var quantity = positiveInteger(item.quantity, 100000);
            var itemPrice = item.item_price === undefined ? null : boundedNumber(item.item_price, MAX_VALUE);
            if (!id || quantity === null) { return; }
            var row = { id: id, quantity: quantity };
            if (itemPrice !== null) { row.item_price = itemPrice; }
            normalized.push(row);
        });
        if (value.length > MAX_CONTENT_ITEMS) { errors.push('contents exceeded the bounded item limit.'); }
        return normalized;
    }

    function normalizePayload(eventName, input) {
        input = isObject(input) ? input : {};
        var errors = [];
        var droppedKeys = [];
        var payload = {};
        var allowedKeys = ['eventId', 'content_ids', 'contents', 'content_type', 'content_name', 'content_category', 'currency', 'value', 'num_items'];

        Object.keys(input).forEach(function (key) {
            if (allowedKeys.indexOf(key) === -1) { droppedKeys.push(key); }
        });

        if (input.content_ids !== undefined) {
            if (!Array.isArray(input.content_ids)) {
                errors.push('content_ids must be an array.');
            } else {
                payload.content_ids = input.content_ids.slice(0, MAX_CONTENT_ITEMS).map(function (value) {
                    return boundedString(value, MAX_STRING_LENGTH);
                }).filter(function (value) { return value !== null; });
                if (input.content_ids.length > MAX_CONTENT_ITEMS) { errors.push('content_ids exceeded the bounded item limit.'); }
            }
        }

        if (input.contents !== undefined) {
            var contents = normalizeContents(input.contents, errors);
            if (contents !== null) { payload.contents = contents; }
        }

        if (input.content_type !== undefined) {
            var contentType = boundedString(input.content_type, 30);
            if (contentType !== 'product' && contentType !== 'product_group') {
                errors.push('content_type must be product or product_group.');
            } else {
                payload.content_type = contentType;
            }
        }

        ['content_name', 'content_category'].forEach(function (key) {
            if (input[key] === undefined) { return; }
            var normalized = boundedString(input[key], MAX_STRING_LENGTH);
            if (normalized !== null) { payload[key] = normalized; }
        });

        if (input.currency !== undefined) {
            var currency = boundedString(input.currency, 3);
            currency = currency ? currency.toUpperCase() : null;
            if (!currency || !/^[A-Z]{3}$/.test(currency)) {
                errors.push('currency must be a three-letter ISO-style code.');
            } else {
                payload.currency = currency;
            }
        }

        if (input.value !== undefined) {
            var value = boundedNumber(input.value, MAX_VALUE);
            if (value === null) { errors.push('value must be a bounded non-negative number.'); }
            else { payload.value = value; }
        }

        if (input.num_items !== undefined) {
            var numberOfItems = positiveInteger(input.num_items, 100000);
            if (numberOfItems === null) { errors.push('num_items must be a bounded positive integer.'); }
            else { payload.num_items = numberOfItems; }
        }

        if (eventName === 'Purchase') {
            if (!payload.currency) { errors.push('Purchase requires currency.'); }
            if (payload.value === undefined) { errors.push('Purchase requires value.'); }
        }

        return {
            event_id: normalizeEventId(input.eventId),
            payload: payload,
            dropped_keys: droppedKeys,
            validation_errors: errors
        };
    }

    function installPixelBase() {
        if (typeof window.fbq === 'function') { return; }
        var queue = function () {
            if (queue.callMethod) { queue.callMethod.apply(queue, arguments); }
            else { queue.queue.push(arguments); }
        };
        if (!window._fbq) { window._fbq = queue; }
        queue.push = queue;
        queue.loaded = true;
        queue.version = '2.0';
        queue.queue = [];
        window.fbq = queue;
    }

    function hasPixelScript() {
        if (document.getElementById(PIXEL_SCRIPT_ID)) { return true; }
        var scripts = document.getElementsByTagName('script');
        for (var index = 0; index < scripts.length; index += 1) {
            if (/^https:\/\/connect\.facebook\.net\/[^/]+\/fbevents\.js(?:\?|$)/.test(String(scripts[index].src || ''))) {
                return true;
            }
        }
        return false;
    }

    function ensurePixel(pixelId) {
        if (!pixelId) { return false; }
        installPixelBase();
        if (!initializedPixelIds[pixelId]) {
            window.fbq('init', pixelId);
            initializedPixelIds[pixelId] = true;
        }
        if (!hasPixelScript()) {
            var script = document.createElement('script');
            script.async = true;
            script.id = PIXEL_SCRIPT_ID;
            script.src = 'https://connect.facebook.net/en_US/fbevents.js';
            var firstScript = document.getElementsByTagName('script')[0];
            if (firstScript && firstScript.parentNode) { firstScript.parentNode.insertBefore(script, firstScript); }
            else if (document.head) { document.head.appendChild(script); }
        }
        return true;
    }

    function safeConfig(payload) {
        var data = payload && payload.data ? payload.data : {};
        var mode = MODES.indexOf(data.mode) !== -1 ? data.mode : 'disabled';
        return {
            contract_version: data.contract_version === 'v1' ? 'v1' : 'v1',
            mode: mode,
            enabled: data.enabled === true,
            live_delivery_ready: data.live_delivery_ready === true,
            pixel_id: boundedString(data.pixel_id, 32),
            supported_events: Array.isArray(data.supported_events) ? data.supported_events.filter(function (name) {
                return SUPPORTED_EVENTS.indexOf(name) !== -1;
            }) : SUPPORTED_EVENTS.slice()
        };
    }

    function initialize(options) {
        options = options || {};
        state.apiBaseUrl = options.apiBaseUrl || '';
        state.consentGranted = options.consentGranted === true;
        state.onDiagnostic = typeof options.onDiagnostic === 'function' ? options.onDiagnostic : null;

        if (!state.consentGranted) {
            state.config = null;
            state.configPromise = null;
            return Promise.resolve(emit({ delivery_state: 'consent_required', sent: false }));
        }

        if (state.configPromise) { return state.configPromise; }

        state.configPromise = window.fetch(joinUrl(state.apiBaseUrl, CONFIG_PATH), {
            method: 'GET',
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok) { throw new Error('Browser Pixel configuration request failed.'); }
                return payload;
            });
        }).then(function (payload) {
            state.config = safeConfig(payload);
            if (state.config.mode === 'live' && state.config.live_delivery_ready) {
                ensurePixel(state.config.pixel_id);
            }
            return emit({
                delivery_state: state.config.mode === 'dry_run' ? 'dry_run_ready' : (state.config.live_delivery_ready ? 'live_ready' : state.config.mode),
                sent: false,
                mode: state.config.mode,
                live_delivery_ready: state.config.live_delivery_ready,
                supported_events: state.config.supported_events
            });
        }).catch(function () {
            state.config = null;
            state.configPromise = null;
            return emit({ delivery_state: 'configuration_unavailable', sent: false });
        });

        return state.configPromise;
    }

    function track(eventName, input) {
        if (SUPPORTED_EVENTS.indexOf(eventName) === -1) {
            return Promise.resolve(emit({ delivery_state: 'unsupported_event', event_name: eventName, sent: false }));
        }
        if (!state.consentGranted) {
            return Promise.resolve(emit({ delivery_state: 'consent_required', event_name: eventName, sent: false }));
        }
        if (!state.config) {
            return Promise.resolve(emit({ delivery_state: 'not_initialized', event_name: eventName, sent: false }));
        }

        var normalized = normalizePayload(eventName, input);
        var detail = {
            delivery_state: state.config.mode,
            mode: state.config.mode,
            event_name: eventName,
            event_id: normalized.event_id,
            payload: normalized.payload,
            dropped_keys: normalized.dropped_keys,
            validation_errors: normalized.validation_errors,
            sent: false
        };

        if (normalized.validation_errors.length > 0) {
            detail.delivery_state = 'validation_failed';
            return Promise.resolve(emit(detail));
        }
        if (state.config.mode === 'disabled') {
            detail.delivery_state = 'disabled';
            return Promise.resolve(emit(detail));
        }
        if (state.config.mode === 'dry_run') {
            detail.delivery_state = 'dry_run';
            return Promise.resolve(emit(detail));
        }
        if (!state.config.live_delivery_ready || !ensurePixel(state.config.pixel_id)) {
            detail.delivery_state = 'live_not_ready';
            return Promise.resolve(emit(detail));
        }

        window.fbq('track', eventName, normalized.payload, { eventID: normalized.event_id });
        detail.delivery_state = 'sent';
        detail.sent = true;
        return Promise.resolve(emit(detail));
    }

    window.FbmBrowserPixel = {
        initialize: initialize,
        trackPageView: function (payload) { return track('PageView', payload); },
        trackViewContent: function (payload) { return track('ViewContent', payload); },
        trackAddToCart: function (payload) { return track('AddToCart', payload); },
        trackInitiateCheckout: function (payload) { return track('InitiateCheckout', payload); },
        trackPurchase: function (payload) { return track('Purchase', payload); }
    };
}(window, document));
