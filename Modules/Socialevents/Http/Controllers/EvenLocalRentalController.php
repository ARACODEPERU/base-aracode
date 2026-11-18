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
use Modules\Socialevents\Entities\EvenLocal;
use Modules\Socialevents\Entities\EvenLocalArea;
use Modules\Socialevents\Entities\EvenLocalRental;
use Modules\Socialevents\Entities\EvenLocalRentalExtraCharge;
use Modules\Socialevents\Entities\EvenLocalRentalRate;
use Modules\Socialevents\Http\Requests\FormalizeRentalAdvanceRequest;
use Modules\Socialevents\Http\Requests\StoreAreaRequest;
use Modules\Socialevents\Http\Requests\UpdateAreaRequest;
use Modules\Socialevents\Http\Requests\StoreRentalExtraChargeRequest;
use Modules\Socialevents\Http\Requests\StoreRentalPaymentRequest;
use Modules\Socialevents\Http\Requests\StoreRentalRateRequest;
use Modules\Socialevents\Http\Requests\StoreRentalRequest;
use Modules\Socialevents\Http\Requests\UpdateRentalRequest;
use Modules\Socialevents\Http\Requests\UpdateRentalStatusRequest;
use Modules\Socialevents\Services\LocalRentalPricingService;
use Modules\Socialevents\Services\LocalRentalSaleService;
use Modules\Socialevents\Services\LocalRentalScheduleService;

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
        $rental = EvenLocalRental::with(['local', 'customer', 'rate', 'extraCharges', 'items.area', 'items.rate'])
            ->findOrFail($id);

        return Inertia::render('Socialevents::LocalRentals/Edit', array_merge($this->formSharedProps(), [
            'rental' => $rental,
        ]));
    }

    public function areasByLocal(int $localId): JsonResponse
    {
        $areas = EvenLocalArea::query()
            ->where('local_id', $localId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'local_id', 'name', 'area_type', 'capacity', 'is_active']);

        return response()->json([
            'success' => true,
            'areas' => $areas,
        ]);
    }

    public function storeArea(StoreAreaRequest $request): JsonResponse
    {
        $area = EvenLocalArea::create([
            'local_id' => $request->input('local_id'),
            'name' => $request->input('name'),
            'area_type' => $request->input('area_type'),
            'capacity' => $request->input('capacity'),
            'is_active' => true,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return response()->json([
            'success' => true,
            'area' => $area->only(['id', 'local_id', 'name', 'area_type', 'capacity', 'is_active']),
        ]);
    }

    /**
     * Renombra un área (nombre, tipo y aforo). El local no cambia.
     */
    public function updateArea(int $id, UpdateAreaRequest $request): JsonResponse
    {
        $area = EvenLocalArea::findOrFail($id);

        $area->update([
            'name' => $request->input('name'),
            'area_type' => $request->input('area_type'),
            'capacity' => $request->input('capacity'),
        ]);

        // El horario solo existe en campos deportivos; en otros tipos se limpia.
        if ($area->area_type === EvenLocalArea::TYPE_SPORTS_FIELD) {
            $area->schedules()->delete();

            foreach ($request->input('schedules', []) as $schedule) {
                $area->schedules()->create([
                    'day_of_week' => $schedule['day_of_week'],
                    'open_time' => $schedule['open_time'],
                    'close_time' => $schedule['close_time'],
                ]);
            }
        } else {
            $area->schedules()->delete();
        }

        return response()->json([
            'success' => true,
            'area' => $area->fresh()->load('schedules')->only(['id', 'local_id', 'name', 'area_type', 'capacity', 'is_active', 'schedules']),
        ]);
    }

    /**
     * Elimina un área. Si ya tiene reservas, no se borra para no perder el historial:
     * solo se desactiva y deja de aparecer para nuevas reservas.
     */
    public function destroyArea(int $id): JsonResponse
    {
        $area = EvenLocalArea::findOrFail($id);

        if ($area->items()->exists() || $area->rates()->exists()) {
            $area->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'El área tiene reservas registradas; se quitó de la lista para nuevas reservas.',
                'deactivated' => true,
            ]);
        }

        $area->delete();

        return response()->json([
            'success' => true,
            'message' => 'Área eliminada correctamente.',
            'deactivated' => false,
        ]);
    }

    public function ratesByLocal(int $localId): JsonResponse
    {
        $rates = EvenLocalRentalRate::query()
            ->where('local_id', $localId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'local_id', 'area_id', 'name', 'hourly_rate', 'is_active']);

        return response()->json([
            'success' => true,
            'rates' => $rates,
        ]);
    }

    public function storeRate(StoreRentalRateRequest $request): JsonResponse
    {
        $rate = EvenLocalRentalRate::create([
            'local_id' => $request->input('local_id'),
            'area_id' => $request->input('area_id'),
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
        // El total sale del monto fijo del torneo o de las líneas por hora; ya validado en la request.
        $totalPrice = $request->input('tournament')
            ? $request->tournamentTotals()['total_price']
            : $request->lineSnapshotTotals()['total_price'];
        $paidAmount = round((float) $request->input('paid_amount'), 2);

        $totals = [
            'total_price' => $totalPrice,
            'paid_amount' => $paidAmount,
            'balance_amount' => round($totalPrice - $paidAmount, 2),
            'payment_status' => $pricingService->resolvePaymentStatus($totalPrice, $paidAmount),
        ];

        DB::transaction(function () use ($request, $pricingService, $totals) {
            if ($request->input('tournament')) {
                $this->storeTournament($request, $totals);

                return;
            }

            $rental = $this->createHeader($request, $totals, 'pending');

            $this->syncItems($rental, $request->input('areas', []));
            $pricingService->recalculateTotals($rental);
        });

        return redirect()->route('even_alquiler_local_index');
    }

    public function update(int $id, UpdateRentalRequest $request, LocalRentalPricingService $pricingService): RedirectResponse
    {
        $rental = EvenLocalRental::findOrFail($id);

        // La edición por horas no reescribe un torneo: su serie y monto se gestionan al crearlo.
        if ($rental->is_tournament) {
            return redirect()->route('even_alquiler_local_index')
                ->with('error', 'Los torneos no se editan por horas.');
        }

        DB::transaction(function () use ($request, $pricingService, $rental) {
            $rental->update([
                'local_id' => $request->input('local_id'),
                'customer_id' => $request->input('customer_id'),
                'event_type' => $request->input('event_type'),
                'includes_tables_chairs' => $request->boolean('includes_tables_chairs'),
                'includes_food' => $request->boolean('includes_food'),
                'beer_provided_by' => $request->input('beer_provided_by'),
                'notes' => $request->input('notes'),
            ]);

            $rental->items()->delete();
            $rental->extraCharges()->where('phase', EvenLocalRentalExtraCharge::PHASE_BOOKING)->delete();
            $this->syncItems($rental, $request->input('areas', []));
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
                ->with(['local:id,description', 'area:id,name,area_type'])
                ->get(),
            'activeAreas' => EvenLocalArea::where('is_active', true)
                ->with('local:id,description')
                ->orderBy('sort_order')
                ->get(['id', 'local_id', 'name', 'area_type', 'capacity']),
            'eventTypes' => EvenLocalRental::eventTypeOptions(),
            'areaTypes' => EvenLocalArea::typeOptions(),
            // Complejos deportivos activos: solo se usa su nombre como sede del torneo.
            'complexes' => EvenLocal::where('status', true)->orderBy('names')->get(['id', 'names']),
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
        ];
    }

    /**
     * Crea la cabecera de la reserva con los totales ya calculados por el servidor.
     *
     * @param  array<string, float|string>  $totals
     */
    private function createHeader(StoreRentalRequest $request, array $totals, string $status): EvenLocalRental
    {
        $firstLine = $request->input('areas.0');

        return EvenLocalRental::create([
            'local_id' => $request->input('local_id'),
            'customer_id' => $request->input('customer_id'),
            'rental_rate_id' => $firstLine['rental_rate_id'],
            'event_type' => $request->input('event_type'),
            'event_date' => $firstLine['event_date'],
            'event_end_date' => $firstLine['event_end_date'] ?? $firstLine['event_date'],
            'start_time' => $firstLine['start_time'],
            'end_time' => $firstLine['end_time'],
            'total_hours' => $firstLine['total_hours'],
            'hourly_rate' => $firstLine['hourly_rate'],
            'base_amount' => $firstLine['base_amount'],
            'includes_tables_chairs' => $request->boolean('includes_tables_chairs'),
            'includes_food' => $request->boolean('includes_food'),
            'beer_provided_by' => $request->input('beer_provided_by'),
            'total_price' => $totals['total_price'],
            'paid_amount' => $totals['paid_amount'],
            'balance_amount' => $totals['balance_amount'],
            'payment_status' => $totals['payment_status'],
            'reservation_status' => $status,
            'notes' => $request->input('notes'),
        ]);
    }

    /**
     * Crea una reserva de torneo: monto fijo total y una línea por cada fecha de la serie.
     * Las líneas no se cobran por hora; el total de la cabecera es el monto pactado.
     *
     * @param  array<string, float|string>  $totals
     */
    private function storeTournament(StoreRentalRequest $request, array $totals): void
    {
        $tournament = $request->input('tournament');
        $dates = app(LocalRentalScheduleService::class)->buildTournamentDates(
            $tournament['start_date'],
            $tournament['end_date'],
            (int) $tournament['day_of_week']
        );

        $rental = EvenLocalRental::create([
            'local_id' => $request->input('local_id'),
            'customer_id' => $request->input('customer_id'),
            'rental_rate_id' => null,
            'event_type' => $request->input('event_type'),
            'event_date' => $dates[0],
            'event_end_date' => end($dates),
            'start_time' => $tournament['start_time'],
            'end_time' => $tournament['end_time'],
            'total_hours' => 0,
            'hourly_rate' => 0,
            'base_amount' => $totals['total_price'],
            'includes_tables_chairs' => $request->boolean('includes_tables_chairs'),
            'includes_food' => $request->boolean('includes_food'),
            'beer_provided_by' => $request->input('beer_provided_by'),
            'total_price' => $totals['total_price'],
            'paid_amount' => $totals['paid_amount'],
            'balance_amount' => $totals['balance_amount'],
            'payment_status' => $totals['payment_status'],
            'reservation_status' => 'pending',
            'is_tournament' => true,
            'tournament_venue' => $tournament['venue_name'],
            'notes' => $request->input('notes'),
        ]);

        foreach ($dates as $date) {
            $rental->items()->create([
                'area_id' => $tournament['area_id'],
                'rental_rate_id' => null,
                'event_date' => $date,
                'event_end_date' => $date,
                'start_time' => $tournament['start_time'],
                'end_time' => $tournament['end_time'],
                'total_hours' => 0,
                'hourly_rate' => 0,
                'base_amount' => 0,
                'extras_total' => 0,
                'subtotal' => 0,
                'is_included' => false,
                'is_tournament' => true,
            ]);
        }
    }

    /**
     * Crea una línea por área y sus extras, recalculando cada línea en el servidor.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncItems(EvenLocalRental $rental, array $lines): void
    {
        $pricingService = app(LocalRentalPricingService::class);

        foreach ($lines as $line) {
            $calculated = $pricingService->buildItemSnapshot($line);

            $rental->items()->create([
                'area_id' => $line['area_id'],
                'rental_rate_id' => $line['rental_rate_id'],
                'event_date' => $line['event_date'],
                'event_end_date' => $line['event_end_date'] ?? $line['event_date'],
                'start_time' => $line['start_time'],
                'end_time' => $line['end_time'],
                'total_hours' => $calculated['total_hours'],
                'hourly_rate' => $calculated['hourly_rate'],
                'base_amount' => $calculated['base_amount'],
                'extras_total' => $calculated['extras_total'],
                'subtotal' => $calculated['subtotal'],
                'is_included' => false,
            ]);

            foreach ($line['extras'] ?? [] as $extra) {
                $rental->extraCharges()->create([
                    'description' => $extra['description'],
                    'amount' => $extra['amount'],
                    'phase' => EvenLocalRentalExtraCharge::PHASE_BOOKING,
                    'reason' => $extra['reason'] ?? 'planned',
                ]);
            }
        }
    }
}
