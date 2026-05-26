<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SaleDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Sales\Entities\SaleLowCommunication;
use Modules\Sales\Entities\SaleSummary;

class InvoiceReportsController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        return Inertia::render('Sales::Reports/InvoiceDashboard', [
            'metrics' => $this->buildMetrics($today, $monthStart, $monthEnd),
            'charts' => [
                'emissionTrend' => $this->buildEmissionTrendChart($today->copy()->subDays(6), $today),
                'sunatStatus' => $this->buildSunatStatusChart($monthStart, $monthEnd),
            ],
            'tables' => [
                'recentDocuments' => $this->buildRecentDocumentsTable(),
                'recentBatches' => $this->buildRecentBatchesTable(),
                'alerts' => $this->buildAlertsTable(),
            ],
        ]);
    }

    private function electronicDocumentsQuery(bool $activeOnly = false)
    {
        $query = SaleDocument::query()
            ->whereIn('invoice_type_doc', ['01', '03', '07', '08'])
            ->whereHas('sale', function ($saleQuery) {
                $saleQuery->where('physical', 2);
            });

        if ($activeOnly) {
            $query->where('status', 1);
        }

        if (! $this->canReviewAllDocuments()) {
            $query->whereHas('sale', function ($saleQuery) {
                $saleQuery->where('user_id', Auth::id());
            });
        }

        return $query;
    }

    private function canReviewAllDocuments(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'Contabilidad']);
    }

    private function buildMetrics(Carbon $today, Carbon $monthStart, Carbon $monthEnd): array
    {
        $todayStats = (clone $this->electronicDocumentsQuery(true))
            ->whereDate('invoice_broadcast_date', $today->toDateString())
            ->selectRaw('COUNT(*) as documents_count, COALESCE(SUM(overall_total), 0) as total_amount')
            ->first();

        $monthStats = (clone $this->electronicDocumentsQuery(true))
            ->whereBetween('invoice_broadcast_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('COUNT(*) as documents_count, COALESCE(SUM(overall_total), 0) as total_amount')
            ->first();

        $pendingSunatCount = (clone $this->electronicDocumentsQuery(true))
            ->whereIn('invoice_status', ['registrado', 'Pendiente', 'Enviada'])
            ->count();

        $notesCount = (clone $this->electronicDocumentsQuery(true))
            ->whereBetween('invoice_broadcast_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('invoice_type_doc', ['07', '08'])
            ->count();

        $rejectedCount = (clone $this->electronicDocumentsQuery(true))
            ->where('invoice_status', 'Rechazada')
            ->count();

        return [
            'todayTotal' => round((float) ($todayStats->total_amount ?? 0), 2),
            'todayCount' => (int) ($todayStats->documents_count ?? 0),
            'monthTotal' => round((float) ($monthStats->total_amount ?? 0), 2),
            'monthCount' => (int) ($monthStats->documents_count ?? 0),
            'pendingSunatCount' => $pendingSunatCount,
            'notesCount' => $notesCount,
            'rejectedCount' => $rejectedCount,
        ];
    }

    private function buildEmissionTrendChart(Carbon $startDate, Carbon $endDate): array
    {
        $documentsByDay = (clone $this->electronicDocumentsQuery(true))
            ->whereBetween('invoice_broadcast_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('DATE(invoice_broadcast_date) as emission_day, COUNT(*) as documents_count, COALESCE(SUM(overall_total), 0) as total_amount')
            ->groupBy('emission_day')
            ->orderBy('emission_day')
            ->get()
            ->keyBy('emission_day');

        $labels = [];
        $totals = [];
        $counts = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $key = $cursor->toDateString();
            $dayData = $documentsByDay->get($key);

            $labels[] = $cursor->format('d/m');
            $totals[] = round((float) ($dayData->total_amount ?? 0), 2);
            $counts[] = (int) ($dayData->documents_count ?? 0);

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
            'counts' => $counts,
        ];
    }

    private function buildSunatStatusChart(Carbon $monthStart, Carbon $monthEnd): array
    {
        $statusGroups = [
            'Aceptados' => 0,
            'Pendientes' => 0,
            'Rechazados' => 0,
            'Por anular' => 0,
            'Otros' => 0,
        ];

        $statuses = (clone $this->electronicDocumentsQuery())
            ->whereBetween('invoice_broadcast_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->pluck('invoice_status');

        foreach ($statuses as $status) {
            $normalizedStatus = trim((string) $status);

            if ($normalizedStatus === 'Aceptada') {
                $statusGroups['Aceptados']++;
            } elseif (in_array($normalizedStatus, ['registrado', 'Pendiente', 'Enviada'], true)) {
                $statusGroups['Pendientes']++;
            } elseif ($normalizedStatus === 'Rechazada') {
                $statusGroups['Rechazados']++;
            } elseif (in_array($normalizedStatus, ['Enviada Por Anular', 'por anular'], true)) {
                $statusGroups['Por anular']++;
            } else {
                $statusGroups['Otros']++;
            }
        }

        return [
            'items' => collect($statusGroups)
                ->filter(fn ($value) => $value > 0)
                ->map(fn ($value, $label) => [
                    'label' => $label,
                    'value' => $value,
                ])
                ->values()
                ->all(),
        ];
    }

    private function buildRecentDocumentsTable(): array
    {
        return (clone $this->electronicDocumentsQuery())
            ->with('sale:id,user_id')
            ->orderByDesc('invoice_broadcast_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get([
                'id',
                'client_rzn_social',
                'invoice_type_doc',
                'invoice_serie',
                'invoice_correlative',
                'invoice_broadcast_date',
                'invoice_status',
                'overall_total',
                'status',
            ])
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $this->documentTypeLabel($document->invoice_type_doc),
                    'number' => $this->formatDocumentNumber($document->invoice_serie, $document->invoice_correlative),
                    'client' => $document->client_rzn_social ?: 'Cliente no disponible',
                    'date' => $document->invoice_broadcast_date,
                    'status' => $document->invoice_status ?: 'Sin estado',
                    'total' => round((float) $document->overall_total, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function buildRecentBatchesTable(): array
    {
        $summaries = SaleSummary::query()
            ->when(! $this->canReviewAllDocuments(), function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest('summary_date')
            ->limit(4)
            ->get(['id', 'correlative', 'status', 'summary_date'])
            ->map(function ($summary) {
                return [
                    'id' => 'summary-' . $summary->id,
                    'type' => 'Resumen diario',
                    'code' => 'RC-' . str_pad((string) ($summary->correlative ?? $summary->id), 5, '0', STR_PAD_LEFT),
                    'status' => $summary->status ?: 'registrado',
                    'date' => $summary->summary_date,
                ];
            });

        $communications = SaleLowCommunication::query()
            ->when(! $this->canReviewAllDocuments(), function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest('communication_date')
            ->limit(4)
            ->get(['id', 'correlative', 'status', 'communication_date'])
            ->map(function ($communication) {
                return [
                    'id' => 'low-' . $communication->id,
                    'type' => 'Comunicacion de baja',
                    'code' => 'RA-' . str_pad((string) ($communication->correlative ?? $communication->id), 5, '0', STR_PAD_LEFT),
                    'status' => $communication->status ?: 'registrado',
                    'date' => $communication->communication_date,
                ];
            });

        return $summaries
            ->concat($communications)
            ->sortByDesc('date')
            ->take(6)
            ->values()
            ->all();
    }

    private function buildAlertsTable(): array
    {
        $pendingSummaryCount = (clone $this->electronicDocumentsQuery(true))
            ->where('invoice_type_doc', '03')
            ->where('invoice_status', 'Pendiente')
            ->count();

        $pendingLowCommunicationCount = (clone $this->electronicDocumentsQuery())
            ->whereIn('invoice_status', ['Enviada Por Anular', 'por anular'])
            ->count();

        $rejectedDocuments = (clone $this->electronicDocumentsQuery(true))
            ->where('invoice_status', 'Rechazada')
            ->orderByDesc('invoice_broadcast_date')
            ->limit(4)
            ->get([
                'id',
                'invoice_type_doc',
                'invoice_serie',
                'invoice_correlative',
                'invoice_response_description',
            ])
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $this->documentTypeLabel($document->invoice_type_doc),
                    'number' => $this->formatDocumentNumber($document->invoice_serie, $document->invoice_correlative),
                    'reason' => $document->invoice_response_description ?: 'Sin detalle registrado.',
                ];
            })
            ->values()
            ->all();

        return [
            'pendingSummaryCount' => $pendingSummaryCount,
            'pendingLowCommunicationCount' => $pendingLowCommunicationCount,
            'rejectedDocumentsCount' => count($rejectedDocuments),
            'rejectedDocuments' => $rejectedDocuments,
        ];
    }

    private function documentTypeLabel(?string $type): string
    {
        return match ($type) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de credito',
            '08' => 'Nota de debito',
            default => 'Documento',
        };
    }

    private function formatDocumentNumber(?string $serie, $correlative): string
    {
        $serie = $serie ?: 'DOC';
        $correlative = $correlative ?: 0;

        return $serie . '-' . str_pad((string) $correlative, 8, '0', STR_PAD_LEFT);
    }
}
