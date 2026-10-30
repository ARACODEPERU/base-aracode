<?php

namespace Modules\Socialevents\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionLandingView;
use Throwable;

/**
 * Registra cada visita a la landing pública de un torneo y completa sus datos
 * técnicos: procedencia, ciudad, navegador, sistema operativo y dispositivo.
 *
 * La visita se guarda de inmediato para no perderla. La ciudad se resuelve por
 * las cabeceras del proxy cuando existen y, si no, después de responder al
 * visitante, para no agregar latencia a la landing.
 */
class TournamentLandingVisitService
{
    private const BOT_PATTERN = '/bot|crawler|spider|facebookexternalhit|telegrambot|slackbot|discordbot|twitterbot|linkedinbot|pinterestbot|curl\/|wget\/|python-requests|headlesschrome|phantomjs|pingdom|uptimerobot|semrush|ahrefs|mj12bot/i';

    public function record(EventEdition $edition, Request $request): void
    {
        try {
            $userAgent = (string) $request->userAgent();

            if ($this->isBot($userAgent)) {
                return;
            }

            $referrer = $this->normalizeReferrer($request->headers->get('referer'));
            $referrerHost = $referrer ? $this->hostFromUrl($referrer) : null;
            $geo = $this->geoFromHeaders($request);
            $device = $this->parseDevice($userAgent);

            $visit = EventEditionLandingView::create([
                'edition_id' => $edition->id,
                'visitor_hash' => $this->visitorHash($request, $userAgent),
                'country' => $geo['country'],
                'country_code' => $geo['country_code'],
                'region' => $geo['region'],
                'city' => $geo['city'],
                'source' => $this->resolveSource($request, $referrerHost),
                'referrer_host' => $referrerHost,
                'referrer' => $referrer,
                'browser' => $device['browser'],
                'browser_version' => $device['browser_version'],
                'platform' => $device['platform'],
                'device_type' => $device['device_type'],
                'user_agent' => $userAgent !== '' ? mb_substr($userAgent, 0, 1000) : null,
                'viewed_at' => now(),
            ]);

            EventEdition::query()->whereKey($edition->id)->increment('landing_views');

            if ($geo['city'] === null && $this->canLookupGeo($request)) {
                $ip = (string) $request->ip();
                $visitId = (int) $visit->id;

                // El visitante ya recibió la página; aquí solo se completa la ciudad.
                app()->terminating(function () use ($visitId, $ip): void {
                    $this->enrichVisitLocation($visitId, $ip);
                });
            }
        } catch (Throwable $exception) {
            // La landing pública nunca debe caerse por un problema del contador.
            Log::warning('No se pudo registrar la visita de la landing del torneo.', [
                'edition_id' => $edition->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isBot(string $userAgent): bool
    {
        return $userAgent !== '' && preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }

    private function visitorHash(Request $request, string $userAgent): string
    {
        return hash_hmac(
            'sha256',
            (string) $request->ip().'|'.$userAgent,
            (string) config('app.key')
        );
    }

    private function canLookupGeo(Request $request): bool
    {
        if (! (bool) config('socialevents.landing_geo_lookup', true)) {
            return false;
        }

        $ip = (string) $request->ip();

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @return array{country: ?string, country_code: ?string, region: ?string, city: ?string}
     */
    private function geoFromHeaders(Request $request): array
    {
        $countryName = $this->header($request, [
            'CloudFront-Viewer-Country-Name',
            'X-AppEngine-Country',
        ]);
        $countryCode = $this->header($request, [
            'CF-IPCountry',
            'X-Vercel-IP-Country',
            'CloudFront-Viewer-Country',
        ]);

        return [
            'country' => $countryName ?? $countryCode,
            'country_code' => $countryCode !== null ? strtoupper($countryCode) : null,
            'region' => $this->header($request, [
                'CF-Region',
                'X-Vercel-IP-Country-Region-Name',
                'CloudFront-Viewer-Country-Region-Name',
                'X-AppEngine-Region',
            ]),
            'city' => $this->header($request, [
                'CF-IPCity',
                'X-Vercel-IP-City',
                'CloudFront-Viewer-City',
                'X-AppEngine-City',
            ]),
        ];
    }

    /**
     * @param  array<int, string>  $names
     */
    private function header(Request $request, array $names): ?string
    {
        foreach ($names as $name) {
            $value = $request->headers->get($name);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $value = trim(urldecode($value));

            if ($value === '') {
                continue;
            }

            return mb_substr($value, 0, 120);
        }

        return null;
    }

    private function enrichVisitLocation(int $visitId, string $ip): void
    {
        try {
            $geo = $this->lookupGeo($ip);

            if ($geo === []) {
                return;
            }

            $visit = EventEditionLandingView::query()->find($visitId);

            if (! $visit) {
                return;
            }

            $visit->fill(array_filter([
                'country' => $visit->country ?: ($geo['country'] ?? null),
                'country_code' => $visit->country_code ?: ($geo['country_code'] ?? null),
                'region' => $visit->region ?: ($geo['region'] ?? null),
                'city' => $visit->city ?: ($geo['city'] ?? null),
            ], fn ($value) => $value !== null && $value !== ''))->save();
        } catch (Throwable $exception) {
            Log::info('No se pudo completar la ubicación de una visita.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{country?: string, country_code?: string, region?: string, city?: string}
     */
    private function lookupGeo(string $ip): array
    {
        $cacheKey = 'tournament_landing_geo_'.hash('sha256', $ip);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $response = Http::acceptJson()
            ->connectTimeout(1)
            ->timeout(2)
            ->get('https://ipwho.is/'.rawurlencode($ip), [
                'fields' => 'success,country,country_code,region,city',
            ]);

        $payload = $response->json();

        if (! is_array($payload) || ! ($payload['success'] ?? false)) {
            Cache::put($cacheKey, [], now()->addDay());

            return [];
        }

        $geo = array_filter([
            'country' => $this->text($payload['country'] ?? null),
            'country_code' => $this->text($payload['country_code'] ?? null),
            'region' => $this->text($payload['region'] ?? null),
            'city' => $this->text($payload['city'] ?? null),
        ], fn ($value) => $value !== null);

        if (isset($geo['country_code'])) {
            $geo['country_code'] = strtoupper($geo['country_code']);
        }

        Cache::put($cacheKey, $geo, now()->addDays(30));

        return $geo;
    }

    private function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 120) : null;
    }

    private function normalizeReferrer(?string $referrer): ?string
    {
        if (! is_string($referrer)) {
            return null;
        }

        $referrer = trim($referrer);

        if ($referrer === '' || ! preg_match('#^https?://#i', $referrer)) {
            return null;
        }

        return mb_substr($referrer, 0, 1000);
    }

    private function hostFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return mb_substr(strtolower($host), 0, 255);
    }

    private function resolveSource(Request $request, ?string $referrerHost): string
    {
        $utmSource = $request->query('utm_source');

        if (is_string($utmSource) && trim($utmSource) !== '') {
            return $this->sourceLabel(trim($utmSource));
        }

        if ($request->query('gclid')) {
            return 'Google';
        }

        if ($request->query('fbclid')) {
            return 'Facebook';
        }

        if ($request->query('ttclid')) {
            return 'TikTok';
        }

        if ($request->query('msclkid')) {
            return 'Bing';
        }

        if ($referrerHost === null) {
            return 'Directo';
        }

        if ($referrerHost === strtolower($request->getHost())) {
            return 'Navegación interna';
        }

        return $this->sourceFromHost($referrerHost);
    }

    private function sourceLabel(string $value): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ._-]/u', '', $value) ?? '';
        $clean = trim(str_replace(['_', '-'], ' ', $clean));

        if ($clean === '') {
            return 'Otro';
        }

        return mb_substr(Str::title($clean), 0, 80);
    }

    private function sourceFromHost(string $host): string
    {
        $rules = [
            'Google' => ['google.'],
            'Bing' => ['bing.com', 'msn.com'],
            'Facebook' => ['facebook.', 'fb.me', 'm.facebook.'],
            'Instagram' => ['instagram.', 'ig.me'],
            'WhatsApp' => ['whatsapp.', 'wa.me', 'whatsapp.com'],
            'TikTok' => ['tiktok.', 'vm.tiktok.'],
            'YouTube' => ['youtube.', 'youtu.be'],
            'X (Twitter)' => ['twitter.', 't.co', 'x.com'],
            'LinkedIn' => ['linkedin.', 'lnkd.in'],
            'Telegram' => ['telegram.', 't.me'],
        ];

        foreach ($rules as $label => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($host, $needle)) {
                    return $label;
                }
            }
        }

        return mb_substr($host, 0, 80);
    }

    /**
     * @return array{browser: ?string, browser_version: ?string, platform: ?string, device_type: ?string}
     */
    private function parseDevice(string $userAgent): array
    {
        if ($userAgent === '') {
            return [
                'browser' => null,
                'browser_version' => null,
                'platform' => null,
                'device_type' => null,
            ];
        }

        [$browser, $browserVersion] = $this->parseBrowser($userAgent);

        return [
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'platform' => $this->parsePlatform($userAgent),
            'device_type' => $this->parseDeviceType($userAgent),
        ];
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function parseBrowser(string $userAgent): array
    {
        $browsers = [
            ['Instagram (app)', '/Instagram\s+([\d.]+)/i'],
            ['Facebook (app)', '/FBAN\/FBAV\/([\d.]+)/i'],
            ['TikTok (app)', '/TikTok\s+([\d.]+)/i'],
            ['LINE (app)', '/Line\/([\d.]+)/i'],
            ['Edge', '/Edg(?:A|iOS)?\/([\d.]+)/i'],
            ['Opera', '/OPR\/([\d.]+)/i'],
            ['Samsung Internet', '/SamsungBrowser\/([\d.]+)/i'],
            ['Chrome', '/(?:Chrome|CriOS)\/([\d.]+)/i'],
            ['Firefox', '/(?:Firefox|FxiOS)\/([\d.]+)/i'],
            ['Safari', '/Version\/([\d.]+).*Safari/i'],
        ];

        foreach ($browsers as [$name, $pattern]) {
            if (preg_match($pattern, $userAgent, $matches) === 1) {
                return [$name, $matches[1] ?? null];
            }
        }

        return ['Navegador web', null];
    }

    private function parsePlatform(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/Windows Phone/i', $userAgent) => 'Windows Phone',
            (bool) preg_match('/Windows NT/i', $userAgent) => 'Windows',
            (bool) preg_match('/Android/i', $userAgent) => 'Android',
            (bool) preg_match('/iPad/i', $userAgent) => 'iPadOS',
            (bool) preg_match('/(iPhone|iPod)/i', $userAgent) => 'iOS',
            (bool) preg_match('/CrOS/i', $userAgent) => 'ChromeOS',
            (bool) preg_match('/Mac OS X|Macintosh/i', $userAgent) => 'macOS',
            (bool) preg_match('/Ubuntu/i', $userAgent) => 'Ubuntu',
            (bool) preg_match('/Linux/i', $userAgent) => 'Linux',
            default => 'Otro',
        };
    }

    private function parseDeviceType(string $userAgent): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|Nexus 7|Nexus 9/i', $userAgent) === 1) {
            return 'tablet';
        }

        if (preg_match('/Android/i', $userAgent) === 1 && preg_match('/Mobile/i', $userAgent) !== 1) {
            return 'tablet';
        }

        if (preg_match('/Mobi|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini|Android.*Mobile/i', $userAgent) === 1) {
            return 'mobile';
        }

        return 'desktop';
    }
}
