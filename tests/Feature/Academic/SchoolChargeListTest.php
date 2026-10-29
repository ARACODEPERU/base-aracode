<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolCharge;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolFeeType;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolPaymentSchedule;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Listado de cobros del colegio: reune cargos y cuotas por pagar en una sola
 * lista, sin duplicar la cuota que ya tiene su cargo de pago.
 */
class SchoolChargeListTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolYear $year;

    private AcaSchoolSection $section;

    private AcaSchoolEnrollment $enrollment;

    private AcaSchoolFeeType $matricula;

    private AcaSchoolFeeType $mensualidad;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Las vistas de módulo no se resuelven contra el manifest de Vite en pruebas.
        $this->withoutVite();

        $this->school = AcaSchool::defaultSchool();
        $this->school->update(['name' => 'Colegio Cobros', 'status' => true]);

        $this->year = AcaSchoolYear::create([
            'school_id' => $this->school->id,
            'year' => 2027,
            'status' => AcaSchoolYear::STATUS_ACTIVE,
        ]);

        $level = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'code' => AcaSchoolLevel::PRIMARIA,
            'name' => 'Primaria',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'level_id' => $level->id,
            'name' => '4°',
            'status' => true,
        ]);

        $this->section = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'B',
            'capacity' => 30,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        $person = Person::create([
            'names' => 'Ana',
            'father_lastname' => 'Torres',
            'mother_lastname' => 'Vega',
            'full_name' => 'Torres Vega Ana',
            'number' => '44556677',
        ]);

        $student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $person->id,
            'student_code' => '2027-0042',
            'status' => true,
        ]);

        $this->enrollment = AcaSchoolEnrollment::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'student_id' => $student->id,
            'section_id' => $this->section->id,
            'type' => AcaSchoolEnrollment::TYPE_NUEVA,
            'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
            'enrollment_date' => now()->toDateString(),
        ]);

        AcaSchoolFeeType::ensureDefaults();
        $this->matricula = AcaSchoolFeeType::where('code', 'matricula')->firstOrFail();
        $this->mensualidad = AcaSchoolFeeType::where('code', 'mensualidad')->firstOrFail();

        // Compromiso de matrícula pendiente (sin vencimiento propio).
        AcaSchoolCharge::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'fee_type_id' => $this->matricula->id,
            'description' => 'Matrícula 2027',
            'amount' => 200,
            'status' => AcaSchoolCharge::STATUS_PENDIENTE,
        ]);

        // Cuota vencida por pagar.
        $this->schedule(1, now()->subMonth()->toDateString(), 150);

        // Cuota futura por pagar.
        $this->schedule(2, now()->addMonth()->toDateString(), 150);

        // Cuota ya pagada: su cargo aparece en la lista y la cuota no se repite.
        $paidSchedule = $this->schedule(3, now()->subWeek()->toDateString(), 150);
        $paidCharge = AcaSchoolCharge::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'fee_type_id' => $this->mensualidad->id,
            'description' => 'Mensualidad 2027',
            'amount' => 150,
            'status' => AcaSchoolCharge::STATUS_PAGADO,
            'paid_at' => now(),
            'payment_method' => 'efectivo',
            'payment_schedule_id' => $paidSchedule->id,
        ]);
        $paidSchedule->update(['charge_id' => $paidCharge->id, 'paid_at' => now()]);

        $this->user = $this->userWithPermission('aca_school_cobro_listado');
    }

    public function test_lista_cargos_y_cuotas_por_cobrar_sin_duplicar_la_cuota_pagada(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_charges_list'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // El resolvedor de Inertia no busca vistas dentro de Modules/.
                ->component('Academic::School/Charges/List', false)
                // Matrícula + cuota vencida + cuota futura (la cuota pagada no se repite).
                ->has('movimientos.data', 3)
                ->where('totals.pendiente_cantidad', 3)
                // Los montos enteros viajan sin decimales en el JSON de Inertia.
                ->where('totals.pendiente_monto', 500)
                ->where('totals.vencido_cantidad', 1)
                ->where('totals.vencido_monto', 150));

        $estados = collect($this->inertiaMovimientos())
            ->pluck('estado')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['pendiente', 'pendiente', 'vencido'], $estados);
    }

    public function test_la_pestana_de_pagados_muestra_el_cargo_con_su_comprobante(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_charges_list', ['estado' => 'pagado']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('movimientos.data', 1)
                ->where('movimientos.data.0.estado', 'pagado')
                ->where('movimientos.data.0.origen', 'cargo')
                ->where('movimientos.data.0.monto', 150)
                ->where('movimientos.data.0.alumno_nombre', 'Torres Vega Ana'));
    }

    public function test_busca_por_nombre_dni_y_codigo_de_alumno(): void
    {
        foreach (['Torres', '44556677', '2027-0042'] as $term) {
            $this->actingAs($this->user)
                ->get(route('aca_school_charges_list', ['search' => $term]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->has('movimientos.data', 3)
                    ->where('movimientos.data.0.alumno_nombre', 'Torres Vega Ana'));
        }
    }

    public function test_filtra_por_seccion_y_por_concepto(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_charges_list', ['seccion_id' => $this->section->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('movimientos.data', 3));

        $this->actingAs($this->user)
            ->get(route('aca_school_charges_list', ['fee_type_id' => $this->matricula->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('movimientos.data', 1)
                ->where('movimientos.data.0.concepto', 'Matrícula'));
    }

    public function test_una_busqueda_sin_coincidencias_devuelve_lista_vacia(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_school_charges_list', ['search' => 'NoExiste']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('movimientos.data', 0)
                ->where('totals.pendiente_cantidad', 0));
    }

    public function test_sin_permiso_no_puede_ver_el_listado(): void
    {
        $sinPermiso = $this->userWithPermission('aca_school_cobro_registrar');

        $this->actingAs($sinPermiso)
            ->get(route('aca_school_charges_list'))
            ->assertForbidden();
    }

    private function schedule(int $installment, string $dueDate, float $amount): AcaSchoolPaymentSchedule
    {
        return AcaSchoolPaymentSchedule::create([
            'enrollment_id' => $this->enrollment->id,
            'fee_type_id' => $this->mensualidad->id,
            'installment' => $installment,
            'due_date' => $dueDate,
            'amount' => $amount,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function inertiaMovimientos(): array
    {
        $response = $this->get(route('aca_school_charges_list'));
        $page = $response->viewData('page');

        return $page['props']['movimientos']['data'] ?? [];
    }

    private function userWithPermission(string ...$permissions): User
    {
        static $sequence = 0;
        $sequence++;

        $person = Person::create([
            'number' => (string) (93000000 + $sequence),
            'names' => 'Caja',
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Colegio',
            'full_name' => 'Caja Prueba Colegio '.$sequence,
        ]);

        $user = User::factory()->create(['person_id' => $person->id]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
