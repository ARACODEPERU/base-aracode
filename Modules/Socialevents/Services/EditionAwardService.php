<?php

namespace Modules\Socialevents\Services;

use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiploma;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Support\DiplomaAwardCatalog;

/**
 * Premiados de una edición.
 *
 * Un premio de equipo genera un único diploma con la lista de su plantel; un
 * premio de jugador, un diploma individual. Los nombres se guardan como copia
 * para que una emisión posterior no cambie si se edita el premio.
 */
class EditionAwardService
{
    public function __construct(
        private PositionTableService $positions,
        private TournamentRankingsService $rankings,
    ) {}

    /**
     * Listado para el panel: cada premio con el modelo que usará y su diploma.
     *
     * El modelo puede elegirse explícitamente (`template_id`) o resolverse por
     * respaldo: el vigente más reciente del mismo tipo en el torneo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForEdition(int $editionId): array
    {
        $edition = EventEdition::findOrFail($editionId);

        $awards = EventEditionAward::where('edition_id', $editionId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $templates = EventEditionDiplomaTemplate::where('event_id', $edition->event_id)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get();

        $diplomas = EventEditionDiploma::where('edition_id', $editionId)
            ->orderBy('id')
            ->get()
            ->keyBy('award_id');

        return $awards->map(function (EventEditionAward $award) use ($templates, $diplomas) {
            $template = $this->pickTemplate($award, $templates);
            $diploma = $diplomas->get($award->id);

            return [
                'id' => $award->id,
                'award_key' => $award->award_key,
                'label' => $award->label,
                'recipient_type' => $award->recipient_type,
                'diploma_type' => $award->diplomaType(),
                'team_id' => $award->team_id,
                'person_id' => $award->person_id,
                'recipient_name' => $award->recipient_name,
                'team_name' => $award->team_name,
                'source' => $award->source,
                'place' => $award->place,
                'meta' => $award->meta ?? [],
                'sort_order' => $award->sort_order,
                'has_template' => (bool) $template,
                // `template_id` es la elección explícita (puede ser null) y
                // `selected_template_id` el modelo que realmente se usará.
                'template_id' => $award->template_id,
                'selected_template_id' => $template?->id,
                'template_name' => $template?->name,
                'template_explicit' => (bool) $award->template_id && $template && (int) $award->template_id === (int) $template->id,
                'squad_count' => $award->isTeamAward() ? count($this->squad($award)) : 0,
                'diploma' => $diploma ? [
                    'id' => $diploma->id,
                    'issued_at' => $diploma->issued_at?->format('d/m/Y H:i'),
                    'public_token' => $diploma->public_token,
                ] : null,
            ];
        })->all();
    }

    /**
     * Modelo que se usará al emitir un premio.
     *
     * Si el premio tiene uno asignado y sigue vigente, se respeta; si no, se
     * usa el vigente más reciente del mismo tipo en el torneo.
     */
    public function resolveTemplate(EventEditionAward $award): ?EventEditionDiplomaTemplate
    {
        $eventId = (int) EventEdition::whereKey($award->edition_id)->value('event_id');

        $templates = EventEditionDiplomaTemplate::where('event_id', $eventId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get();

        return $this->pickTemplate($award, $templates);
    }

    /**
     * Asigna explícitamente un modelo a un premio.
     *
     * Valida que pertenezca al mismo torneo y que su tipo coincida con el
     * destinatario: un diploma de equipo no puede usar un modelo de jugador.
     */
    public function assignTemplate(EventEditionAward $award, ?int $templateId): EventEditionAward
    {
        $edition = EventEdition::findOrFail($award->edition_id);

        if ($templateId) {
            $template = EventEditionDiplomaTemplate::where('event_id', $edition->event_id)
                ->where('is_active', true)
                ->findOrFail($templateId);

            if ($template->diploma_type !== $award->diplomaType()) {
                throw ValidationException::withMessages([
                    'template_id' => 'El modelo elegido no corresponde al tipo de este premio.',
                ]);
            }
        }

        $award->forceFill(['template_id' => $templateId])->save();

        return $award->refresh();
    }

    /**
     * Premio de muestra para previsualizar un modelo.
     *
     * Usa un premiado real del mismo tipo si existe; si no, devuelve un borrador
     * en memoria (sin guardar) para que el editor resuelva las variables.
     */
    public function sampleOfType(int $editionId, string $diplomaType): EventEditionAward
    {
        $award = EventEditionAward::where('edition_id', $editionId)
            ->where('recipient_type', $diplomaType)
            ->orderBy('sort_order')
            ->first();

        if ($award) {
            return $award;
        }

        $isTeam = $diplomaType === EventEditionDiplomaTemplate::TYPE_TEAM;

        return new EventEditionAward([
            'edition_id' => $editionId,
            'award_key' => $isTeam ? 'champion' : 'mvp',
            'label' => $isTeam ? 'Campeón' : 'Mejor jugador',
            'recipient_type' => $diplomaType,
            'recipient_name' => $isTeam ? 'Nombre del equipo' : 'Nombre del jugador',
            'team_name' => 'Equipo de ejemplo',
            'place' => $isTeam ? 1 : null,
            'meta' => ['goles' => 7, 'asistencias' => 3, 'mvp' => 2],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EventEditionDiplomaTemplate>  $templates
     */
    private function pickTemplate(EventEditionAward $award, $templates): ?EventEditionDiplomaTemplate
    {
        $type = $award->diplomaType();

        if ($award->template_id) {
            $explicit = $templates->first(fn (EventEditionDiplomaTemplate $template) => (int) $template->id === (int) $award->template_id);

            if ($explicit && $explicit->diploma_type === $type) {
                return $explicit;
            }
        }

        return $templates->first(fn (EventEditionDiplomaTemplate $template) => $template->diploma_type === $type);
    }

    /**
     * Candidatos para el modal de sugerencias.
     *
     * No se aplican solos: el organizador confirma a quién premia.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function candidates(int $editionId): array
    {
        $standings = $this->positions->getStandings($editionId);

        $teamCandidates = array_map(fn (array $row) => [
            'recipient_type' => 'team',
            'team_id' => $row['team_id'],
            'person_id' => null,
            'recipient_name' => $row['team_name'],
            'team_name' => $row['team_name'],
            'place' => $row['position'],
            'meta' => [
                'puntos' => $row['points'],
                'partidos' => $row['matches_played'],
                'goles_favor' => $row['goals_for'],
                'goles_contra' => $row['goals_against'],
                'posicion' => $row['position'],
            ],
        ], array_slice($standings, 0, 8));

        $players = $this->playerCandidates($editionId, $this->rankings->topPlayersPayload($editionId, 8));
        $scorers = $this->playerCandidates($editionId, $this->rankings->topScorersPayload($editionId, 8));
        $keepers = $this->playerCandidates($editionId, $this->rankings->topGoalkeepersPayload($editionId, 8));

        $assistRows = $this->rankings->topScorersPayload($editionId);
        usort($assistRows, fn ($a, $b) => ($b['assists'] ?? 0) <=> ($a['assists'] ?? 0));
        $assists = $this->playerCandidates($editionId, array_slice($assistRows, 0, 8));

        return [
            'champion' => $teamCandidates,
            'runner_up' => $teamCandidates,
            'third_place' => $teamCandidates,
            'fourth_place' => $teamCandidates,
            'fair_play' => $teamCandidates,
            'revelation_team' => $teamCandidates,
            'mvp' => $players,
            'best_defender' => $players,
            'top_scorer' => $scorers,
            'top_assists' => $assists,
            'best_goalkeeper' => $keepers,
        ];
    }

    /**
     * Crea o actualiza un premio. Devuelve el premio guardado.
     */
    public function save(int $editionId, array $data, ?int $awardId = null): EventEditionAward
    {
        EventEdition::findOrFail($editionId);

        $key = trim((string) $data['award_key']);

        if (! DiplomaAwardCatalog::exists($key)) {
            throw ValidationException::withMessages([
                'award_key' => 'El tipo de premio no es válido.',
            ]);
        }

        $duplicate = EventEditionAward::where('edition_id', $editionId)
            ->where('award_key', $key)
            ->when($awardId, fn ($query) => $query->where('id', '!=', $awardId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'award_key' => 'Ya existe un premiado con este tipo de premio en la edición.',
            ]);
        }

        $award = $awardId
            ? EventEditionAward::where('edition_id', $editionId)->findOrFail($awardId)
            : new EventEditionAward(['edition_id' => $editionId]);

        $recipientType = $data['recipient_type'] ?? ($award->recipient_type ?: 'player');
        $teamId = $data['team_id'] ?? null;
        $personId = $data['person_id'] ?? null;

        [$recipientName, $teamName] = $this->resolveRecipient($editionId, $recipientType, $teamId, $personId, $data);

        if (array_key_exists('template_id', $data) && $data['template_id']) {
            $edition = EventEdition::findOrFail($editionId);
            $template = EventEditionDiplomaTemplate::where('event_id', $edition->event_id)
                ->where('is_active', true)
                ->find((int) $data['template_id']);

            $type = $recipientType === 'team'
                ? EventEditionDiplomaTemplate::TYPE_TEAM
                : EventEditionDiplomaTemplate::TYPE_PLAYER;

            if (! $template || $template->diploma_type !== $type) {
                throw ValidationException::withMessages([
                    'template_id' => 'El modelo de diploma no corresponde a este premiado.',
                ]);
            }
        }

        $award->fill([
            'award_key' => $key,
            'label' => trim((string) ($data['label'] ?? '')) ?: DiplomaAwardCatalog::labelFromKey($key),
            'recipient_type' => $recipientType,
            'team_id' => $teamId,
            'person_id' => $personId,
            'recipient_name' => $recipientName,
            'team_name' => $teamName,
            'source' => $data['source'] ?? 'manual',
            'place' => $data['place'] ?? null,
            'meta' => $data['meta'] ?? null,
            'is_published' => (bool) ($data['is_published'] ?? true),
            'template_id' => $data['template_id'] ?? $award->template_id,
        ]);

        if (! $award->exists && empty($data['sort_order'])) {
            $award->sort_order = (int) EventEditionAward::where('edition_id', $editionId)->max('sort_order') + 10;
        } elseif (isset($data['sort_order'])) {
            $award->sort_order = (int) $data['sort_order'];
        }

        $award->save();

        return $award;
    }

    public function delete(EventEditionAward $award): void
    {
        // Los diplomas emitidos caen por llave foránea (cascade).
        $award->delete();
    }

    /**
     * Reordena los premiados según los ids recibidos.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(int $editionId, array $orderedIds): void
    {
        DB::transaction(function () use ($editionId, $orderedIds) {
            foreach (array_values($orderedIds) as $index => $id) {
                EventEditionAward::where('edition_id', $editionId)
                    ->where('id', $id)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });
    }

    /**
     * Plantel del equipo premiado, ordenado por camiseta y nombre.
     *
     * @return array<int, array{name: string, jersey: ?int, position: ?string}>
     */
    public function squad(EventEditionAward $award): array
    {
        if (! $award->isTeamAward() || ! $award->team_id) {
            return [];
        }

        return EventEditionTeamPlayer::with('person')
            ->where('edition_id', $award->edition_id)
            ->where('team_id', $award->team_id)
            ->get()
            ->map(fn (EventEditionTeamPlayer $player) => [
                'name' => $player->person?->full_name ?? 'Jugador',
                'jersey' => $player->jersey_number !== null ? (int) $player->jersey_number : null,
                'position' => $player->position,
            ])
            ->sortBy([
                fn ($a, $b) => ($a['jersey'] ?? 999) <=> ($b['jersey'] ?? 999),
                fn ($a, $b) => strcasecmp($a['name'], $b['name']),
            ])
            ->values()
            ->all();
    }

    /**
     * Completa el nombre del ganador y del equipo desde la base.
     *
     * @return array{0: string, 1: ?string}
     */
    private function resolveRecipient(
        int $editionId,
        string $recipientType,
        ?int $teamId,
        ?int $personId,
        array $data
    ): array {
        $fallbackName = trim((string) ($data['recipient_name'] ?? ''));

        if ($recipientType === 'team') {
            $team = EventTeam::find($teamId);
            $name = $team?->name ?: $fallbackName;

            return [$name ?: 'Equipo', $team?->name ?: $fallbackName ?: null];
        }

        $person = Person::find($personId);
        $name = $person?->full_name ?: $fallbackName;

        $teamName = null;
        if ($teamId) {
            $teamName = EventTeam::find($teamId)?->name;
        }

        if (! $teamName && $personId) {
            $registration = EventEditionTeamPlayer::with('team')
                ->where('edition_id', $editionId)
                ->where('person_id', $personId)
                ->first();
            $teamName = $registration?->team?->name;
        }

        return [$name ?: 'Jugador', $teamName];
    }

    /**
     * Traduce las filas de ranking a candidatos de premio.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function playerCandidates(int $editionId, array $rows): array
    {
        $playerIds = array_values(array_filter(array_map(fn ($row) => $row['player_id'] ?? null, $rows)));

        $registrations = EventEditionTeamPlayer::with(['team', 'person'])
            ->where('edition_id', $editionId)
            ->whereIn('person_id', $playerIds)
            ->get()
            ->keyBy('person_id');

        return array_map(function (array $row) use ($registrations) {
            $playerId = $row['player_id'] ?? null;
            $registration = $playerId ? $registrations->get($playerId) : null;
            $stats = $row['stats'] ?? [];

            return [
                'recipient_type' => 'player',
                'team_id' => $registration?->team_id,
                'person_id' => $playerId,
                'recipient_name' => $row['player_name'] ?? 'Jugador',
                'team_name' => $registration?->team?->name,
                'place' => null,
                'meta' => [
                    'puntos' => $row['points'] ?? null,
                    'goles' => $row['goals'] ?? ($stats['goals'] ?? null),
                    'asistencias' => $row['assists'] ?? ($stats['assists'] ?? null),
                    'mvp' => $stats['mvp'] ?? null,
                    'atajadas' => $stats['saves'] ?? null,
                    'camiseta' => $registration?->jersey_number,
                    'posicion' => $registration?->position,
                ],
            ];
        }, $rows);
    }
}
