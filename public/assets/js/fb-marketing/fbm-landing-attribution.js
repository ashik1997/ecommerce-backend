(function (window, document) {
    'use strict';

    var STORAGE_KEY = 'fbm_landing_attribution_session';
    var CHECKOUT_FIELD = 'fbm_attribution_session_uuid';

    function storageCandidates() {
        var candidates = [];
        try { candidates.push(window.localStorage); } catch (error) {}
        try { candidates.push(window.sessionStorage); } catch (error) {}
        return candidates;
    }

    function readStoredSession() {
        var stores = storageCandidates();
        for (var index = 0; index < stores.length; index += 1) {
            try {
                var raw = stores[index].getItem(STORAGE_KEY);
                if (!raw) { continue; }
                var value = JSON.parse(raw);
                if (!value || typeof value.uuid !== 'string') { continue; }
                if (value.expires_at && Date.parse(value.expires_at) <= Date.now()) {
                    stores[index].removeItem(STORAGE_KEY);
                    continue;
                }
                return value.uuid;
            } catch (error) {}
        }
        return null;
    }

    function storeSession(uuid, expiresAt) {
        if (typeof uuid !== 'string' || uuid.length === 0) { return; }
        var payload = JSON.stringify({ uuid: uuid, expires_at: expiresAt || null });
        var stores = storageCandidates();
        for (var index = 0; index < stores.length; index += 1) {
            try {
                stores[index].setItem(STORAGE_KEY, payload);
                return;
            } catch (error) {}
        }
    }

    function clearSession() {
        var stores = storageCandidates();
        for (var index = 0; index < stores.length; index += 1) {
            try { stores[index].removeItem(STORAGE_KEY); } catch (error) {}
        }
    }

    function generateOpaquePurchaseEventId() {
        try {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return 'fbm-browser-' + window.crypto.randomUUID();
            }
        } catch (error) {}
        return 'fbm-browser-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 18);
    }

    function readStoredPurchaseEventId() {
        var stores = storageCandidates();
        for (var index = 0; index < stores.length; index += 1) {
            try {
                var value = String(stores[index].getItem(PURCHASE_EVENT_STORAGE_KEY) || '');
                if (/^[A-Za-z0-9._:-]{1,255}$/.test(value)) { return value; }
            } catch (error) {}
        }
        return null;
    }

    function storePurchaseEventId(value) {
        var stores = storageCandidates();
        for (var index = 0; index < stores.length; index += 1) {
            try {
                stores[index].setItem(PURCHASE_EVENT_STORAGE_KEY, value);
                return;
            } catch (error) {}
        }
    }

    function getOrCreatePurchaseEventId() {
        var value = readStoredPurchaseEventId();
        if (value) { return value; }
        value = generateOpaquePurchaseEventId();
        storePurchaseEventId(value);
        return value;
    }

    function clearPurchaseEventId() {
        var stores = storageCandidates();
        for (var index = 0; index < stores.length; index += 1) {
            try { stores[index].removeItem(PURCHASE_EVENT_STORAGE_KEY); } catch (error) {}
        }
    }

    function newPurchaseEventId() {
        clearPurchaseEventId();
        return getOrCreatePurchaseEventId();
    }

    function cookie(name) {
        var prefix = name + '=';
        var parts = (document.cookie || '').split(';');
        for (var index = 0; index < parts.length; index += 1) {
            var part = parts[index].trim();
            if (part.indexOf(prefix) !== 0) { continue; }
            try { return decodeURIComponent(part.substring(prefix.length)); }
            catch (error) { return part.substring(prefix.length); }
        }
        return null;
    }

    function queryParameter(url, name) {
        try { return new URL(url).searchParams.get(name); }
        catch (error) { return null; }
    }

    function joinUrl(baseUrl, path) {
        return String(baseUrl || '').replace(/\/$/, '') + path;
    }

    function capture(options) {
        options = options || {};
        if (options.consentGranted !== true) {
            return Promise.resolve({ success: false, data: { capture_state: 'consent_required' } });
        }

        var landingUrl = String(options.landingUrl || window.location.href || '');
        var body = {
            session_uuid: readStoredSession(),
            landing_url: landingUrl,
            referrer_url: options.referrerUrl || document.referrer || null,
            utm_source: queryParameter(landingUrl, 'utm_source'),
            utm_medium: queryParameter(landingUrl, 'utm_medium'),
            utm_campaign: queryParameter(landingUrl, 'utm_campaign'),
            utm_content: queryParameter(landingUrl, 'utm_content'),
            utm_term: queryParameter(landingUrl, 'utm_term'),
            utm_id: queryParameter(landingUrl, 'utm_id'),
            fbclid: queryParameter(landingUrl, 'fbclid'),
            fbc: cookie('_fbc'),
            fbp: cookie('_fbp')
        };

        return window.fetch(joinUrl(options.apiBaseUrl, '/api/fb-marketing/attribution/landing'), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (payload && payload.data && payload.data.capture_state === 'stored') {
                    storeSession(payload.data.attribution_session_uuid, payload.data.expires_at);
                }
                return payload;
            });
        });
    }

    function withCheckoutAttribution(payload, options) {
        options = options || {};
        var enriched = Object.assign({}, payload || {});
        var uuid = readStoredSession();
        if (uuid) { enriched[CHECKOUT_FIELD] = uuid; }
        enriched[PURCHASE_EVENT_CHECKOUT_FIELD] = options.newPurchase === true
            ? newPurchaseEventId()
            : getOrCreatePurchaseEventId();
        return enriched;
    }

    window.FbmLandingAttribution = {
        capture: capture,
        withCheckoutAttribution: withCheckoutAttribution,
        getAttributionSessionUuid: readStoredSession,
        getPurchaseEventId: getOrCreatePurchaseEventId,
        newPurchaseEventId: newPurchaseEventId,
        clearPurchaseEventId: clearPurchaseEventId,
        clear: clearSession
    };
}(window, document));
