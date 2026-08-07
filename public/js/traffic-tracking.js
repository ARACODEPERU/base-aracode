/**
 * Traffic tracking — atribución de primer toque (first-touch).
 *
 * Se ejecuta en TODAS las páginas (públicas y panel) para registrar el primer
 * origen de la visita. Reglas:
 *  - Si hay variables UTM / fbclid / gclid -> se registran y se clasifica el canal.
 *  - Si no hay UTM pero hay referrer      -> se guarda como 'referrer' (se muestra la página de origen).
 *  - Si no hay nada                       -> 'organic'.
 *
 * Los datos quedan en localStorage bajo la clave 'traffic_tracking' con vigencia
 * de 24h (el primer toque no se sobrescribe dentro de ese período).
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'traffic_tracking';
    var TWENTY_FOUR_HOURS = 24 * 60 * 60 * 1000;
    var params = new URLSearchParams(window.location.search);

    // First-touch: si ya existe un registro fresco (< 24h) no se sobrescribe.
    var existing = null;
    try { existing = JSON.parse(localStorage.getItem(STORAGE_KEY)); } catch (e) {}
    if (existing && existing.timestamp && (Date.now() - existing.timestamp < TWENTY_FOUR_HOURS)) {
        return;
    }

    var utmSource   = params.get('utm_source') || '';
    var utmMedium   = params.get('utm_medium') || '';
    var utmCampaign = params.get('utm_campaign') || '';
    var utmTerm     = params.get('utm_term') || '';
    var utmContent  = params.get('utm_content') || '';
    var utmId       = params.get('utm_id') || '';
    var fbclid      = params.get('fbclid') || '';
    var gclid       = params.get('gclid') || '';
    var referer     = document.referrer || '';

    var src = (utmSource || '').toLowerCase();
    var med = (utmMedium || '').toLowerCase();
    var isPaidMedium = /(paid|cpc|ppc|ads)/.test(med);
    var isFacebook = /(facebook|^fb$)/.test(src);
    var isGoogle = src.indexOf('google') !== -1;

    var trafficSource = 'organic';

    if (fbclid || (isFacebook && isPaidMedium)) {
        trafficSource = 'facebook_ads';
    } else if (gclid || (isGoogle && isPaidMedium)) {
        trafficSource = 'google_ads';
    } else if (isFacebook || /(instagram|tiktok|youtube|linkedin|twitter|threads|whatsapp|^x$)/.test(src)) {
        trafficSource = 'social';
    } else if (isGoogle) {
        trafficSource = 'organic';
    } else if (isPaidMedium) {
        trafficSource = 'cpc';
    } else if (/social/.test(med)) {
        trafficSource = 'social';
    } else if (/(email|newsletter|mail)/.test(src) || /(email|newsletter|mail)/.test(med)) {
        trafficSource = 'email';
    } else if (referer) {
        trafficSource = 'referrer';
    } else {
        trafficSource = 'organic';
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        utm_source: utmSource,
        utm_medium: utmMedium,
        utm_campaign: utmCampaign,
        utm_term: utmTerm,
        utm_content: utmContent,
        utm_id: utmId,
        fbclid: fbclid,
        gclid: gclid,
        referer: referer,
        landing_url: window.location.href,
        traffic_source: trafficSource,
        timestamp: Date.now()
    }));
})();
