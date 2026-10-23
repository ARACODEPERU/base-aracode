<?php

namespace Modules\Socialevents\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;

/**
 * Documentos PDF de una edición para la app móvil.
 *
 * Reutiliza las mismas vistas Blade que la web para que el acta, la planilla
 * del partido y la lista de sanciones salgan idénticas desde cualquier canal.
 * La pertenencia a la organización se valida en el controlador, antes de
 * generar la URL firmada.
 */
class EditionPdfService
{
    /**
     * Acta de un partido ya cerrado.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function acta(int $reportId)
    {
        $report = EventEditionMatchReport::with([
            'partido.equipolocal.manager',
            'partido.equipovisitante.manager',
            'partido.edicion.evento',
        ])->findOrFail($reportId);

        $match = $report->partido;

        abort_unless($match && $match->edicion, 404, 'El acta no tiene un partido asociado.');

        $local = $match->equipolocal;
        $visitor = $match->equipovisitante;

        $closedAt = $report->closed_at
            ? Carbon::parse($report->closed_at)
            : $report->created_at;

        $acta = [
            'event_name' => $match->edicion->evento?->title ?? '',
            'edition_name' => $match->edicion->name ?? '',
            'minutes_code' => $report->minutes_code,
            'match_id' => (int) $report->match_id,
            'minutes_subject' => ($local?->name ?? 'Local').' VS '.($visitor?->name ?? 'Visitante'),
            'minutes_subject_local' => $local?->name ?? 'Local',
            'minutes_subject_visitor' => $visitor?->name ?? 'Visitante',
            'local_score' => (int) $report->local_score,
            'visitor_score' => (int) $report->visitor_score,
            'closed_at' => $closedAt ? $closedAt->format('d/m/Y H:i') : '-',
            'referees' => $report->referees ?? [],
            'observations' => $report->observations,
            'protest_details' => $report->protest_details ?? [],
            'resolution_details' => $report->resolution_details,
            'participants' => [
                [
                    'person_id' => $local?->manager?->id,
                    'full_name' => $local?->manager?->full_name ?? 'Sin asignar',
                ],
                [
                    'person_id' => $visitor?->manager?->id,
                    'full_name' => $visitor?->manager?->full_name ?? 'Sin asignar',
                ],
            ],
        ];

        return Pdf::loadView('socialevents::minutes.pdf.minutes_match', [
            'acta' => $acta,
        ]);
    }

    /**
     * Planilla en blanco para registrar un partido en cancha.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function matchSheet(int $matchId)
    {
        $match = EventEditionMatch::with([
            'equipolocal.manager',
            'equipovisitante.manager',
            'edicion.evento',
        ])->findOrFail($matchId);

        $edition = $match->edicion;

        $data = [
            'event_name' => $edition?->evento?->title ?? '',
            'edition_name' => $edition?->name ?? '',
            'local_team' => $match->equipolocal?->name ?? 'TBD',
            'visitor_team' => $match->equipovisitante?->name ?? 'TBD',
            'local_manager' => $match->equipolocal?->manager?->formatted_name ?? 'Sin asignar',
            'visitor_manager' => $match->equipovisitante?->manager?->formatted_name ?? 'Sin asignar',
            'match_date' => $match->match_date ? $match->match_date->format('d/m/Y H:i') : null,
            'location' => $match->location,
        ];

        return Pdf::loadView('socialevents::teams.pdf.match_sheet', [
            'data' => $data,
        ]);
    }

    /**
     * Lista de sanciones pendientes con la misma absorción de la doble amarilla
     * que aplica el listado web.
     */
    public function sanctions(int $editionId)
    {
        $edition = EventEdition::with('evento')->findOrFail($editionId);

        $players = EventEditionTeamPlayer::with([
            'team',
            'person',
            'sanctions' => fn ($query) => $query->where('is_paid', false),
        ])
            ->where('edition_id', $editionId)
            ->whereHas('sanctions', fn ($query) => $query->where('is_paid', false))
            ->get();

        $rows = [];
        $total = 0;

        foreach ($players as $player) {
            foreach ($player->sanctions->groupBy('match_id') as $matchSanctions) {
                $hasDoubleYellow = $matchSanctions->where('type', 'double_yellow')->isNotEmpty();

                $effective = $hasDoubleYellow
                    ? $matchSanctions->whereIn('type', ['double_yellow', 'red'])
                    : $matchSanctions;

                foreach ($effective as $sanction) {
                    $amount = (float) $sanction->amount_fine;
                    $total += $amount;

                    $rows[] = [
                        'player_name' => $player->person?->full_name ?? 'Jugador',
                        'team_name' => $player->team?->name ?? 'Equipo',
                        'card' => $this->sanctionLabel($sanction->type),
                        'price' => number_format($amount, 2, '.', ''),
                    ];
                }
            }
        }

        return Pdf::loadView('socialevents::sanctions.pdf.list', [
            'event_name' => $edition->evento?->title ?? '',
            'edicion' => $edition,
            'rows' => $rows,
            'total' => number_format($total, 2, '.', ''),
        ]);
    }

    private function sanctionLabel(?string $type): string
    {
        return match ($type) {
            'yellow' => 'Amarilla',
            'double_yellow' => 'Doble Amarilla',
            'red' => 'Roja Directa',
            default => $type ?? '-',
        };
    }
}
