<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAuditLog;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasurySettingsService;

/**
 * Puesta en marcha de Tesorería: activar o desactivar el módulo y fijar la
 * fecha desde la que cuenta. Es una operación crítica: pide la contraseña del
 * usuario y queda auditada.
 */
class TreasuryConfigurationController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasurySettingsService $settings)
    {
    }

    public function index()
    {
        $this->authorizePermission('treasury_configuracion');

        $history = TreasuryAuditLog::query()
            ->where('entity', 'treasury_settings')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (TreasuryAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'old' => $log->old_values,
                'new' => $log->new_values,
                'meta' => $log->meta,
                'user' => $log->user?->name,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Treasury::Configuration/Index', [
            'settings' => $this->settings->summary(),
            'history' => $history,
            'canManage' => auth()->user()?->hasPermissionTo('treasury_configuracion'),
        ]);
    }

    /**
     * Guarda la configuración. Crítico: contraseña del usuario y auditoría.
     */
    public function update(Request $request)
    {
        $this->authorizePermission('treasury_configuracion');

        $data = $request->validate([
            'password' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
            'starts_on' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        if (! $user || ! filled($user->password) || ! Hash::check($data['password'], $user->password)) {
            return back()->with('error', 'Contraseña incorrecta: no se modificó la configuración de Tesorería.');
        }

        try {
            $this->settings->update(
                (bool) $data['enabled'],
                $data['starts_on'] ?? null,
                $user->id,
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Configuración de Tesorería guardada y auditada.');
    }
}
