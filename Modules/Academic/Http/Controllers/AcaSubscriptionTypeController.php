<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaSubscriptionType;

class AcaSubscriptionTypeController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = AcaSubscriptionType::orderBy('order_number')->get();

        return Inertia::render('Academic::Subscriptions/List', [
            'subscriptions' => $subscriptions,
            // Aviso del webhook necesario para los planes con cobro automático.
            'mercadopagoWebhook' => app(\Modules\Academic\Services\MercadoPagoWebhookStatus::class)->state(),
        ]);
    }

    /**
     * Vista de los planes activos con el diseño de la landing de suscripciones.
     *
     * Es una página completa (Blade) dentro del panel: el enlace del sidebar la
     * abre con navegación normal, no con Inertia. Muestra los detalles de cada
     * plan y el precio en soles; el precio en dólares solo aparece cuando el
     * plan lo tiene habilitado.
     */
    public function showcase()
    {
        $plans = AcaSubscriptionType::where('status', '1')
            ->orderBy('order_number')
            ->orderBy('id')
            ->get()
            ->map(fn (AcaSubscriptionType $plan) => $this->presentPlan($plan));

        // El plan destacado de la portada es el primero con precio en soles.
        $featured = $plans->first(fn (array $plan) => $plan['pen'] !== null) ?? $plans->first();

        $company = Company::first();

        // El logo negativo vive en storage; si falta el archivo, se usa el isotipo
        // del sistema para no dejar una imagen rota en la cabecera.
        $logo = $company?->logo_negative
            && file_exists(public_path('storage/' . $company->logo_negative));

        return view('academic::subscriptions.showcase', [
            'plans' => $plans,
            'featured' => $featured,
            'included' => $this->commonBenefits($plans),
            'company' => $company,
            'logo' => $logo
                ? asset('storage/' . $company->logo_negative)
                : asset('img/logo176x32_negativo.png'),
        ]);
    }

    /**
     * Normaliza un plan para la vista: textos, detalles y precios válidos.
     */
    private function presentPlan(AcaSubscriptionType $plan): array
    {
        $prices = collect($plan->prices ?? []);

        // El detalle del precio se oculta cuando solo repite el periodo del plan
        // (por ejemplo, precio "Mensual" en un plan mensual).
        $pen = $this->withoutDuplicatePeriod($this->price($prices, 'PEN'), $plan->period);
        $usd = $plan->show_dollar_price
            ? $this->withoutDuplicatePeriod($this->price($prices, 'USD'), $plan->period)
            : null;

        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'description' => $plan->description,
            'period' => $plan->period,
            'recurring' => (bool) $plan->recurring_enabled,
            'details' => collect($plan->details ?? [])
                ->pluck('label')
                ->map(fn ($label) => trim((string) $label))
                ->filter()
                ->values()
                ->all(),
            'pen' => $pen,
            'usd' => $usd,
            'flags' => [
                'access_all_courses' => (bool) $plan->access_all_courses,
                'access_specialization_programs' => (bool) $plan->access_specialization_programs,
                'access_all_webinars' => (bool) $plan->access_all_webinars,
                'can_view_specialization_zooms' => (bool) $plan->can_view_specialization_zooms,
                'can_view_specialization_webinars' => (bool) $plan->can_view_specialization_webinars,
            ],
        ];
    }

    /**
     * Primer precio válido de la moneda pedida, ya formateado para mostrar.
     */
    private function price(Collection $prices, string $currency): ?array
    {
        $price = $prices->first(function ($price) use ($currency) {
            return strtoupper((string) ($price['currency'] ?? '')) === $currency
                && $this->amount($price['amount'] ?? null) !== null;
        });

        if (! $price) {
            return null;
        }

        $amount = $this->amount($price['amount'] ?? null);

        return [
            'amount' => $amount,
            'formatted' => number_format($amount, $amount == floor($amount) ? 0 : 2, '.', ','),
            'detail' => trim((string) ($price['detail'] ?? '')),
        ];
    }

    /**
     * Quita el detalle del precio cuando solo repite el periodo del plan.
     */
    private function withoutDuplicatePeriod(?array $price, ?string $period): ?array
    {
        if ($price
            && trim($price['detail']) !== ''
            && trim($period ?? '') !== ''
            && mb_strtolower(trim($price['detail'])) === mb_strtolower(trim($period))) {
            $price['detail'] = '';
        }

        return $price;
    }

    /**
     * Convierte el monto guardado (texto o número) a un monto positivo.
     */
    private function amount($value): ?float
    {
        $clean = str_replace([',', ' '], '', (string) $value);

        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        $amount = (float) $clean;

        return $amount > 0 ? $amount : null;
    }

    /**
     * Beneficios que comparten todos los planes activos.
     */
    private function commonBenefits(Collection $plans): array
    {
        $labels = [
            'access_all_courses' => 'Acceso a todos los cursos y talleres',
            'access_specialization_programs' => 'Acceso a los programas de especialización',
            'access_all_webinars' => 'Acceso a todos los webinars',
            'can_view_specialization_zooms' => 'Sesiones en vivo de especialización (Zoom/Meet)',
            'can_view_specialization_webinars' => 'Webinars dentro de los programas de especialización',
        ];

        if ($plans->isEmpty()) {
            return [];
        }

        $included = [];

        foreach ($labels as $flag => $label) {
            if ($plans->every(fn (array $plan) => $plan['flags'][$flag] === true)) {
                $included[] = $label;
            }
        }

        return $included;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $periods = getEnumValues('aca_subscription_types', 'period');
        return Inertia::render('Academic::Subscriptions/Create', [
            'periods' => $periods
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //dd($request->all());
        $this->validate(
            $request,
            [
                'title' => 'required',
                'description' => 'required',
                'details.*.label' => 'required',
                'prices.0.currency' => 'required',
                'prices.0.amount' => 'required',
                'period' => 'required'
            ]
        );

        AcaSubscriptionType::create([
            'title' => $request->get('title'),
            'usine' => $request->get('usine'),
            'description' => $request->get('description'),
            'details' => $request->get('details'),
            'prices' => $request->get('prices'),
            'show_dollar_price' => $request->boolean('show_dollar_price'),
            'status' => $request->get('status') ?? false,
            'period' => $request->get('period'),
            'order_number' => $request->get('order_number'),
            'access_all_courses' => $request->boolean('access_all_courses'),
            'access_specialization_programs' => $request->boolean('access_specialization_programs'),
            'access_all_webinars' => $request->boolean('access_all_webinars'),
            'can_view_specialization_zooms' => $request->boolean('can_view_specialization_zooms'),
            'can_view_specialization_webinars' => $request->boolean('can_view_specialization_webinars'),
        ]);

        return redirect()->route('aca_subscriptions_list')
            ->with('message', __('Creado con éxito'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('academic::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $subscription = AcaSubscriptionType::find($id);
        $periods = getEnumValues('aca_subscription_types', 'period');

        return Inertia::render('Academic::Subscriptions/Edit', [
            'subscription' => $subscription,
            'periods' => $periods,
            // Aviso y acceso directo a IntegrationHub para configurar el webhook.
            'mercadopagoWebhook' => app(\Modules\Academic\Services\MercadoPagoWebhookStatus::class)->state(),
            // Estado del modo prueba: credenciales, suscripciones y bitácora.
            'subscriptionTest' => app(\Modules\Academic\Services\SubscriptionTestModeService::class)
                ->state($subscription, auth()->user()?->email),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'title' => 'required',
                'description' => 'required',
                'details.*.label' => 'required',
                'prices.0.currency' => 'required',
                'prices.0.amount' => 'required',
                'prices.0.detail' => 'required',
                'period' => 'required',
                'recurring_enabled' => 'nullable|boolean',
                'test_mode' => 'nullable|boolean'
            ]
        );

        $period = $request->get('period');
        // El cobro automático existe para planes mensuales, trimestrales y anuales.
        $recurrente = in_array($period, array_keys(AcaSubscriptionType::RECURRING_PERIODS), true);
        $recurringEnabled = $request->boolean('recurring_enabled') && $recurrente;
        // El modo prueba usa la misma recurrencia.
        $testMode = $request->boolean('test_mode') && $recurrente;

        AcaSubscriptionType::find($id)->update([
            'title' => $request->get('title'),
            'usine' => $request->get('usine'),
            'description' => $request->get('description'),
            'details' => $request->get('details'),
            'prices' => $request->get('prices'),
            'show_dollar_price' => $request->boolean('show_dollar_price'),
            'status' => $request->get('status') ?? false,
            'period' => $period,
            'recurring_enabled' => $recurringEnabled,
            'test_mode' => $testMode,
            'order_number' => $request->get('order_number'),
            'access_all_courses' => $request->boolean('access_all_courses'),
            'access_specialization_programs' => $request->boolean('access_specialization_programs'),
            'access_all_webinars' => $request->boolean('access_all_webinars'),
            'can_view_specialization_zooms' => $request->boolean('can_view_specialization_zooms'),
            'can_view_specialization_webinars' => $request->boolean('can_view_specialization_webinars'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = AcaSubscriptionType::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
