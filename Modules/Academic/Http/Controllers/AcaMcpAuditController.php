<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Academic\Entities\McpAuditLog;

/**
 * Auditoría del servidor MCP: qué herramienta llamó cada agente, con qué
 * argumentos (resumidos), si falló y cuánto tardó.
 */
class AcaMcpAuditController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'tool' => 'nullable|string|max:100',
            'status' => 'nullable|in:ok,error',
            'user_id' => 'nullable|integer',
        ]);

        $registros = McpAuditLog::query()
            ->with('user:id,name,email')
            ->when(filled($filtros['tool'] ?? null), fn ($consulta) => $consulta->where('tool', 'like', '%' . $filtros['tool'] . '%'))
            ->when(filled($filtros['status'] ?? null), fn ($consulta) => $consulta->where('status', $filtros['status']))
            ->when(filled($filtros['user_id'] ?? null), fn ($consulta) => $consulta->where('user_id', $filtros['user_id']))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (McpAuditLog $registro) => [
                'id' => $registro->id,
                'tool' => $registro->tool,
                'status' => $registro->status,
                'user' => $registro->user?->name ?? 'Token sin usuario',
                'arguments' => $registro->arguments,
                'error' => $registro->error,
                'duration_ms' => $registro->duration_ms,
                'ip' => $registro->ip,
                'created_at' => optional($registro->created_at)->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Academic::Settings/McpAudit', [
            'logs' => $registros,
            'filters' => [
                'tool' => $filtros['tool'] ?? '',
                'status' => $filtros['status'] ?? '',
                'user_id' => $filtros['user_id'] ?? '',
            ],
            'tools' => McpAuditLog::query()->select('tool')->distinct()->orderBy('tool')->pluck('tool'),
            'summary' => [
                'total' => McpAuditLog::count(),
                'errors' => McpAuditLog::where('status', 'error')->count(),
                'today' => McpAuditLog::whereDate('created_at', now()->toDateString())->count(),
            ],
        ]);
    }
}
