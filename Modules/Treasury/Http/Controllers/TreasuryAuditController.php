<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAuditLog;

/**
 * Visor de la auditoría de dominio. Solo lectura: los registros no se editan
 * ni se borran (el propio modelo lo impide).
 */
class TreasuryAuditController extends Controller
{
    use ValidatesTreasuryRequests;

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_auditoria');

        $filters = [
            'entity' => $request->get('entity'),
            'action' => $request->get('action'),
            'user_id' => $request->integer('user_id') ?: null,
            'from' => $request->get('from'),
            'to' => $request->get('to'),
        ];

        $logs = TreasuryAuditLog::query()
            ->with(['user:id,name'])
            ->when($filters['entity'], fn ($query, $value) => $query->where('entity', $value))
            ->when($filters['action'], fn ($query, $value) => $query->where('action', $value))
            ->when($filters['user_id'], fn ($query, $value) => $query->where('user_id', $value))
            ->when($filters['from'], fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['to'], fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (TreasuryAuditLog $log) => [
                'id' => $log->id,
                'entity' => $log->entity,
                'entity_id' => $log->entity_id,
                'action' => $log->action,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'meta' => $log->meta,
                'user' => $log->user?->name,
                'ip' => $log->ip,
                'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('Treasury::Audit/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'entities' => TreasuryAuditLog::query()->distinct()->orderBy('entity')->pluck('entity')->all(),
            'actions' => TreasuryAuditLog::query()->distinct()->orderBy('action')->pluck('action')->all(),
        ]);
    }
}
