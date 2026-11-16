<?php

namespace Modules\Performance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Performance\Services\QueueHealthService;

class PerformanceQueueController extends Controller
{
    public function __construct(private QueueHealthService $queues)
    {
    }

    /**
     * Estado de la cola y de los errores recientes.
     */
    public function index(Request $request)
    {
        $this->ensurePermission($request, 'performance_queue');

        return Inertia::render('Performance::Pages/Queues', [
            'report' => $this->queues->report($request->boolean('refresh')),
        ]);
    }

    private function ensurePermission(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyRole(['admin', 'Administrador']) && $user->hasPermissionTo($permission),
            403,
            'No tienes permiso para gestionar el rendimiento.'
        );
    }
}
