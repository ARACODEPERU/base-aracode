<?php

namespace Modules\Academic\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaSchoolCharge;

/**
 * Consulta unificada de compromisos de pago del colegio.
 *
 * Reune en una sola lista las dos fuentes reales de deuda:
 * - aca_school_charges: cargos (matricula, conceptos sueltos y el cargo que
 *   representa una cuota ya pagada).
 * - aca_school_payment_schedules: cuotas del cronograma aun sin pagar.
 *
 * Una cuota pagada se muestra una sola vez (como cargo), porque su fila de
 * cronograma queda excluida en cuanto tiene charge_id. Si un cargo se anula,
 * el cronograma vuelve a pendiente y reaparece como deuda.
 */
class SchoolChargeListService
{
    /** Estados visibles de un movimiento. */
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_VENCIDO = 'vencido';
    public const ESTADO_PAGADO = 'pagado';
    public const ESTADO_ANULADO = 'anulado';

    /** Filtro de estado por defecto: todo lo que falta cobrar. */
    public const ESTADO_POR_COBRAR = 'por_cobrar';
    public const ESTADO_TODOS = 'todos';

    public static function estadoLabels(): array
    {
        return [
            self::ESTADO_POR_COBRAR => 'Por cobrar',
            self::ESTADO_VENCIDO => 'Vencidos',
            self::ESTADO_PAGADO => 'Pagados',
            self::ESTADO_ANULADO => 'Anulados',
            self::ESTADO_TODOS => 'Todos',
        ];
    }

    /**
     * Lista paginada de movimientos de cobro.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?int $schoolId, int $perPage = 20): LengthAwarePaginator
    {
        $perPage = in_array((int) $perPage, [10, 20, 50, 100], true) ? (int) $perPage : 20;

        return $this->query($schoolId, $filters)
            ->orderByRaw("CASE WHEN estado IN ('pendiente', 'vencido') THEN 0 ELSE 1 END")
            ->orderByRaw('COALESCE(vencimiento, pagado_en, creado_en) ASC')
            ->orderBy('origen_id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($row) => $this->present($row));
    }

    /**
     * Totales del conjunto filtrado (sin aplicar el filtro de estado, para que
     * el resumen siga mostrando lo pendiente aunque se consulte otra pestaña).
     *
     * @param  array<string, mixed>  $filters
     * @return array{pendiente_cantidad: int, pendiente_monto: float, vencido_cantidad: int, vencido_monto: float, pagado_cantidad: int, pagado_monto: float}
     */
    public function totals(array $filters, ?int $schoolId): array
    {
        $row = $this->query($schoolId, $filters, false)
            ->selectRaw("SUM(CASE WHEN estado IN ('pendiente', 'vencido') THEN 1 ELSE 0 END) AS pendiente_cantidad")
            ->selectRaw("SUM(CASE WHEN estado IN ('pendiente', 'vencido') THEN monto ELSE 0 END) AS pendiente_monto")
            ->selectRaw("SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) AS vencido_cantidad")
            ->selectRaw("SUM(CASE WHEN estado = 'vencido' THEN monto ELSE 0 END) AS vencido_monto")
            ->selectRaw("SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) AS pagado_cantidad")
            ->selectRaw("SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) AS pagado_monto")
            ->first();

        return [
            'pendiente_cantidad' => (int) ($row->pendiente_cantidad ?? 0),
            'pendiente_monto' => (float) ($row->pendiente_monto ?? 0),
            'vencido_cantidad' => (int) ($row->vencido_cantidad ?? 0),
            'vencido_monto' => (float) ($row->vencido_monto ?? 0),
            'pagado_cantidad' => (int) ($row->pagado_cantidad ?? 0),
            'pagado_monto' => (float) ($row->pagado_monto ?? 0),
        ];
    }

