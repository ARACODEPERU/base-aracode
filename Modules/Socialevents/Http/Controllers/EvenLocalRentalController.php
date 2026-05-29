<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\LocalSale;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Entities\EvenLocalRental;
use Modules\Socialevents\Entities\EvenLocalRentalExtraCharge;
use Modules\Socialevents\Entities\EvenLocalRentalRate;
use Modules\Socialevents\Http\Requests\FormalizeRentalAdvanceRequest;
use Modules\Socialevents\Http\Requests\StoreRentalExtraChargeRequest;
use Modules\Socialevents\Http\Requests\StoreRentalPaymentRequest;
use Modules\Socialevents\Http\Requests\StoreRentalRateRequest;
use Modules\Socialevents\Http\Requests\StoreRentalRequest;
use Modules\Socialevents\Http\Requests\UpdateRentalRequest;
use Modules\Socialevents\Http\Requests\UpdateRentalStatusRequest;
use Modules\Socialevents\Services\LocalRentalPricingService;
use Modules\Socialevents\Services\LocalRentalSaleService;

class EvenLocalRentalController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Socialevents::LocalRentals/List', [
            'rentals' => fn () => EvenLocalRental::with(['local', 'customer', 'extraCharges'])
                ->when(request('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('customer', function ($sub) use ($search) {
                            $sub->where('full_name', 'like', "%{$search}%");
                        })->orWhereHas('local', function ($sub) use ($search) {
                            $sub->where('description', 'like', "%{$search}%");
                        });
                    });
                })
                ->when(request('local_id'), fn ($query, $localId) => $query->where('local_id', $localId))
                ->when(request('event_date'), fn ($query, $date) => $query->whereDate('event_date', $date))
                ->latest('created_at')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'locals' => fn () => LocalSale::orderBy('description')->get(['id', 'description']),
            'activeRates' => fn () => EvenLocalRentalRate::where('is_active', true)
                ->with('local:id,description')
                ->get(),
            'filters' => fn () => request()->only('search', 'local_id', 'event_date'),
            'eventTypes' => fn () => EvenLocalRental::eventTypeOptions(),
            'reservationStatuses' => fn () => EvenLocalRental::reservationStatusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Socialevents::LocalRentals/Create', $this->formSharedProps());
    }

    public function edit(int $id): Response
    {
        $rental = EvenLocalRental::with(['local', 'customer', 'rate', 'extraCharges'])
            ->findOrFail($id);

        return Inertia::render('Socialevents::LocalRentals/Edit', array_merge($this->formSharedProps(), [
            'rental' => $rental,
        ]));
    }

    public function ratesByLocal(int $localId): JsonResponse
    {
        $rates = EvenLocalRentalRate::query()
            ->where('local_id', $localId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'local_id', 'name', 'hourly_rate', 'is_active']);

        return response()->json([
            'success' => true,
            'rates' => $rates,
        ]);
    }

    public function storeRate(StoreRentalRateRequest $request): JsonResponse
    {
        $rate = EvenLocalRentalRate::create([
            'local_id' => $request->input('local_id'),
            'name' => $request->input('name'),
            'hourly_rate' => $request->input('hourly_rate'),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'rate' => $rate->only(['id', 'local_id', 'name', 'hourly_rate', 'is_active']),
        ]);
    }

    public function store(StoreRentalRequest $request, LocalRentalPricingService $pricingService): RedirectResponse
    {
        $snapshot = $request->pricingSnapshot();

        DB::transaction(function () use ($request, $pricingService, $snapshot) {
            $rental = EvenLocalRental::create([
                'local_id' => $request->input('local_id'),
                'customer_id' => $request->input('customer_id'),
                'rental_rate_id' => $request->input('rental_rate_id'),
                'event_type' => $request->input('event_type'),
                'event_date' => $request->input('event_date'),
                'event_end_date' => $request->input('event_end_date'),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'total_hours' => $snapshot['total_hours'],
                'hourly_rate' => $snapshot['hourly_rate'],
                'base_amount' => $snapshot['base_amount'],
                'includes_tables_chairs' => $request->boolean('includes_tables_chairs'),
                'includes_food' => $request->boolean('includes_food'),
                'beer_provided_by' => $request->input('beer_provided_by'),
                'total_price' => $snapshot['total_price'],
                'paid_amount' => $snapshot['paid_amount'],
                'balance_amount' => $snapshot['balance_amount'],
                'payment_status' => $snapshot['payment_status'],
                'reservation_status' => 'pending',
                'notes' => $request->input('notes'),
            ]);

            $this->syncBookingExtras($rental, $request->input('extras', []));
            $pricingService->recalculateTotals($rental);
        });

        return redirect()->route('even_alquiler_local_index');
    }

    public function update(int $id, UpdateRentalRequest $request, LocalRentalPricingService $pricingService): RedirectResponse
    {
        $rental = EvenLocalRental::findOrFail($id);
        $snapshot = $request->pricingSnapshot();

        DB::transaction(function () use ($request, $pricingService, $snapshot, $rental) {
            $rental->update([
                'local_id' => $request->input('local_id'),
                'customer_id' => $request->input('customer_id'),
                'rental_rate_id' => $request->input('rental_rate_id'),
                'event_type' => $request->input('event_type'),
                'event_date' => $request->input('event_date'),
                'event_end_date' => $request->input('event_end_date'),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'total_hours' => $snapshot['total_hours'],
                'hourly_rate' => $snapshot['hourly_rate'],
                'base_amount' => $snapshot['base_amount'],
                'includes_tables_chairs' => $request->boolean('includes_tables_chairs'),
                'includes_food' => $request->boolean('includes_food'),
                'beer_provided_by' => $request->input('beer_provided_by'),
                'notes' => $request->input('notes'),
            ]);

            $rental->extraCharges()->where('phase', EvenLocalRentalExtraCharge::PHASE_BOOKING)->delete();
            $this->syncBookingExtras($rental, $request->input('extras', []));
            $pricingService->recalculateTotals($rental->fresh());
        });

        return redirect()->route('even_alquiler_local_index');
    }

    public function updateStatus(int $id, UpdateRentalStatusRequest $request): JsonResponse
    {
        $rental = EvenLocalRental::findOrFail($id);
        $rental->update(['reservation_status' => $request->input('reservation_status')]);

        return response()->json([
            'success' => true,
            'rental' => $rental->fresh(['local', 'customer']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $rental = EvenLocalRental::with('payments')->findOrFail($id);

        if ($rental->payments()->whereNotNull('sale_id')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: la reserva tiene notas de venta emitidas. Cancele la reserva en su lugar.',
            ], 422);
        }

        $rental->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reserva eliminada correctamente.',
        ]);
    }

    public function show(int $id): Response
    {
        $rental = EvenLocalRental::with([
            'local',
            'customer',
            'rate',
            'extraCharges.addedBy',
            'payments.sale',
        ])->findOrFail($id);

        return Inertia::render('Socialevents::LocalRentals/Show', [
            'rental' => $rental,
            'eventTypes' => EvenLocalRental::eventTypeOptions(),
            'reservationStatuses' => EvenLocalRental::reservationStatusOptions(),
            'paymentMethods' => PaymentMethod::orderBy('description')->get(['id', 'description']),
        ]);
    }

    public function payments(int $id, LocalRentalSaleService $saleService): JsonResponse
    {
        $rental = EvenLocalRental::findOrFail($id);

        return response()->json(array_merge($saleService->buildPaymentsPayload($rental), [
            'payment_methods' => PaymentMethod::orderBy('description')->get(['id', 'description']),
        ]));
    }

    public function storePayment(int $id, StoreRentalPaymentRequest $request, LocalRentalSaleService $saleService): JsonResponse
    {
        $rental = EvenLocalRental::findOrFail($id);

        try {
            $result = $saleService->registerPayment($rental, $request->validated());

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function formalizeAdvance(int $id, FormalizeRentalAdvanceRequest $request, LocalRentalSaleService $saleService): JsonResponse
    {
        $rental = EvenLocalRental::findOrFail($id);

        try {
            return response()->json($saleService->formalizeAdvance($rental, $request->validated()));
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeExtra(
        StoreRentalExtraChargeRequest $request,
        int $id,
        LocalRentalPricingService $pricingService
    ): RedirectResponse {
        $rental = EvenLocalRental::findOrFail($id);

        $pricingService->addExtraCharge($rental, [
            'description' => $request->input('description'),
            'amount' => $request->input('amount'),
            'phase' => EvenLocalRentalExtraCharge::PHASE_DURING_EVENT,
            'reason' => $request->input('reason'),
            'added_by' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('even_alquiler_local_show', $id)
            ->with('flash', [
                'message' => 'Cargo agregado. Nuevo saldo: S/ ' . number_format($rental->fresh()->balance_amount, 2),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formSharedProps(): array
    {
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id', ['6'])->get();

        return [
            'locals' => LocalSale::orderBy('description')->get(['id', 'description']),
            'activeRates' => EvenLocalRentalRate::where('is_active', true)
                ->with('local:id,description')
                ->get(),
            'eventTypes' => EvenLocalRental::eventTypeOptions(),
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $extras
     */
    private function syncBookingExtras(EvenLocalRental $rental, array $extras): void
    {
        foreach ($extras as $extra) {
            $rental->extraCharges()->create([
                'description' => $extra['description'],
                'amount' => $extra['amount'],
                'phase' => EvenLocalRentalExtraCharge::PHASE_BOOKING,
                'reason' => $extra['reason'] ?? 'planned',
            ]);
        }
    }
}
