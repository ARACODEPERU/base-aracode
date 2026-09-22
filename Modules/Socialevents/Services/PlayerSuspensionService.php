<?php

namespace Modules\Socialevents\Services;

use Carbon\CarbonInterface;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionPlayerExclusion;
use Modules\Socialevents\Entities\EventEditionPlayerSuspension;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;

class PlayerSuspensionService
{
    /**
     * IDs de jugadores suspendidos en una edición.
     * Si se pasa el partido, evalúa el tipo "matches" según los partidos de ese equipo
     * celebrados desde que se impuso la suspensión (participó => cumple).
     * Si se pasa fecha, filtra además por el tipo "date_range".
     *
     * @return array<int>
     */
    public function getSuspendedPlayerIds(int $editionId, ?EventEditionMatch $match = null, ?CarbonInterface $onDate = null): array
    {
        $suspensions = EventEditionPlayerSuspension::query()
            ->where('edition_id', $editionId)
            ->active()
            ->get();

        if ($suspensions->isEmpty()) {
            return [];
        }

        return $suspensions
            ->filter(fn (EventEditionPlayerSuspension $s) => $this->suspensionBlocks($s, $match, $onDate))
            ->pluck('player_id')
            ->all();
    }

    /**
     * ¿Esta suspensión en particular impide jugar (en el partido/fecha dado)?
     */
    public function suspensionBlocks(EventEditionPlayerSuspension $suspension, ?EventEditionMatch $match = null, ?CarbonInterface $onDate = null): bool
    {
        switch ($suspension->type) {
            case EventEditionPlayerSuspension::TYPE_DEFINITIVE:
                return true;

            case EventEditionPlayerSuspension::TYPE_MATCHES:
                if ($match === null) {
                    // Sin partido concreto se asume bloqueo (listados y rankings).
                    return true;
                }

                // El jugador cumple partido por partido: cada partido de su equipo
                // (local o visitante) CELEBRADO (con marcador) desde que se impuso
                // la suspensión cuenta como cumplido, PARTICIPARA O NO.
                $served = $this->countMatchesServed($suspension, $match);

                return $served < (int) $suspension->matches_count;

            case EventEditionPlayerSuspension::TYPE_DATE_RANGE:
                $date = $onDate ?? $match?->match_date ?? now();

                if ($suspension->starts_at && $date->startOfDay()->lt($suspension->starts_at->startOfDay())) {
                    return false;
                }

                return ! ($suspension->ends_at && $date->startOfDay()->gt($suspension->ends_at->startOfDay()));

            default:
                return true;
        }
    }

    /**
     * Partidos de la edición del partido dado, del equipo del jugador suspendido,
     * celebrados (con marcador) desde que se impuso la suspensión. Cuenta el
     * partido actual si ya está celebrado (recálculo de acta).
     *
     * @return int
     */
    private function countMatchesServed(EventEditionPlayerSuspension $suspension, EventEditionMatch $match): int
    {
        // El partido actual ya está celebrado => cuenta como cumplido en este recálculo.
        $isCurrentMatchPlayed = $match->score_h !== null && $match->score_a !== null;

        $teamIds = EventEditionTeamPlayer::query()
            ->where('edition_id', $suspension->edition_id)
            ->where('person_id', $suspension->player_id)
            ->pluck('team_id')
            ->all();

        if (empty($teamIds)) {
            return 0;
        }

        $query = EventEditionMatch::query()
            ->where('edition_id', $suspension->edition_id)
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('equipo_h_id', $teamIds)
                    ->orWhereIn('equipo_a_id', $teamIds);
            })
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->where(function ($q) use ($suspension) {
                $q->where('match_date', '>=', $suspension->suspended_at)
                    ->orWhereNull('match_date');
            });

        if (! $isCurrentMatchPlayed) {
            $query->where('id', '!=', $match->id);
        }

        return $query->count();
    }

    /**
     * Mapa [player_id => suspension] con la suspensión activa que aplica para el
     * partido/fecha dado (para badges en alineaciones).
     *
     * @return array<int, EventEditionPlayerSuspension>
     */
    public function getActiveSuspensionsMap(int $editionId, ?EventEditionMatch $match = null, ?CarbonInterface $onDate = null): array
    {
        $suspensions = EventEditionPlayerSuspension::query()
            ->where('edition_id', $editionId)
            ->active()
            ->get();

        $map = [];

        foreach ($suspensions as $suspension) {
            if ($this->suspensionBlocks($suspension, $match, $onDate)) {
                $map[$suspension->player_id] = $suspension;
            }
        }

        return $map;
    }

    /**
     * Activa una suspensión (crea o reemplaza la anterior del jugador en la edición).
     */
    public function suspend(array $data): EventEditionPlayerSuspension
    {
        return EventEditionPlayerSuspension::updateOrCreate(
            ['edition_id' => $data['edition_id'], 'player_id' => $data['player_id']],
            [
                'type' => $data['type'],
                'matches_count' => $data['matches_count'] ?? null,
                'matches_served' => 0,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'reason' => $data['reason'],
                'suspended_by' => $data['suspended_by'] ?? null,
                'suspended_at' => now(),
                'lifted_at' => null,
            ]
        );
    }

    /**
     * Levanta manualmente una suspensión (el jugador vuelve a jugar).
     */
    public function lift(EventEditionPlayerSuspension $suspension): void
    {
        $suspension->update(['lifted_at' => now()]);
    }

    /**
     * Rankings: suspendidos vigentes + excluidos definitivos se ocultan.
     *
     * @return array<int>
     */
    public function getPlayerIdsHiddenFromRankings(int $editionId, ?CarbonInterface $onDate = null): array
    {
        $excluded = EventEditionPlayerExclusion::where('edition_id', $editionId)
            ->pluck('player_id')
            ->all();

        $suspended = $this->getSuspendedPlayerIds($editionId, null, $onDate);

        return array_values(array_unique(array_merge($excluded, $suspended)));
    }

    /**
     * Detecta jugadores suspendidos marcados como participantes (Titular/Suplente)
     * en el acta que se quiere guardar. Devuelve [id => nombre] para el mensaje.
     *
     * @return array<int, string>
     */
    public function findSuspendedParticipating(EventEditionMatch $match, ?array $playersH, ?array $playersA): array
    {
        $participating = [];

        foreach ([$playersH, $playersA] as $players) {
            if (! is_array($players)) {
                continue;
            }

            foreach ($players as $p) {
                if (! isset($p['person_id'])) {
                    continue;
                }

                $role = $p['match_role'] ?? null;

                if ($role === null || $role === '') {
                    continue;
                }

                $participating[] = (int) $p['person_id'];
            }
        }

        if (empty($participating)) {
            return [];
        }

        $suspendedIds = $this->getSuspendedPlayerIds((int) $match->edition_id, $match);
        $blocked = array_intersect($participating, $suspendedIds);

        if (empty($blocked)) {
            return [];
        }

        return \App\Models\Person::query()
            ->whereIn('id', $blocked)
            ->pluck('full_name', 'id')
            ->all();
    }
}
