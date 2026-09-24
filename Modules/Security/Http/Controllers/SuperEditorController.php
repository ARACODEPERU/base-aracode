<?php

namespace Modules\Security\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Security\Entities\SuperEditorAudit;
use Modules\Security\Entities\SuperEditorSession;
use Modules\Security\Services\PermissionPanel;
use Modules\Security\Services\SuperEditorService;

/**
 * Endpoints del Modo Super Editor.
 *
 * Todo lo que escribe pasa por SuperEditorService y los datos del panel los
 * arma PermissionPanel: aquí solo se valida la entrada y se traduce el
 * resultado a JSON para el frontend.
 */
class SuperEditorController extends Controller
{
    public function __construct(
        private SuperEditorService $service,
        private PermissionPanel $panel,
    ) {
    }

    /**
     * Entrar al modo: no pide contraseña. La puerta es el rol autorizado (la
     * ruta ya exige `role:admin` + `throttle`) y lo único que se abre es una
     * sesión de edición con el borrador vacío.
     */
    public function enter(Request $request): JsonResponse
    {
        $session = $this->service->open(
            $request->user(),
            $request->ip(),
            $request->userAgent(),
            $request->session()
        );

        return response()->json([
            'ok' => true,
            'message' => 'Modo Super Editor activado. Los cambios quedan en borrador hasta que salgas.',
            'session_id' => $session->id,
            'state' => $this->service->shareData($request),
        ]);
    }

    /**
     * Estado actual del modo (activo, cambios sin aplicar, vencimiento).
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'state' => $this->service->shareData($request),
        ]);
    }

    /**
     * Datos del panel para un elemento concreto de la interfaz.
     */
    public function element(Request $request): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'kind' => 'nullable|string|max:60',
            'url' => 'nullable|string|max:500',
        ]);

        $session = $this->currentSession($request);

        return response()->json([
            'ok' => true,
            'panel' => $this->panel->element($data['permission'], $data, $session->id),
        ]);
    }

    /**
     * Deja un cambio en el borrador (no toca los permisos todavía).
     */
    public function stage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|max:255',
            'role_id' => 'required|integer',
            'allowed' => 'required|boolean',
            'label' => 'nullable|string|max:255',
            'kind' => 'nullable|string|max:60',
            'url' => 'nullable|string|max:500',
        ]);

        $session = $this->currentSession($request);
        $result = $this->service->stage($session, $request->user(), $data);

        return response()->json([
            'ok' => true,
            'message' => $result['change']
                ? 'Cambio en borrador. Se aplicará cuando salgas del Modo Super Editor.'
                : 'El borrador volvió al estado actual: ya no hay cambios pendientes en ese rol.',
            'change' => $result['change'] ? [
                'permission' => $result['change']->permission_name,
                'role_id' => (int) $result['change']->role_id,
                'allowed' => (bool) $result['change']->allowed,
                'allowed_before' => (bool) $result['change']->allowed_before,
            ] : null,
            'state' => $this->service->shareData($request),
        ]);
    }

    /**
     * Crea un permiso inexistente al que apunta un elemento de la interfaz.
     */
    public function createPermission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'kind' => 'nullable|string|max:60',
            'url' => 'nullable|string|max:500',
        ]);

        $session = $this->currentSession($request);
        $permission = $this->service->createPermission($session, $request->user(), $data['permission'], $data);

        return response()->json([
            'ok' => true,
            'message' => sprintf('Permiso %s creado y concedido al rol %s.', $permission->name, $this->service->roleName()),
            'panel' => $this->panel->element($permission->name, $data, $session->id),
            'state' => $this->service->shareData($request),
        ]);
    }

    /**
     * Retira del borrador los cambios de un elemento.
     */
    public function revert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|max:255',
        ]);

        $session = $this->currentSession($request);
        $deleted = $this->service->revert($session, $request->user(), $data['permission']);

        return response()->json([
            'ok' => true,
            'message' => $deleted > 0
                ? 'Se retiraron del borrador los cambios de este elemento.'
                : 'No había cambios pendientes en este elemento.',
            'state' => $this->service->shareData($request),
        ]);
    }

    /**
     * Salir del modo: aplica (o descarta) el borrador completo, dejando el
     * registro en la bitácora. La contraseña se exige solo cuando hay un
     * borrador con cambios que aplicar; el servicio es quien lo decide, así que
     * una petición sin contraseña sobre un borrador lleno se rechaza aquí.
     */
    public function exit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => 'nullable|string',
            'mode' => 'required|in:apply,discard',
        ]);

        $session = $this->currentSession($request);

        $result = $this->service->close(
            $session,
            $request->user(),
            $data['password'] ?? null,
            $data['mode'] === 'discard' ? SuperEditorService::CLOSE_DISCARDED : SuperEditorService::CLOSE_APPLIED,
            $request->ip(),
            $request->session()
        );

        return response()->json([
            'ok' => true,
            'result' => $result,
            'message' => $this->exitMessage($result),
            'state' => $this->service->shareData($request),
        ]);
    }

    /**
     * Vista de historial (fuera del modo editor: solo lectura).
     */
    public function log()
    {
        return Inertia::render('Security::SuperEditor/Log', [
            'actions' => SuperEditorAudit::actionLabels(),
            'filters' => request()->all('search', 'action'),
        ]);
    }

    public function logData()
    {
        $query = $this->service->auditsQuery(request()->all('search', 'action'));

        return \DataTables::of($query)
            ->editColumn('created_at', fn (SuperEditorAudit $audit) => $audit->created_at?->format('d/m/Y H:i:s'))
            ->addColumn('action_label', fn (SuperEditorAudit $audit) => $audit->actionLabel())
            ->addColumn('user_name', fn (SuperEditorAudit $audit) => $audit->user?->name ?? '—')
            ->addColumn('element', function (SuperEditorAudit $audit) {
                if (! $audit->element_label) {
                    return '—';
                }

                return trim(sprintf('%s «%s»', $audit->element_kind ?? 'elemento', $audit->element_label));
            })
            ->addColumn('state', function (SuperEditorAudit $audit) {
                if ($audit->allowed_before === null && $audit->allowed_after === null) {
                    return '—';
                }

                return sprintf(
                    '%s → %s',
                    $audit->allowed_before ? 'permitido' : 'denegado',
                    $audit->allowed_after ? 'permitido' : 'denegado'
                );
            })
            ->toJson();
    }

    protected function currentSession(Request $request): SuperEditorSession
    {
        $session = $this->service->activeSession($request);

        if (! $session) {
            throw ValidationException::withMessages([
                'super_editor' => 'No hay una sesión de edición activa.',
            ]);
        }

        return $session;
    }

    protected function exitMessage(array $result): string
    {
        if ($result['reason'] === SuperEditorService::CLOSE_DISCARDED) {
            return $result['discarded'] > 0
                ? sprintf('Se descartaron %d cambios y se registró el descarte en el historial.', $result['discarded'])
                : 'Saliste del Modo Super Editor sin cambios pendientes.';
        }

        $message = sprintf('Se aplicaron %d cambios y quedaron registrados en el historial.', $result['applied']);

        if (($result['blocked'] ?? 0) > 0) {
            $message .= sprintf(' %d cambios se omitieron por ser permisos protegidos.', $result['blocked']);
        }

        return $message;
    }
}
