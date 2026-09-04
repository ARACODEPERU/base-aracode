<?php

namespace Modules\Socialevents\Services;

use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMedia;
use Modules\Socialevents\Support\TournamentDateLabels;
use Modules\Socialevents\Support\TournamentMedia;
use Modules\Socialevents\Support\TournamentPhaseLabels;

class TournamentPublicDataService
{
    public function __construct(
        private TournamentStandingsService $standingsService,
        private TournamentRankingsService $rankingsService,
        private TournamentFixtureService $fixtureService,
    ) {}

    /**
     * Payload agregador para GET /api/socialevents/v1/edition/{id}/public
     *
     * @return array<string, mixed>
     */
    public function buildForApi(int $editionId): array
    {
        $edition = EventEdition::with(['evento'])->findOrFail($editionId);
        $event = $edition->evento;

        return [
            'edition' => $this->editionPayload($edition),
            'event' => $this->eventPayload($event),
            'links' => $this->linksPayload($edition),
            'standings' => $this->standingsService->asStandingsPayload($editionId),
            'rankings' => $this->standingsService->asRankingsPayload($editionId),
            'players_ranking' => $this->rankingsService->topPlayersPayload($editionId),
            'scorers_ranking' => $this->rankingsService->topScorersPayload($editionId),
            'goalkeepers_ranking' => $this->rankingsService->topGoalkeepersPayload($editionId),
            'upcoming_matches' => $this->fixtureService->upcomingPayload($editionId),
            'recent_results' => $this->fixtureService->recentResultsPayload($editionId),
            'phase_labels' => TournamentPhaseLabels::labels(),
        ];
    }

    /**
     * Variables para la landing Blade (modelos Eloquent + colecciones).
     *
     * @return array<string, mixed>
     */
    public function buildForLanding(EventEdition $edition): array
    {
        $editionId = $edition->id;

        return [
            'edition' => $edition,
            'matches' => $this->fixtureService->groupedFixture($editionId),
            'currentEquipment' => $this->standingsService->sortedTeams($editionId),
            'playersRanking' => $this->rankingsService->topPlayers($editionId),
            'scorersRanking' => $this->rankingsService->topScorers($editionId),
            'goalkeepersRanking' => $this->rankingsService->topGoalkeepers($editionId),
            'phaseLabels' => TournamentPhaseLabels::labels(),
            'gallery' => $this->galleryPayload($editionId),
        ];
    }

    /**
     * Medios de la galería agrupados por fecha para la landing.
     *
     * @return array<int, array<string, mixed>>
     */
    private function galleryPayload(int $editionId): array
    {
        $media = EventEditionMedia::with('match')
            ->where('edition_id', $editionId)
            ->orderByDesc('media_date')
            ->orderByDesc('created_at')
            ->get();

        $groups = [];

        foreach ($media as $item) {
            $dateKey = $item->media_date->format('Y-m-d');

            $groups[$dateKey] ??= [
                'date' => $dateKey,
                'label' => TournamentDateLabels::full($item->media_date),
                'items' => [],
            ];

            $groups[$dateKey]['items'][] = [
                'id' => $item->id,
                'type' => $item->type,
                'url' => TournamentMedia::url($item->file_path),
                'mime_type' => $item->mime_type,
                'match_label' => $item->match
                    ? $this->matchLabel($item->match)
                    : null,
            ];
        }

        return array_values($groups);
    }

    private function matchLabel($match): string
    {
        $home = $match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir';
        $away = $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir';

        return $home.' vs '.$away;
    }

    /**
     * @return array<string, mixed>
     */
    private function editionPayload(EventEdition $edition): array
    {
        return [
            'id' => $edition->id,
            'event_id' => $edition->event_id,
            'name' => $edition->name,
            'year' => $edition->year,
            'start_date' => $edition->start_date,
            'end_date' => $edition->end_date,
            'competition_format' => $edition->competition_format,
            'status' => $edition->status,
            'prize_details' => $edition->prize_details,
            'inscription_fee' => $edition->inscription_fee,
            'has_bases' => ! empty($edition->path_database_file),
            'bases_file' => $this->basesFilePayload($edition),
            'teams_count' => $edition->equipos()->count(),
            'matches_count' => $edition->matches()->count(),
            'contact_name' => $edition->contact_name,
            'contact_phone' => $edition->contact_phone,
            'contact_whatsapp' => $edition->contact_whatsapp,
            'landing_published' => (bool) $edition->landing_published,
            'mobile_enabled' => (bool) $edition->mobile_enabled,
            'public_slug' => $edition->public_slug,
            'branding' => $this->brandingForApi($edition->branding),
            'accent_color' => $edition->accentColor(),
            'hero_image' => TournamentMedia::url($edition->landing_hero_image),
        ];
    }

    /**
     * @param  \Modules\Socialevents\Entities\EvenEvent|null  $event
     * @return array<string, mixed>|null
     */
    private function eventPayload($event): ?array
    {
        if (! $event) {
            return null;
        }

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image' => TournamentMedia::url($event->image1),
            'image1' => TournamentMedia::url($event->image1),
            'image2' => TournamentMedia::url($event->image2),
            'image3' => TournamentMedia::url($event->image3),
            'image4' => TournamentMedia::url($event->image4),
            'date_start' => $event->date_start,
            'date_end' => $event->date_end,
            'status' => $event->status,
            'broadcast' => (bool) $event->broadcast,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function linksPayload(EventEdition $edition): array
    {
        return [
            'landing_web' => $edition->landingUrl(),
            'api_public' => url("/api/socialevents/v1/edition/{$edition->id}/public"),
        ];
    }

    /**
     * @return array<string, string|null>|null
     */
    private function basesFilePayload(EventEdition $edition): ?array
    {
        if (! $edition->path_database_file) {
            return null;
        }

        return [
            'name' => $edition->name_database_file ?? 'bases_torneo.pdf',
            'url' => TournamentMedia::url($edition->path_database_file),
        ];
    }

    /**
     * JSON object `{}` when vacío (evita `[]` que rompe clientes móviles).
     *
     * @return array<string, mixed>|\stdClass
     */
    private function brandingForApi(mixed $branding): array|\stdClass
    {
        if (! is_array($branding) || $branding === [] || array_is_list($branding)) {
            return new \stdClass;
        }

        return $branding;
    }
}
