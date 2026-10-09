<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Services\GateScanService;
use Modules\Academic\Services\SchoolContextService;

/**
 * Porteria del colegio: la pantalla donde se escanea el QR del carné para
 * registrar la asistencia institucional (entrada y salida), el escaneo en si y
 * el reporte del dia.
 *
 * La logica vive en GateScanService: aqui solo se valida, se delega y se
 * responde. La hora que se guarda es la del servidor, no la del equipo de la
 * puerta, para que todas las lecturas sean comparables entre si.
 */
class AcaSchoolGateController extends Controller
{
    public function __construct(
        private GateScanService $gate,
        private SchoolContextService $context
    ) {
    }

    /**
     * Pantalla del escáner (cámara, pistola o código a mano).
     */
    public function scanner(Request $request)
    {
        $school = $this->context->currentSchool();
        $day = $this->gate->dayPayload();

        return Inertia::render('Academic::School/Gate/Scanner', [
            'schoolName' => $school?->name,
            'schoolId' => $school?->id,
            'counters' => $day['counters'],
            'recent' => $day['recent'],
            'today' => now()->toDateString(),
            'canReport' => (bool) $request->user()?->can('aca_school_porteria_reporte'),
        ]);
    }

    /**
     * Registra un escaneo. Responde siempre 200 con el detalle del resultado
     * (ok, duplicate, not_found, no_enrollment) para que la pantalla pueda
     * mostrar el aviso correcto; solo una falla real devuelve 422.
     */
    public function scan(Request $request)
    {
        $validated = $this->validate($request, [
            'code' => 'required|string|max:40',
            'mode' => 'nullable|in:in,out',
        ]);

        $result = $this->gate->scan(
            $validated['code'],
            $validated['mode'] ?? AcaSchoolGateAttendance::EVENT_ENTRADA
        );

        $httpStatus = $result['result'] === GateScanService::RESULT_ERROR ? 422 : 200;

        return response()->json($result, $httpStatus);
    }

    /**
     * Contadores y últimas lecturas del día (para la pantalla).
     */
    public function day(Request $request)
    {
        return response()->json($this->gate->dayPayload());
    }

    /**
     * Reporte del día en CSV (entradas, salidas y tardanzas por alumno).
     */
    public function report(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            abort(404, 'No hay un colegio configurado.');
        }

        $date = $request->query('date') ?: now()->toDateString();
        $date = Carbon::parse($date)->toDateString();

        $rows = $this->gate->rowsForDay((int) $school->id, $date);
        $fileName = 'porteria_'.$date.'.csv';

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');

            // BOM para que Excel abra el CSV con acentos.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Codigo', 'Alumno', 'Seccion', 'Entrada', 'Estado entrada',
                'Salida', 'Salida anticipada', 'Escaneos',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->student?->student_code,
                    $row->student?->person?->full_name,
                    trim(($row->section?->grade?->name ?? '').' '.($row->section?->name ?? '')),
                    AcaSchoolGateAttendance::shortTime($row->entry_at),
                    $row->entryStatusLabel(),
                    AcaSchoolGateAttendance::shortTime($row->exit_at),
                    $row->early_exit ? 'Si' : 'No',
                    $row->scans_count,
                ]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
