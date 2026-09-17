<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Commercial\Entities\CommercialContract;
use Modules\Commercial\Entities\CommercialContractPayment;

class CommercialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $canClients = $user && $user->can('comm_clientes_listado');
        $canContracts = $user && $user->can('comm_contratos_listado');
        $canSchedule = $user && $user->can('comm_contratos_cronograma');

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $nextThirtyDays = $today->copy()->addDays(30);

        $payments = CommercialContractPayment::query();
        $contracts = CommercialContract::query();

        $monthLabels = [];
        $projected = [];
        $collected = [];

        if ($canSchedule) {
            for ($index = 5; $index >= 0; $index--) {
                $date = $today->copy()->subMonths($index);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                $monthLabels[] = $date->locale('es')->isoFormat('MMM');
                $projected[] = round((float) CommercialContractPayment::whereBetween('due_date', [$start, $end])->sum('total_amount'), 2);
                $collected[] = round((float) CommercialContractPayment::whereBetween('paid_at', [$start, $end])->sum('total_amount'), 2);
            }
        }

        $statusLabels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'overdue' => 'Vencido',
            'partial' => 'Parcial',
            'amortized' => 'Amortizado',
            'cancelled' => 'Anulado',
        ];

        $contractTypeLabels = [
            'new_development' => 'Nuevo desarrollo',
            'maintenance' => 'Mantenimiento',
            'rental' => 'Alquiler',
        ];

        $paymentStatus = collect();
        if ($canSchedule) {
            $paymentStatus = CommercialContractPayment::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get()
                ->map(fn ($item) => [
                    'label' => $statusLabels[$item->status] ?? $item->status,
                    'value' => (int) $item->total,
                ])
                ->values();
        }

        $contractTypes = collect();
        if ($canContracts) {
            $contractTypes = CommercialContract::selectRaw('contract_type, COUNT(*) as total')
                ->groupBy('contract_type')
                ->get()
                ->map(fn ($item) => [
                    'label' => $contractTypeLabels[$item->contract_type] ?? $item->contract_type,
                    'value' => (int) $item->total,
                ])
                ->values();
        }

        $upcomingPayments = $canSchedule
            ? CommercialContractPayment::with(['contract.client', 'contract.service'])
                ->whereIn('status', ['pending', 'partial', 'amortized'])
                ->whereBetween('due_date', [$today, $nextThirtyDays])
                ->orderBy('due_date')
                ->limit(8)
                ->get()
                ->map(fn ($payment) => $this->paymentRow($payment))
            : collect();

        $overduePayments = $canSchedule
            ? CommercialContractPayment::with(['contract.client', 'contract.service'])
                ->whereIn('status', ['pending', 'partial', 'amortized', 'overdue'])
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->limit(8)
                ->get()
                ->map(fn ($payment) => $this->paymentRow($payment))
            : collect();

        $expiringContracts = $canContracts
            ? CommercialContract::with(['client', 'service'])
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $nextThirtyDays)
                ->orderBy('end_date')
                ->limit(8)
                ->get()
                ->map(fn ($contract) => [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'client' => $contract->client?->full_name,
                    'service' => $contract->service?->description,
                    'end_date' => $contract->end_date,
                    'days_left' => $today->diffInDays(Carbon::parse($contract->end_date), false),
                ])
            : collect();

        $recentClients = $canClients
            ? Person::where('is_client', true)
                ->latest()
                ->limit(5)
                ->get(['id', 'full_name', 'number', 'telephone', 'created_at'])
            : collect();

        $recentContracts = $canContracts
            ? CommercialContract::with(['client', 'service'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($contract) => [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'client' => $contract->client?->full_name,
                    'service' => $contract->service?->description,
                    'amount' => (float) $contract->amount,
                    'currency' => $contract->currency,
                    'created_at' => $contract->created_at?->format('Y-m-d'),
                ])
            : collect();

        return Inertia::render('Commercial::Dashboard', [
            'metrics' => [
                'clients' => $canClients ? Person::where('is_client', true)->count() : 0,
                'activeContracts' => $canContracts ? (clone $contracts)->where('status', true)->count() : 0,
                'expiringContracts' => $canContracts ? CommercialContract::whereNotNull('end_date')
                    ->whereDate('end_date', '>=', $today)
                    ->whereDate('end_date', '<=', $nextThirtyDays)
                    ->count() : 0,
                'pendingAmount' => $canSchedule ? round((float) CommercialContractPayment::whereIn('status', ['pending', 'partial', 'amortized', 'overdue'])->sum('balance_amount'), 2) : 0,
                'overdueAmount' => $canSchedule ? round((float) CommercialContractPayment::whereIn('status', ['pending', 'partial', 'amortized', 'overdue'])->whereDate('due_date', '<', $today)->sum('balance_amount'), 2) : 0,
                'collectedThisMonth' => $canSchedule ? round((float) CommercialContractPayment::where('status', 'paid')->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('total_amount'), 2) : 0,
                'paymentsDueToday' => $canSchedule ? (clone $payments)->whereIn('status', ['pending', 'partial', 'amortized'])->whereDate('due_date', $today)->count() : 0,
                'contractsWithoutSchedule' => $canContracts ? CommercialContract::doesntHave('payments')->count() : 0,
            ],
            'permissions' => [
                'clients' => $canClients,
                'contracts' => $canContracts,
                'schedule' => $canSchedule,
            ],
            'charts' => [
                'cashflow' => [
                    'labels' => $monthLabels,
                    'projected' => $projected,
                    'collected' => $collected,
                ],
                'paymentStatus' => $paymentStatus,
                'contractTypes' => $contractTypes,
            ],
            'tables' => [
                'upcomingPayments' => $upcomingPayments,
                'overduePayments' => $overduePayments,
                'expiringContracts' => $expiringContracts,
                'recentClients' => $recentClients,
                'recentContracts' => $recentContracts,
            ],
        ]);
    }

    private function paymentRow(CommercialContractPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'contract_id' => $payment->contract_id,
            'client' => $payment->contract?->client?->full_name,
            'service' => $payment->contract?->service?->description,
            'description' => $payment->description,
            'due_date' => $payment->due_date,
            'total_amount' => (float) $payment->total_amount,
            'balance_amount' => (float) ($payment->balance_amount ?: $payment->total_amount),
            'currency' => $payment->currency,
            'status' => $payment->status,
        ];
    }
}
