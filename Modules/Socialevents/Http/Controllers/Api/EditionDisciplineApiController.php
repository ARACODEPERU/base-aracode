<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionPlayerExclusion;
use Modules\Socialevents\Entities\EventEditionPlayerSuspension;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Services\PlayerSuspensionService;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Support\TournamentLandingCache;

/**
 * Disciplina de la edición desde la app móvil (Fase 5B): tarjetas pendientes,
 * suspensiones y exclusiones.
 *
 * Repite las reglas de la web: solo se tocan jugadores inscritos en la edición,
 * las tarjetas manuales exigen un partido con resultado y la doble amarilla
 * registra la expulsión más sus dos amarillas. La pertenencia sale del token;
 * ninguna ruta recibe la organización del cliente.
 */
class EditionDisciplineApiController extends Controller
{
    /** Etiquetas de tarjeta usadas en la app y en la web. */
    private const CARD_LABELS = [
        'yellow' => 'Amarilla',
        'double_yellow' => 'Doble amarilla',
        'red' => 'Roja directa',
    ];

    public function __construct(
        private SportsOrganizationService $sports,
        private PlayerSuspensionService $suspensions
    ) {}

    public function sanctions(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanRead($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $board = $this->sanctionBoard($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Sanciones de la edición obtenidas correctamente',
            'data' => [
                'players' => $board['players'],
                'total_pending' => $board['total_pending'],
                'played_matches' => $this->playedMatches($editionId),
                'all_players' => $this->enrolledPlayers($editionId),
            ],
        ]);
    }

