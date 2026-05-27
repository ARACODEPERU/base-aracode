<?php

namespace Modules\Socialevents\Support;

use Illuminate\Support\Str;
use Modules\Socialevents\Entities\EventEdition;

final class TournamentLandingPresenter
{
    private const DEFAULT_HERO = 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?q=80&w=2070&auto=format&fit=crop';

    /**
     * Metadatos de presentación para la vista Blade (no sustituyen $edition ni colecciones).
     *
     * @return array<string, mixed>
     */
    public static function viewMeta(EventEdition $edition): array
    {
        $event = $edition->evento;
        $eventTitle = $event->title ?? 'Torneo';
        $teamCount = $edition->relationLoaded('equipos')
            ? $edition->equipos->count()
            : $edition->equipos()->count();

        $heroImage = self::resolveHeroImage($edition);
        $prizePlaces = self::prizePlacesList($edition->prize_details);
        $prizeSummary = self::mainPrizeLabel($edition->prize_details);
        $inscriptionLabel = filled($edition->inscription_fee)
            ? 'S/ '.number_format((float) $edition->inscription_fee, 0)
            : null;

        $descriptionPlain = trim(strip_tags((string) ($event->description ?? '')));
        $seoBase = "{$eventTitle} — {$edition->name}. Fixture, posiciones y estadísticas.";
        $seoDescription = Str::limit(
            $descriptionPlain !== '' ? "{$seoBase} {$descriptionPlain}" : $seoBase,
            160,
            '…'
        );

        return [
            'eventTitle' => $eventTitle,
            'teamCount' => $teamCount,
            'heroImage' => $heroImage,
            'canonicalUrl' => $edition->landingUrl(),
            'seoTitle' => "{$eventTitle} | {$edition->name}",
            'seoDescription' => $seoDescription,
            'seoImage' => $heroImage,
            'prizeSummary' => $prizeSummary,
            'inscriptionLabel' => $inscriptionLabel,
            'prizePlaces' => $prizePlaces,
            'hasPrizeSection' => count($prizePlaces) > 0,
            'accentColor' => self::sanitizeAccentColor($edition->accentColor()),
        ];
    }

    public static function resolveHeroImage(EventEdition $edition): string
    {
        if (filled($edition->landing_hero_image)) {
            return TournamentMedia::url($edition->landing_hero_image) ?? self::DEFAULT_HERO;
        }

        $event = $edition->evento;
        if ($event && filled($event->image1)) {
            return TournamentMedia::url($event->image1) ?? self::DEFAULT_HERO;
        }

        return self::DEFAULT_HERO;
    }

    /**
     * @param  array<string, mixed>|null  $prizeDetails
     */
    public static function mainPrizeLabel(?array $prizeDetails): ?string
    {
        $places = self::prizePlacesList($prizeDetails);

        return $places[0]['label_text'] ?? null;
    }

    /**
     * @param  array<string, mixed>|null  $prizeDetails
     * @return array<int, array<string, string|null>>
     */
    public static function prizePlacesList(?array $prizeDetails): array
    {
        if (! is_array($prizeDetails) || $prizeDetails === []) {
            return [];
        }

        $titles = [
            'first' => '1.er puesto',
            'second' => '2.º puesto',
            'third' => '3.er puesto',
            'fourth' => '4.º puesto',
        ];

        $places = [];

        foreach ($titles as $key => $title) {
            $row = $prizeDetails[$key] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $money = filled($row['money'] ?? null)
                ? 'S/ '.number_format((float) $row['money'], 0)
                : null;
            $gift = filled($row['gift'] ?? null) ? (string) $row['gift'] : null;

            if ($money === null && $gift === null) {
                continue;
            }

            $parts = array_filter([$money, $gift]);
            $labelText = implode(' + ', $parts);

            $places[] = [
                'key' => $key,
                'title' => $title,
                'money' => $money,
                'gift' => $gift,
                'label_text' => $labelText,
            ];
        }

        return $places;
    }

    private static function sanitizeAccentColor(?string $color): ?string
    {
        if (! is_string($color) || $color === '') {
            return null;
        }

        $color = trim($color);

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $color;
        }

        return null;
    }
}