    /**
     * Consulta externa sobre la union de cargos y cuotas, ya filtrada.
     *
     * @param  array<string, mixed>  $filters
     */
    private function query(?int $schoolId, array $filters, bool $withEstado = true): Builder
    {
        $query = DB::query()->fromSub($this->baseQuery($schoolId), 'mov');

        $this->applyFilters($query, $filters, $withEstado);

        return $query;
    }

    /**
     * Union de las dos fuentes. Ambas ramas exponen exactamente las mismas
     * columnas para que la union sea valida.
     */
    private function baseQuery(?int $schoolId): Builder
    {
        $hoy = now()->toDateString();

        $charges = DB::table('aca_school_charges as c')
            ->join('aca_school_enrollments as e', 'e.id', '=', 'c.enrollment_id')
            ->join('aca_school_students as s', 's.id', '=', 'e.student_id')
            ->join('people as p', 'p.id', '=', 's.person_id')
            ->leftJoin('aca_school_sections as sec', 'sec.id', '=', 'e.section_id')
            ->leftJoin('aca_school_grades as g', 'g.id', '=', 'sec.grade_id')
            ->leftJoin('aca_school_levels as lv', 'lv.id', '=', 'g.level_id')
            ->leftJoin('aca_school_years as y', 'y.id', '=', 'e.year_id')
            ->leftJoin('aca_school_fee_types as ft', 'ft.id', '=', 'c.fee_type_id')
            ->leftJoin('aca_school_payment_schedules as ps', 'ps.id', '=', 'c.payment_schedule_id')
            ->leftJoin('sale_documents as sd', 'sd.id', '=', 'c.sale_document_id')
            ->when($schoolId, fn ($q) => $q->where('e.school_id', $schoolId))
            ->select([
                DB::raw("'cargo' as origen"),
                'c.id as origen_id',
                'c.enrollment_id',
                'c.id as charge_id',
                'c.fee_type_id',
                'ft.name as concepto',
                'c.description as descripcion',
                'ps.installment as cuota',
                'ps.due_date as vencimiento',
                'c.amount as monto',
                'c.status as estado',
                'c.paid_at as pagado_en',
                'c.payment_method as metodo',
                'c.reference as referencia',
                'c.sale_id as sale_id',
                'c.sale_document_id as sale_document_id',
                'sd.invoice_type_doc as invoice_type_doc',
                'sd.invoice_serie as invoice_serie',
                'sd.number as invoice_number',
                's.id as alumno_id',
                'p.full_name as alumno_nombre',
                'p.number as alumno_documento',
                's.student_code as alumno_codigo',
                'lv.id as nivel_id',
                'lv.name as nivel',
                'g.id as grado_id',
                'g.name as grado',
                'sec.id as seccion_id',
                'sec.name as seccion',
                'y.id as anio_id',
                'y.year as anio',
                'c.created_at as creado_en',
            ]);

        $schedules = DB::table('aca_school_payment_schedules as sc')
            ->join('aca_school_enrollments as e', 'e.id', '=', 'sc.enrollment_id')
            ->join('aca_school_students as s', 's.id', '=', 'e.student_id')
            ->join('people as p', 'p.id', '=', 's.person_id')
            ->leftJoin('aca_school_sections as sec', 'sec.id', '=', 'e.section_id')
            ->leftJoin('aca_school_grades as g', 'g.id', '=', 'sec.grade_id')
            ->leftJoin('aca_school_levels as lv', 'lv.id', '=', 'g.level_id')
            ->leftJoin('aca_school_years as y', 'y.id', '=', 'e.year_id')
            ->leftJoin('aca_school_fee_types as ft', 'ft.id', '=', 'sc.fee_type_id')
            ->whereNull('sc.charge_id')
            ->when($schoolId, fn ($q) => $q->where('e.school_id', $schoolId))
            ->select([
                DB::raw("'cuota' as origen"),
                'sc.id as origen_id',
                'sc.enrollment_id',
                DB::raw('NULL as charge_id'),
                'sc.fee_type_id',
                'ft.name as concepto',
                DB::raw('NULL as descripcion'),
                'sc.installment as cuota',
                'sc.due_date as vencimiento',
                'sc.amount as monto',
                // La fecha la genera el sistema (Y-m-d), no proviene del usuario.
                DB::raw("CASE WHEN sc.due_date < '".$hoy."' THEN '".self::ESTADO_VENCIDO."' ELSE '".self::ESTADO_PENDIENTE."' END as estado"),
                DB::raw('NULL as pagado_en'),
                DB::raw('NULL as metodo'),
                DB::raw('NULL as referencia'),
                DB::raw('NULL as sale_id'),
                DB::raw('NULL as sale_document_id'),
                DB::raw('NULL as invoice_type_doc'),
                DB::raw('NULL as invoice_serie'),
                DB::raw('NULL as invoice_number'),
                's.id as alumno_id',
                'p.full_name as alumno_nombre',
                'p.number as alumno_documento',
                's.student_code as alumno_codigo',
                'lv.id as nivel_id',
                'lv.name as nivel',
                'g.id as grado_id',
                'g.name as grado',
                'sec.id as seccion_id',
                'sec.name as seccion',
                'y.id as anio_id',
                'y.year as anio',
                'sc.created_at as creado_en',
            ]);

        return $charges->unionAll($schedules);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, bool $withEstado = true): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('alumno_nombre', 'like', '%'.$search.'%')
                    ->orWhere('alumno_documento', 'like', '%'.$search.'%')
                    ->orWhere('alumno_codigo', 'like', '%'.$search.'%');
            });
        }

        foreach (['anio_id', 'nivel_id', 'grado_id', 'seccion_id', 'fee_type_id'] as $campo) {
            if (! empty($filters[$campo])) {
                $query->where($campo, $filters[$campo]);
            }
        }

        if (! $withEstado) {
            return;
        }

        match ((string) ($filters['estado'] ?? self::ESTADO_POR_COBRAR)) {
            self::ESTADO_PAGADO => $query->where('estado', self::ESTADO_PAGADO),
            self::ESTADO_ANULADO => $query->where('estado', self::ESTADO_ANULADO),
            self::ESTADO_VENCIDO => $query->where('estado', self::ESTADO_VENCIDO),
            self::ESTADO_TODOS => null,
            default => $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_VENCIDO]),
        };
    }

    /**
     * Normaliza una fila para la vista: descripcion legible, etiqueta de estado
     * y URL del comprobante cuando existe.
     */
    private function present(object $row): array
    {
        $data = (array) $row;

        $data['monto'] = (float) $row->monto;
        $data['estado_label'] = match ($row->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_VENCIDO => 'Vencido',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_ANULADO => 'Anulado',
            default => ucfirst((string) $row->estado),
        };

        if ($row->origen === 'cuota') {
            $data['descripcion'] = trim(($row->concepto ?? 'Cuota').' · Cuota '.$row->cuota);
        }

        $data['comprobante'] = $row->invoice_number
            ? trim(($row->invoice_serie ?? '').'-'.$row->invoice_number, '-')
            : null;

        $data['pdf_url'] = $this->pdfUrl($row);

        return $data;
    }

    /**
     * URL del PDF del comprobante vinculado al cargo (mismo criterio que la
     * vista de cobros del alumno).
     */
    private function pdfUrl(object $row): ?string
    {
        if (! $row->sale_document_id) {
            return null;
        }

        if (in_array($row->invoice_type_doc, ['01', '03'], true)) {
            return route('saledocuments_download', [
                'id' => $row->sale_document_id,
                'type' => $row->invoice_type_doc,
                'file' => 'PDF',
                'format' => 'A4',
            ]);
        }

        if ($row->invoice_type_doc === '80' && $row->sale_id) {
            return route('ticketpdf_sales', ['id' => $row->sale_id]);
        }

        return null;
    }
}
