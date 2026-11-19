<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Support\HealthAudit;

class HealActivityController extends Controller
{
    /**
     * Actividades de auditables que se pueden filtrar en el listado.
     */
    private const TIPOS = [
        'created' => 'Creación',
        'updated' => 'Modificación',
        'deleted' => 'Eliminación',
        'signed' => 'Firma',
        'sign_failed' => 'Intento de firma fallido',
        'cancelled' => 'Anulación',
        'restored' => 'Restauración',
        'anonymized' => 'Anonimización',
        'deactivated' => 'Desactivación',
        'viewed' => 'Consulta',
        'exported' => 'Exportación',
        'retention_purge' => 'Purga por retención',
    ];

    private const CATEGORIAS = [
        'clinical' => 'Clínico',
        'security' => 'Seguridad',
        'admin' => 'Administración',
        'access' => 'Acceso',
    ];

    public function index(Request $request): Response
    {
        // Consultar el registro de auditoría también se audita.
        HealthAudit::record('viewed', [
            'subject_type' => HealActivity::class,
            'category' => HealthAudit::CATEGORY_SECURITY,
            'metadata' => [
                'documento' => 'registro_actividades',
                'subject_name' => 'Registro de Actividades',
            ],
        ]);

        $activities = $this->consulta($request)
            ->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('Health::Admin/Activities/Index', [
            'activities' => $activities,
            'filters' => $request->all('search', 'activity_type', 'category', 'patient_id', 'date_from', 'date_to'),
            'activityTypes' => self::TIPOS,
            'categories' => self::CATEGORIAS,
            'subjectTypes' => ['settings' => 'Configuración'],
        ]);
    }

    public function show(int $id)
    {
        $activity = HealActivity::with(['actorPerson', 'patient.person'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'activity' => $activity,
        ]);
    }

    /**
     * Exporta el resultado de los filtros a CSV (solo lectura).
     */
    public function export(Request $request)
    {
        $query = $this->consulta($request)->limit(5000);

        HealthAudit::record('exported', [
            'subject_type' => HealActivity::class,
            'category' => HealthAudit::CATEGORY_SECURITY,
            'metadata' => [
                'documento' => 'exportacion_actividades',
                'filtros' => $request->only('search', 'activity_type', 'category', 'patient_id', 'date_from', 'date_to'),
                'subject_name' => 'Registro de Actividades',
            ],
        ]);

        $fileName = 'actividades_salud_' . now()->format('Ymd_His') . '.csv';

        return HttpResponse::streamDownload(function () use ($query) {
            $salida = fopen('php://output', 'w');

            fputcsv($salida, [
                'id',
                'fecha',
                'tipo',
                'categoria',
                'actor',
                'paciente',
                'sujeto',
                'sujeto_id',
                'ip',
                'ruta',
                'hash',
            ]);

            $query->chunk(500, function ($actividades) use ($salida) {
                foreach ($actividades as $actividad) {
                    fputcsv($salida, [
                        $actividad->id,
                        optional($actividad->created_at)->toDateTimeString(),
                        $actividad->activity_type,
                        $actividad->category,
                        $actividad->actor_display,
                        $actividad->patient_display,
                        $actividad->subject_type,
                        $actividad->subject_id,
                        $actividad->ip,
                        $actividad->route,
                        $actividad->entry_hash,
                    ]);
                }
            });

            fclose($salida);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function consulta(Request $request)
    {
        return HealActivity::with(['actorPerson', 'patient.person'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('actor_display', 'like', "%{$search}%")
                        ->orWhere('patient_display', 'like', "%{$search}%")
                        ->orWhere('activity_type', 'like', "%{$search}%");
                });
            })
            ->when($request->get('activity_type'), fn ($query, $type) => $query->where('activity_type', $type))
            ->when($request->get('category'), fn ($query, $category) => $query->where('category', $category))
            ->when($request->integer('patient_id'), fn ($query, $patientId) => $query->where('patient_id', $patientId))
            ->when($request->get('date_from'), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->get('date_to'), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->latest('id');
    }

    private function perPage(Request $request): int
    {
        return min(100, max(10, (int) $request->get('per_page', 20)));
    }
}
