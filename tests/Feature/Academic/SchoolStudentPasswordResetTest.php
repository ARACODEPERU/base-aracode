<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolStudent;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * El administrador repone la contraseña del aula virtual al número de DNI del
 * alumno; si la cuenta todavía no existe, se crea con esa contraseña.
 */
class SchoolStudentPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolStudent $student;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = AcaSchool::defaultSchool();
        $this->school->update(['name' => 'Colegio Semilla', 'status' => true]);

        $person = Person::create([
            'names' => 'Lucía',
            'father_lastname' => 'Quispe',
            'mother_lastname' => 'Ramos',
            'full_name' => 'Quispe Ramos Lucía',
            'number' => '87654321',
            'email' => 'lucia@colegio.test',
        ]);

        $this->student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $person->id,
            'student_code' => AcaSchoolStudent::nextStudentCode($this->school->id),
            'status' => true,
        ]);

        $this->admin = $this->userWithPermission('aca_school_alumno_editar');
    }

    public function test_el_administrador_repone_la_contrasena_al_dni(): void
    {
        // Cuenta del alumno con una contraseña que ya cambió desde su perfil.
        $user = User::create([
            'name' => 'Quispe Ramos Lucía',
            'email' => 'lucia@colegio.test',
            'password' => Hash::make('clavepropia'),
            'person_id' => $this->student->person_id,
            'status' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('aca_school_students_password_reset', $this->student->id));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('87654321', $user->fresh()->password));
        $this->assertFalse(Hash::check('clavepropia', $user->fresh()->password));
    }

    public function test_si_el_alumno_no_tiene_cuenta_se_crea_con_el_dni(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('aca_school_students_password_reset', $this->student->id));

        $response->assertOk()->assertJson(['success' => true]);

        $user = User::where('person_id', $this->student->person_id)->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('87654321', $user->password));
    }

    public function test_sin_dni_no_se_repone_la_contrasena(): void
    {
        // La columna no admite NULL: sin DNI equivale a un número vacío.
        $this->student->person->update(['number' => '']);

        User::create([
            'name' => 'Quispe Ramos Lucía',
            'email' => 'lucia@colegio.test',
            'password' => Hash::make('clavepropia'),
            'person_id' => $this->student->person_id,
            'status' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('aca_school_students_password_reset', $this->student->id));

        $response->assertStatus(422)->assertJson(['success' => false]);

        $user = User::where('person_id', $this->student->person_id)->first();
        $this->assertTrue(Hash::check('clavepropia', $user->password));
    }

    public function test_sin_permiso_no_puede_reponer_la_contrasena(): void
    {
        $sinPermiso = $this->userWithPermission('aca_school_alumno_listado');

        $this->actingAs($sinPermiso)
            ->post(route('aca_school_students_password_reset', $this->student->id))
            ->assertForbidden();

        $this->assertNull(User::where('person_id', $this->student->person_id)->first());
    }

    private function userWithPermission(string ...$permissions): User
    {
        // Cada usuario de prueba necesita un documento distinto.
        static $sequence = 0;
        $sequence++;

        $person = Person::create([
            'number' => (string) (90000000 + $sequence),
            'names' => 'Secretaría',
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Colegio',
            'full_name' => 'Secretaría Prueba Colegio',
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
