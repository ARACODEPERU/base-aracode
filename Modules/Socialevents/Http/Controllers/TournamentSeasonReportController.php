<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Services\TournamentSeasonReportService;

/**
 * Reporte de fin de temporada de una edición.
 *
 * La vista permite elegir torneo y edición; las secciones marcadas son las que
 * se imprimen en el PDF. El resumen siempre se calcula con datos reales de la
 * edición, sin depender de la landing publicada.
 */
class TournamentSeasonReportController extends Controller
{
    public function __construct(
        private SportsOrganizationService $sports,
        private TournamentSeasonReportService $reports,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->sports->assertCanRead($user);

        $events = $this->eventsForUser($user);
        $edition = $this->resolveEdition($request, $user, $events);

        $report = $edition ? $this->reports->build($edition) : null;

        return Inertia::render('Socialevents::Editions/SeasonReport', [
            'sportsMode' => $this->sports->sportsMode(),
            'events' => $events->map(fn (EvenEvent $event) => [
                'id' => (int) $event->id,
                'title' => $event->title,
                'editions' => $event->editions->map(fn (EventEdition $edition) => [
                    'id' => (int) $edition->id,
                    'name' => $edition->name,
                    'year' => $edition->year,
                    'status' => $edition->status,
                    'status_label' => $this->statusLabel($edition->status),
                ])->values()->all(),
            ])->values()->all(),
            'selected' => [
                'event_id' => $edition ? (int) $edition->event_id : null,
                'edition_id' => $edition ? (int) $edition->id : null,
            ],
            'report' => $report,
            'sections' => TournamentSeasonReportService::SECTIONS,
        ]);
    }

    public function pdf(Request $request)
    {
        $user = $request->user();
        $this->sports->assertCanRead($user);

        $validator = Validator::make($request->all(), [
            'edition_id' => ['required', 'integer', 'exists:event_editions,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', Rule::in(array_keys(TournamentSeasonReportService::SECTIONS))],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Selecciona al menos una sección para el PDF.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $editionId = (int) $request->input('edition_id');
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::with('evento')->findOrFail($editionId);
        $report = $this->reports->build($edition);
        $sections = array_values(array_unique($request->input('sections', [])));

        $pdf = Pdf::loadView('socialevents::torneos.report-pdf', [
            'report' => $report,
            'sections' => $sections,
        ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = 'reporte-temporada-'.Str::slug((string) $edition->name).'-'.$edition->year.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  Collection<int, EvenEvent>  $events
     */
    private function resolveEdition(Request $request, $user, Collection $events): ?EventEdition
    {
        $editionId = (int) $request->query('edition_id', 0);

        if ($editionId > 0) {
            $this->sports->assertOwnedEdition($user, $editionId);

            return EventEdition::with('evento')->findOrFail($editionId);
        }

        $eventId = (int) $request->query('event_id', 0);

        if ($eventId > 0) {
            $this->sports->assertOwnedEvent($user, $eventId);
        }

        $candidates = $eventId > 0
            ? $events->firstWhere('id', $eventId)?->editions ?? collect()
            : $events->flatMap->editions;

        // Se muestra la edición más reciente para que el reporte abra con datos
        // y el organizador solo tenga que cambiar de temporada si lo necesita.
        return $candidates
            ->sortByDesc(fn (EventEdition $edition) => sprintf(
                '%s-%010d',
                $edition->start_date ?? '0000-00-00',
                (int) $edition->id
            ))
            ->first();
    }

    /**
     * @return Collection<int, EvenEvent>
     */
    private function eventsForUser($user): Collection
    {
        return EvenEvent::query()
            ->when(
                $this->sports->sportsMode() && ! $this->sports->isSuperAdmin($user),
                fn ($query) => $query->where('organization_id', $this->sports->organizationId($user))
            )
            ->whereHas('editions')
            ->with(['editions' => fn ($query) => $query->orderByDesc('start_date')->orderByDesc('id')])
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    private function statusLabel(?string $status): string
    {
        return [
            'pending' => 'Próximamente',
            'in_progress' => 'En juego',
            'finished' => 'Finalizada',
        ][$status] ?? ($status ?: 'Sin estado');
    }
}
