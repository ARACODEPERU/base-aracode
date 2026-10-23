<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Services\EditionPdfService;
use Modules\Socialevents\Services\SportsOrganizationService;

/**
 * Reportes de la edición para la app móvil (Fase 5C).
 *
 * El listado es de solo lectura y funciona incluso con el acceso vencido. Los
 * PDF se generan con las vistas de la web y se entregan por una URL firmada de
 * corta duración: la app abre el documento en el navegador y el servidor no
 * deja copias públicas de las actas.
 */
class EditionReportsApiController extends Controller
{
    private const TYPES = ['acta', 'planilla', 'sanciones'];

    private const EXPIRES_MINUTES = 30;

    public function __construct(
        private SportsOrganizationService $sports,
        private EditionPdfService $pdfs
    ) {}

    /**
     * Actas cerradas, partidos para imprimir planillas y conteos de la edición.
     */
    public function index(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanRead($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $edition = EventEdition::with('evento')->findOrFail($editionId);

        $matches = EventEditionMatch::with([
            'equipolocal',
            'equipovisitante',
            'report',
        ])
            ->where('edition_id', $editionId)
            ->orderBy('match_date')
            ->orderBy('id')
            ->get();

        $reports = EventEditionMatchReport::with([
            'partido.equipolocal',
            'partido.equipovisitante',
        ])
            ->whereHas('partido', fn ($query) => $query->where('edition_id', $editionId))
            ->get()
            ->sortByDesc(fn ($report) => $report->partido?->match_date?->timestamp
                ?? $report->created_at?->timestamp
                ?? 0)
            ->values();

        $finishedWithoutReport = $matches->filter(function (EventEditionMatch $match) {
            $hasScore = $match->score_h !== null && $match->score_a !== null;

            return $hasScore
                && ! in_array($match->status, ['cancelled', 'no_points'], true)
                && ! $match->report;
        })->count();

        return response()->json([
            'success' => true,
            'message' => 'Reportes de la edición obtenidos correctamente',
            'data' => [
                'edition' => [
                    'id' => (int) $edition->id,
                    'name' => $edition->name,
                    'event_title' => $edition->evento?->title ?? '',
                    'landing_url' => $edition->landingUrl(),
                ],
                'summary' => [
                    'matches_total' => $matches->count(),
                    'reports_total' => $reports->count(),
                    'matches_without_report' => $finishedWithoutReport,
                ],
                'reports' => $reports->map(fn ($report) => $this->reportPayload($report))->values(),
                'matches' => $matches->map(fn ($match) => $this->matchPayload($match))->values(),
                'pdf_types' => self::TYPES,
            ],
        ]);
    }

    /**
     * Prepara la URL firmada del PDF pedido. No genera el documento todavía:
     * eso ocurre cuando el navegador abre el enlace.
     */
    public function pdf(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanRead($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'report_id' => 'nullable|required_if:type,acta|integer',
            'match_id' => 'nullable|required_if:type,planilla|integer',
        ], [
            'type.in' => 'El tipo de documento elegido no está disponible.',
            'report_id.required_if' => 'Selecciona el acta que quieres descargar.',
            'match_id.required_if' => 'Selecciona el partido de la planilla.',
        ]);

        $type = $validated['type'];
        $parameters = [
            'editionId' => $editionId,
            'type' => $type,
        ];

        if ($type === 'acta') {
            $report = EventEditionMatchReport::with('partido')->findOrFail((int) $validated['report_id']);
            abort_unless((int) $report->partido?->edition_id === $editionId, 404);
            $parameters['report'] = (int) $report->id;
        }

        if ($type === 'planilla') {
            $match = EventEditionMatch::findOrFail((int) $validated['match_id']);
            abort_unless((int) $match->edition_id === $editionId, 404);
            $parameters['match'] = (int) $match->id;
        }

        return response()->json([
            'success' => true,
            'message' => 'Documento preparado correctamente',
            'data' => [
                'type' => $type,
                'url' => URL::temporarySignedRoute(
                    'api.reports.pdf',
                    now()->addMinutes(self::EXPIRES_MINUTES),
                    $parameters
                ),
                'expires_in' => self::EXPIRES_MINUTES * 60,
            ],
        ]);
    }

    /**
     * Entrega el PDF de una URL firmada. No usa sesión: la firma demuestra que
     * el organizador pidió el documento y caduca a los 30 minutos.
     */
    public function download(Request $request, int $editionId, string $type)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        EventEdition::findOrFail($editionId);

        if ($type === 'acta') {
            $report = EventEditionMatchReport::with('partido')->findOrFail((int) $request->query('report'));
            abort_unless((int) $report->partido?->edition_id === $editionId, 404);

            $code = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $report->minutes_code) ?: (string) $report->id;

            return $this->pdfs->acta((int) $report->id)->stream('acta_'.$code.'.pdf');
        }

        if ($type === 'planilla') {
            $match = EventEditionMatch::findOrFail((int) $request->query('match'));
            abort_unless((int) $match->edition_id === $editionId, 404);

            return $this->pdfs->matchSheet((int) $match->id)
                ->stream('planilla_partido_'.$match->id.'.pdf');
        }

        return $this->pdfs->sanctions($editionId)
            ->stream('sanciones_edicion_'.$editionId.'.pdf');
    }

    /** @return array<string, mixed> */
    private function reportPayload(EventEditionMatchReport $report): array
    {
        $match = $report->partido;
        $local = $match?->equipolocal;
        $visitor = $match?->equipovisitante;

        $closedAt = $report->closed_at
            ? Carbon::parse($report->closed_at)
            : $report->created_at;

        return [
            'id' => (int) $report->id,
            'minutes_code' => $report->minutes_code,
            'match_id' => (int) $report->match_id,
            'local_team' => $local?->name ?? 'Local',
            'visitor_team' => $visitor?->name ?? 'Visitante',
            'match_label' => ($local?->name ?? 'Local').' vs '.($visitor?->name ?? 'Visitante'),
            'local_score' => (int) $report->local_score,
            'visitor_score' => (int) $report->visitor_score,
            'match_date' => $match?->match_date?->format('Y-m-d H:i'),
            'closed_at' => $closedAt?->format('Y-m-d H:i'),
            'status' => $report->protest_status === 'pending' ? 'pending' : 'closed',
            'status_label' => $report->protest_status === 'pending' ? 'Reclamo pendiente' : 'Cerrada',
            'has_protest' => (bool) $report->has_protest,
            'protest_status' => $report->protest_status,
            'protest_status_label' => [
                'none' => 'Sin reclamo',
                'pending' => 'Reclamo pendiente',
                'resolved' => 'Reclamo resuelto',
                'rejected' => 'Reclamo rechazado',
            ][$report->protest_status] ?? null,
            'has_minutes_file' => filled($report->minutes_file),
        ];
    }

    /** @return array<string, mixed> */
    private function matchPayload(EventEditionMatch $match): array
    {
        $local = $match->equipolocal?->name ?? $match->placeholder_h ?? 'Local';
        $visitor = $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Visitante';
        $round = $match->round_number ? 'Fecha '.$match->round_number.' · ' : '';
        $date = $match->match_date?->format('d/m/Y H:i');

        return [
            'id' => (int) $match->id,
            'local_team' => $local,
            'visitor_team' => $visitor,
            'label' => $round.$local.' vs '.$visitor.($date ? ' · '.$date : ''),
            'match_date' => $match->match_date?->format('Y-m-d H:i'),
            'status' => $match->status,
            'has_report' => (bool) $match->report,
        ];
    }
}