    public function storeSanction(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'match_id' => 'required|integer|exists:event_edition_matches,id',
            'player_id' => 'required|integer|exists:people,id',
            'type' => 'required|in:yellow,red,double_yellow',
            'minute' => 'nullable|string|max:5',
            'amount_fine' => 'nullable|numeric|min:0|max:9999.99',
        ], [
            'match_id.required' => 'Selecciona el partido.',
            'player_id.required' => 'Selecciona el jugador.',
            'type.required' => 'Selecciona el tipo de tarjeta.',
            'amount_fine.max' => 'El monto no puede superar S/ 9999.99.',
        ]);

        // Solo partidos jugados de esta edición: una tarjeta manual necesita un
        // acta con marcador, igual que en la web.
        $match = EventEditionMatch::where('edition_id', $editionId)
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->find($validated['match_id']);

        abort_unless($match, 422, 'El partido no pertenece a esta edición o todavía no tiene resultado.');

        $enrolled = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $validated['player_id'])
            ->exists();

        abort_unless($enrolled, 422, 'El jugador no está inscrito en esta edición.');

        $edition = EventEdition::findOrFail($editionId);

        $amount = isset($validated['amount_fine']) && $validated['amount_fine'] !== null
            ? round((float) $validated['amount_fine'], 2)
            : (float) match ($validated['type']) {
                'yellow' => $edition->yellow_price ?? 0,
                'red' => $edition->direct_red_price ?? 0,
                'double_yellow' => $edition->double_yellow_price ?? 0,
            };

        $created = DB::transaction(function () use ($validated, $amount) {
            $ids = [];

            if ($validated['type'] === 'double_yellow') {
                $ids[] = EventEditionMatchSanction::create([
                    'match_id' => $validated['match_id'],
                    'player_id' => $validated['player_id'],
                    'type' => 'double_yellow',
                    'minute' => $validated['minute'] ?? null,
                    'amount_fine' => $amount,
                ])->id;

                for ($i = 1; $i <= 2; $i++) {
                    $ids[] = EventEditionMatchSanction::create([
                        'match_id' => $validated['match_id'],
                        'player_id' => $validated['player_id'],
                        'type' => 'yellow',
                        'minute' => $validated['minute'] ?? null,
                        'amount_fine' => 0,
                    ])->id;
                }
            } else {
                $ids[] = EventEditionMatchSanction::create([
                    'match_id' => $validated['match_id'],
                    'player_id' => $validated['player_id'],
                    'type' => $validated['type'],
                    'minute' => $validated['minute'] ?? null,
                    'amount_fine' => $amount,
                ])->id;
            }

            return $ids;
        });

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Tarjeta registrada correctamente.',
            'data' => [
                'sanction_ids' => $created,
                'amount_fine' => $amount,
            ],
        ], 201);
    }

    public function suspensions(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanRead($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $suspensions = EventEditionPlayerSuspension::with('player')
            ->where('edition_id', $editionId)
            ->orderByDesc('id')
            ->get();

        $teamByPlayer = $this->teamsByPlayer($editionId);
        $activeIds = $suspensions
            ->filter(fn (EventEditionPlayerSuspension $suspension) => $suspension->isActive())
            ->pluck('player_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Suspensiones de la edición obtenidas correctamente',
            'data' => [
                'suspensions' => $suspensions->map(
                    fn (EventEditionPlayerSuspension $suspension) => [
                        'id' => (int) $suspension->id,
                        'player_id' => (int) $suspension->player_id,
                        'player_name' => $this->playerName($suspension->player),
                        'team_name' => $teamByPlayer[(int) $suspension->player_id] ?? null,
                        'type' => $suspension->type,
                        'type_label' => $suspension->type_label,
                        'status_label' => $suspension->status_label,
                        'matches_count' => $suspension->matches_count,
                        'matches_served' => (int) $suspension->matches_served,
                        'starts_at' => $suspension->starts_at?->format('Y-m-d'),
                        'ends_at' => $suspension->ends_at?->format('Y-m-d'),
                        'reason' => $suspension->reason,
                        'suspended_by' => $suspension->suspended_by,
                        'suspended_at' => $suspension->suspended_at?->format('Y-m-d'),
                        'is_active' => $suspension->isActive(),
                    ]
                )->values(),
                'players' => $this->enrolledPlayers($editionId, $activeIds),
            ],
        ]);
    }

    public function storeSuspension(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'player_id' => 'required|integer',
            'type' => 'required|in:definitive,matches,date_range',
            'matches_count' => 'nullable|integer|min:1|max:100|required_if:type,matches',
            'starts_at' => 'nullable|date|required_if:type,date_range',
            'ends_at' => 'nullable|date|after_or_equal:starts_at|required_if:type,date_range',
            'reason' => 'required|string|max:500',
            'suspended_by' => 'nullable|string|max:255',
        ], [
            'matches_count.required_if' => 'Indica cuántos partidos durará la suspensión.',
            'starts_at.required_if' => 'Indica la fecha de inicio.',
            'ends_at.required_if' => 'Indica la fecha de fin.',
            'reason.required' => 'Escribe el motivo de la suspensión.',
        ]);

        $enrolled = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $validated['player_id'])
            ->exists();

        abort_unless($enrolled, 422, 'El jugador no está inscrito en esta edición.');

        $suspension = $this->suspensions->suspend([
            'edition_id' => $editionId,
            'player_id' => $validated['player_id'],
            'type' => $validated['type'],
            'matches_count' => $validated['matches_count'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'reason' => $validated['reason'],
            'suspended_by' => $validated['suspended_by'] ?? null,
        ]);

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Jugador suspendido. No podrá jugar ni aparecer en los rankings mientras la suspensión esté vigente.',
            'data' => [
                'id' => (int) $suspension->id,
                'type_label' => $suspension->type_label,
                'status_label' => $suspension->status_label,
            ],
        ], 201);
    }

    public function liftSuspension(Request $request, int $editionId, int $suspensionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $suspension = EventEditionPlayerSuspension::where('edition_id', $editionId)
            ->findOrFail($suspensionId);

        $this->suspensions->lift($suspension);

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Suspensión levantada. El jugador vuelve a jugar y a los rankings.',
        ]);
    }

    public function exclusions(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanRead($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $exclusions = EventEditionPlayerExclusion::with('player')
            ->where('edition_id', $editionId)
            ->orderByDesc('id')
            ->get();

        $teamByPlayer = $this->teamsByPlayer($editionId);
        $excludedIds = $exclusions->pluck('player_id')->map(fn ($id) => (int) $id)->all();

        return response()->json([
            'success' => true,
            'message' => 'Exclusiones de la edición obtenidas correctamente',
            'data' => [
                'exclusions' => $exclusions->map(
                    fn (EventEditionPlayerExclusion $exclusion) => [
                        'id' => (int) $exclusion->id,
                        'player_id' => (int) $exclusion->player_id,
                        'player_name' => $this->playerName($exclusion->player),
                        'team_name' => $teamByPlayer[(int) $exclusion->player_id] ?? null,
                        'reason' => $exclusion->reason,
                        'excluded_by' => $exclusion->excluded_by,
                        'excluded_at' => $exclusion->excluded_at?->format('Y-m-d'),
                    ]
                )->values(),
                'players' => $this->enrolledPlayers($editionId, $excludedIds),
            ],
        ]);
    }

    public function storeExclusion(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'player_id' => 'required|integer',
            'reason' => 'required|string|max:500',
            'excluded_by' => 'nullable|string|max:255',
        ], [
            'reason.required' => 'Escribe el motivo de la exclusión.',
        ]);

        $enrolled = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $validated['player_id'])
            ->exists();

        abort_unless($enrolled, 422, 'El jugador no está inscrito en esta edición.');

        $exclusion = EventEditionPlayerExclusion::updateOrCreate(
            [
                'edition_id' => $editionId,
                'player_id' => $validated['player_id'],
            ],
            [
                'reason' => $validated['reason'],
                'excluded_by' => $validated['excluded_by'] ?? null,
                'excluded_at' => now(),
            ]
        );

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Jugador excluido de los rankings de la edición.',
            'data' => ['id' => (int) $exclusion->id],
        ], 201);
    }

    public function destroyExclusion(Request $request, int $editionId, int $exclusionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $exclusion = EventEditionPlayerExclusion::where('edition_id', $editionId)
            ->findOrFail($exclusionId);

        $exclusion->delete();

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Exclusión eliminada. El jugador vuelve a los rankings.',
        ]);
    }

    /**
     * Jugadores con tarjetas pendientes y su deuda efectiva.
     *
     * @return array{players: array<int, array<string, mixed>>, total_pending: float}
     */
    private function sanctionBoard(int $editionId): array
    {
        $players = EventEditionTeamPlayer::with([
            'team',
            'person',
            'sanctions' => function ($query) use ($editionId) {
                $query->with('match')
                    ->where('is_paid', false)
                    ->whereHas('match', fn ($match) => $match->where('edition_id', $editionId));
            },
        ])
            ->where('edition_id', $editionId)
            ->whereHas('sanctions', function ($query) use ($editionId) {
                $query->where('is_paid', false)
                    ->whereHas('match', fn ($match) => $match->where('edition_id', $editionId));
            })
            ->get();

        $totalPending = 0.0;

        $rows = $players->map(function (EventEditionTeamPlayer $player) use (&$totalPending) {
            // Regla de absorción: con doble amarilla en un partido se cobran la
            // doble y las rojas, y se ignoran las amarillas simples de ese partido.
            $effective = collect();

            foreach ($player->sanctions->groupBy('match_id') as $matchSanctions) {
                $hasDoubleYellow = $matchSanctions->where('type', 'double_yellow')->isNotEmpty();

                $effective = $effective->merge(
                    $hasDoubleYellow
                        ? $matchSanctions->whereIn('type', ['double_yellow', 'red'])
                        : $matchSanctions
                );
            }

            $debt = (float) $effective->sum(fn ($sanction) => (float) $sanction->amount_fine);
            $totalPending += $debt;

            return [
                'player_id' => (int) $player->person_id,
                'name' => $this->playerName($player->person),
                'team_name' => $player->team?->name,
                'total_debt' => round($debt, 2),
                'sanctions' => $effective
                    ->map(fn (EventEditionMatchSanction $sanction) => [
                        'id' => (int) $sanction->id,
                        'type' => $sanction->type,
                        'type_label' => self::CARD_LABELS[$sanction->type] ?? $sanction->type,
                        'minute' => $sanction->minute,
                        'amount_fine' => (float) $sanction->amount_fine,
                        'match_id' => (int) $sanction->match_id,
                        'match_label' => $this->matchLabel($sanction->match),
                    ])
                    ->values()
                    ->all(),
            ];
        })
            ->sortBy('name')
            ->values()
            ->all();

        return [
            'players' => $rows,
            'total_pending' => round($totalPending, 2),
        ];
    }

    /** Partidos jugados de la edición, para registrar una tarjeta manual. */
    private function playedMatches(int $editionId): array
    {
        return EventEditionMatch::with(['equipolocal:id,name', 'equipovisitante:id,name'])
            ->where('edition_id', $editionId)
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->orderBy('match_date')
            ->get(['id', 'team_h_id', 'team_a_id', 'round_number', 'match_date', 'score_h', 'score_a'])
            ->map(fn (EventEditionMatch $match) => [
                'id' => (int) $match->id,
                'label' => $this->matchLabel($match),
            ])
            ->values()
            ->all();
    }

    private function matchLabel(?EventEditionMatch $match): string
    {
        if (! $match) {
            return 'Sin partido asignado';
        }

        $round = $match->round_number ?? '?';
        $date = $match->match_date?->format('d/m/Y');
        $teams = trim(sprintf(
            '%s %s-%s %s',
            $match->equipolocal?->name ?? 'Local',
            $match->score_h ?? '-',
            $match->score_a ?? '-',
            $match->equipovisitante?->name ?? 'Visitante'
        ));

        return 'Fecha '.$round.' · '.$teams.($date ? ' · '.$date : '');
    }

    /**
     * Jugadores inscritos en la edición, sin los ids indicados.
     *
     * @param  array<int>  $excludeIds
     * @return array<int, array{player_id: int, name: string, team_name: string|null}>
     */
    private function enrolledPlayers(int $editionId, array $excludeIds = []): array
    {
        return EventEditionTeamPlayer::with(['team:id,name', 'person'])
            ->where('edition_id', $editionId)
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('person_id', $excludeIds))
            ->get()
            ->filter(fn (EventEditionTeamPlayer $player) => $player->person !== null)
            ->map(fn (EventEditionTeamPlayer $player) => [
                'player_id' => (int) $player->person_id,
                'name' => $this->playerName($player->person),
                'team_name' => $player->team?->name,
            ])
            ->sortBy([['team_name', 'asc'], ['name', 'asc']])
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function teamsByPlayer(int $editionId): array
    {
        return EventEditionTeamPlayer::with('team:id,name')
            ->where('edition_id', $editionId)
            ->get()
            ->mapWithKeys(fn (EventEditionTeamPlayer $player) => [
                (int) $player->person_id => $player->team?->name ?? '',
            ])
            ->all();
    }

    private function playerName($person): string
    {
        $full = trim((string) ($person?->full_name ?? ''));
        if ($full !== '') {
            return $full;
        }

        $names = trim((string) ($person?->names ?? ''));

        return $names !== '' ? $names : 'Jugador sin nombre';
    }
}
