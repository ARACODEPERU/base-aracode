<?php

namespace Modules\Integrationhub\Support;

/**
 * Resuelve el valor del campo "Fuente" a partir del tracking de primer toque
 * (first-touch) que se guarda en localStorage y se persiste en las tablas
 * de suscriptores, ventas y carritos abandonados.
 *
 * - Orgánico (sin UTM ni referrer)          -> "Organico"
 * - Facebook Ads (fbclid o medium ads)      -> "Meta_ads"
 * - Facebook CPC/pago                       -> "Meta_cpc"
 * - Google Ads (gclid o medium ads)         -> "Google_ads"
 * - Google CPC/pago                         -> "Google_cpc"
 * - Otras plataformas con ads/cpc           -> "{Plataforma}_ads" / "{Plataforma}_cpc"
 * - Email                                   -> "Email"
 * - Vino de otra página                     -> dominio del referrer
 * - Red social orgánica                     -> nombre de la plataforma (p.ej. "Youtube")
 */
class TrafficSourceResolver
{
    /**
     * Campos de tracking de primer toque que se persisten en las tablas
     * de suscriptores, ventas y carritos abandonados.
     */
    public const KEYS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'utm_id', 'fbclid', 'gclid', 'referer', 'landing_url', 'traffic_source',
    ];

    public static function fuente(array $tracking): string
    {
        $source = strtolower(trim((string)($tracking['utm_source'] ?? '')));
        $medium = strtolower(trim((string)($tracking['utm_medium'] ?? '')));
        $fbclid = !empty($tracking['fbclid']);
        $gclid  = !empty($tracking['gclid']);
        $trafficSource = (string)($tracking['traffic_source'] ?? '');
        $referer = (string)($tracking['referer'] ?? '');

        $platforms = [
            'facebook' => 'Meta',
            'fb'       => 'Meta',
            'instagram'=> 'Instagram',
            'google'   => 'Google',
            'youtube'  => 'Youtube',
            'tiktok'   => 'Tiktok',
            'twitter'  => 'X',
            'x'        => 'X',
            'threads'  => 'Threads',
            'linkedin' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'email'    => 'Email',
        ];

        $platform = null;
        foreach ($platforms as $needle => $label) {
            if ($source === $needle || str_starts_with($source, $needle)) {
                $platform = $label;
                break;
            }
        }

        $isAds  = (bool) preg_match('/ads/', $medium);
        $isPaid = (bool) preg_match('/cpc|ppc|paid/', $medium);

        // Facebook / Meta
        if ($platform === 'Meta' && ($fbclid || $isAds)) {
            return 'Meta_ads';
        }
        if ($platform === 'Meta' && $isPaid) {
            return 'Meta_cpc';
        }
        // Google
        if ($platform === 'Google' && ($gclid || $isAds)) {
            return 'Google_ads';
        }
        if ($platform === 'Google' && $isPaid) {
            return 'Google_cpc';
        }
        // Otras plataformas con anuncios (Youtube, Tiktok, etc.)
        if ($platform && $platform !== 'Meta' && $platform !== 'Google' && ($isAds || $isPaid)) {
            return $platform . ($isAds ? '_ads' : '_cpc');
        }
        // Email
        if ($trafficSource === 'email' || $platform === 'Email') {
            return 'Email';
        }
        // Orgánico
        if ($trafficSource === 'organic' || ($trafficSource === '' && $source === '' && $medium === '' && $referer === '')) {
            return 'Organico';
        }
        // Vino de otra página
        if ($trafficSource === 'referrer' || ($referer !== '' && $source === '')) {
            $host = parse_url($referer, PHP_URL_HOST);
            return $host ?: 'Referrer';
        }
        // Red social orgánica -> nombre de la plataforma
        if ($platform) {
            return $platform;
        }

        if ($trafficSource === 'cpc') {
            return 'CPC';
        }

        return $trafficSource !== '' ? ucfirst($trafficSource) : 'Organico';
    }

    /**
     * Construye las acciones de Fuente y Medio para el endpoint create_contact.
     *
     * @param  array  $tracking  Tracking de primer toque (utm_*, fbclid, gclid, referer, ...)
     * @return array  Arreglo de acciones [['action' => 'set_field_value', ...], ...]
     */
    public static function actions(array $tracking): array
    {
        $actions = [
            [
                'action' => 'set_field_value',
                'field_name' => 'Fuente',
                'value' => self::fuente($tracking),
            ],
        ];

        $medio = !empty($tracking['utm_campaign']) ? $tracking['utm_campaign'] : ($tracking['utm_medium'] ?? '');
        if ($medio !== '') {
            $actions[] = [
                'action' => 'set_field_value',
                'field_name' => 'Medio',
                'value' => $medio,
            ];
        }

        return $actions;
    }
}
