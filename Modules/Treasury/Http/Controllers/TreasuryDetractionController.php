<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryDetractionService;
use Modules\Treasury\Services\TreasuryTaxService;

/**
 * Cuenta de detracciones: saldo, detracciones cobradas, pago de IGV con ese
 * dinero, liberación a una cuenta de la empresa y ajustes manuales.
 *
 * La liberación y el ajuste son movimientos críticos: exigen la contraseña
 * del usuario y quedan en auditoría.
 */
class TreasuryDetractionController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryDetractionService $detractions,
        private TreasuryTaxService $taxes,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_detraccion');

        $destinations = TreasuryAccount::query()
            ->active()
            ->where('type', '<>', TreasuryAccount::TYPE_DETRACTION)
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'currency' => strtoupper((string) $account->currency_type_id) ?: 'PEN',
            ]);

        // IGV del periodo anterior: es lo que normalmente se paga con este saldo.
        $periodoIgv = now()->subMonthNoOverflow()->format('Y-m');

        return Inertia::render('Treasury::Detraction/Index', [
            'summary' => $this->detractions->summary(),
            'igv' => [
                'period' => $periodoIgv,
                'amount' => $this->taxes->unpaidForPeriod($periodoIgv, 'PEN'),
                'due_date' => $this->taxes->dueDate($periodoIgv),
            ],
            'destinations' => $destinations,
            'threshold' => (float) config('treasury.detraction_threshold', 700),
            'percent' => (float) config('treasury.detraction_percent', 12),
            'canManage' => auth()->user()?->hasPermissionTo('treasury_detraccion'),
        ]);
    }

    /**
     * Ajuste manual del saldo de detracción. Crítico: pide contraseña.
     */
    public function adjust(Request $request)
    {
        $this->authorizePermission('treasury_detraccion');

        $data = $request->validate([
            'password' => ['required', 'string'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        if (! $this->passwordOk($request, $data['password'])) {
            return back()->with('error', 'Contraseña incorrecta: no se modificó la cuenta de detracción.');
        }

        try {
            $this->detractions->adjust(
                $data['type'],
                (float) $data['amount'],
                $data['date'],
                $data['reason'],
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Ajuste de detracción registrado y auditado.');
    }

    /**
     * Liberación del saldo detraído hacia una cuenta de la empresa.
     */
    public function release(Request $request)
    {
        $this->authorizePermission('treasury_detraccion');

        $data = $request->validate([
            'password' => ['required', 'string'],
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->passwordOk($request, $data['password'])) {
            return back()->with('error', 'Contraseña incorrecta: no se liberó la detracción.');
        }

        $destination = TreasuryAccount::query()->active()->findOrFail($data['treasury_account_id']);

        try {
            $this->detractions->release(
                $destination,
                (float) $data['amount'],
                $data['date'],
                $data['reference'] ?? null,
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Detracción liberada a ' . $destination->displayName() . '.');
    }

    /**
     * La contraseña debe ser la del usuario autenticado.
     */
    private function passwordOk(Request $request, string $password): bool
    {
        $user = $request->user();

        return $user && filled($user->password) && Hash::check($password, $user->password);
    }
}
