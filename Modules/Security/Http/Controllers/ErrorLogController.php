<?php

namespace Modules\Security\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Entities\SecurityErrorLog;
use Modules\Security\Services\ErrorLogService;
use Throwable;

/**
 * Pantalla "Errores" del módulo Security.
 *
 * Muestra el contexto completo de los errores que avisó el bot de Telegram (la
 * misma fila a la que apunta el enlace del mensaje) para poder corregirlos, y
 * permite limpiarlos para no llenar el disco: uno por uno, todos, o los que
 * superan la retención configurada en Seguridad → Alertas.
 *
 * Ver exige el permiso conf_errores; borrar exige conf_errores_eliminar, que
 * solo tienen los roles de administración.
 */
class ErrorLogController extends Controller
{
    public function __construct(private readonly ErrorLogService $logs)
    {
    }

    public function index()
    {
        return Inertia::render('Security::Errors/Index', [
            'stats' => $this->stats(),
            'settings' => $this->settingsPayload(),
            'levels' => array_keys($this->logs->stats()['levels']),
            'canDelete' => $this->canDelete(),
        ]);
    }

    /**
     * Listado paginado con filtros (nivel, texto, rango de fechas).
     */
    public function data(Request $request)
    {
        $perPage = max(5, min(100, (int) $request->input('per_page', 15)));

        $query = SecurityErrorLog::query()
            ->when($request->filled('level'), fn ($q) => $q->where('level', (string) $request->input('level')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $this->escapeLike((string) $request->input('search')) . '%';

                $q->where(function ($sub) use ($term) {
                    $sub->where('message', 'like', $term)
                        ->orWhere('exception_class', 'like', $term)
                        ->orWhere('file', 'like', $term)
                        ->orWhere('url', 'like', $term)
                        ->orWhere('user_label', 'like', $term);
                });
            })
            ->when($request->filled('date_start'), fn ($q) => $q->whereDate('created_at', '>=', (string) $request->input('date_start')))
            ->when($request->filled('date_end'), fn ($q) => $q->whereDate('created_at', '<=', (string) $request->input('date_end')))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $page = $query->paginate($perPage)->appends($request->query());

        $rows = collect($page->items())->map(fn (SecurityErrorLog $log) => $this->rowPayload($log))->values();

        return response()->json([
            'data' => $rows,
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'from' => $page->firstItem(),
            'to' => $page->lastItem(),
        ]);
    }

    /**
     * Detalle completo de un error (destino del enlace del aviso).
     */
    public function show(int $id)
    {
        $log = SecurityErrorLog::findOrFail($id);

        return Inertia::render('Security::Errors/Show', [
            'error_log' => $this->detailPayload($log),
            'canDelete' => $this->canDelete(),
        ]);
    }

    /**
     * Borra un error concreto.
     */
    public function destroy(int $id)
    {
        $log = SecurityErrorLog::findOrFail($id);
        $this->logs->delete((int) $log->id);

        return response()->json([
            'message' => "Se eliminó el error {$log->reference()} y su contexto.",
            'deleted' => 1,
        ]);
    }

    /**
     * Borra TODOS los errores guardados.
     */
    public function clear()
    {
        $deleted = $this->logs->clear();

        return response()->json([
            'message' => $deleted === 0
                ? 'No había errores guardados que eliminar.'
                : "Se eliminaron {$deleted} error(es) con todo su contexto.",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Borra solo los errores más viejos que la retención configurada.
     */
    public function prune(Request $request)
    {
        $settings = SecurityAlertSetting::current();

        $days = $request->filled('days')
            ? max(1, min(3650, (int) $request->input('days')))
            : $settings->retentionDays();

        if ($days <= 0) {
            return response()->json([
                'message' => 'La retención está en 0 días (no se borra nada automáticamente). Indica días para purgar.',
                'deleted' => 0,
            ]);
        }

        $deleted = $this->logs->prune($days);

        return response()->json([
            'message' => "Se eliminaron {$deleted} error(es) con más de {$days} día(s) de antigüedad.",
            'deleted' => $deleted,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowPayload(SecurityErrorLog $log): array
    {
        return [
            'id' => $log->id,
            'reference' => $log->reference(),
            'level' => $log->level,
            'message' => $this->shortMessage((string) $log->message),
            'exception_class' => $log->exception_class,
            'origin' => $log->origin(),
            'method' => $log->method,
            'url' => $log->url,
            'user_label' => $log->user_label,
            'ip' => $log->ip,
            'occurrences' => (int) $log->occurrences,
            'created_at' => optional($log->created_at)->format('d/m/Y H:i:s'),
            'last_seen_at' => optional($log->last_seen_at)->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(SecurityErrorLog $log): array
    {
        $context = $log->contextArray();

        return array_merge($this->rowPayload($log), [
            'message' => (string) $log->message,
            'file' => $log->file,
            'line' => $log->line,
            'environment' => $log->environment,
            'user_agent' => $log->user_agent,
            'user_id' => $log->user_id,
            'trace' => (string) $log->trace,
            'context' => $context,
            'context_raw' => (string) $log->context,
            'created_full' => optional($log->created_at)->format('d/m/Y H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        $stats = $this->logs->stats();

        return [
            'total' => $stats['total'],
            'size' => $this->humanSize($stats['size_bytes']),
            'size_bytes' => $stats['size_bytes'],
            'oldest_at' => $stats['oldest_at'] ? $this->formatDate($stats['oldest_at']) : null,
            'newest_at' => $stats['newest_at'] ? $this->formatDate($stats['newest_at']) : null,
            'levels' => $stats['levels'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(): array
    {
        $settings = SecurityAlertSetting::current();

        return [
            'store_context' => $settings->storesContext(),
            'retention_days' => $settings->retentionDays(),
            'purge_enabled' => $settings->autoPurge(),
        ];
    }

    private function canDelete(): bool
    {
        try {
            return (bool) Auth::user()?->can('conf_errores_eliminar');
        } catch (Throwable) {
            return false;
        }
    }

    private function shortMessage(string $message): string
    {
        return mb_strlen($message) > 220 ? mb_substr($message, 0, 220) . '…' : $message;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    }

    private function formatDate(mixed $value): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i:s');
        } catch (Throwable) {
            return '';
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
