<?php

namespace Tests\Unit\Modules\Academic\Http;

use App\Models\Modulo;
use App\Models\Person;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Http\Controllers\AcaSchoolStudentController;
use Modules\Academic\Services\SchoolContextService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Editar los datos de un apoderado desde la ficha del alumno
 * (/academic/school/students/edit/{id}, columna ACCIONES).
 *
 * El permiso es propio de apoderados (`aca_school_alumno_apoderados_editar`):
 * antes esta edicion exigia `aca_school_alumno_editar`, el permiso que toca la
 * ficha del alumno, asi que secretaria no podia mantener apoderados sin recibir
 * acceso al alumno completo.
 *
 * Se invocan las acciones del controlador directamente —el permiso y la sesion
 * viven en la ruta— y se comprueba aparte, leyendo los archivos, que la ruta, el
 * boton y el modal quedan atados al mismo permiso.
 */
class AcaSchoolGuardianEditTest extends TestCase
{
    private const PERMISSION = 'aca_school_alumno_apoderados_editar';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createGuardianSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'role_has_permissions',
            'model_has_permissions',
            'permissions',
            'roles',
            'modulos',
            'parameters',
            'identity_document_type',
            'aca_school_student_guardians',
            'people',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // El permiso
    // -----------------------------------------------------------------

    public function test_la_migracion_crea_el_permiso_y_lo_concede_a_los_roles_de_administracion(): void
    {
        foreach (['admin', 'Administrador'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        (require base_path('Modules/Academic/Database/Migrations/2026_10_13_000002_add_school_student_guardian_edit_permission.php'))->up();

        $this->assertSame(1, Permission::where('name', self::PERMISSION)->count());

        foreach (['admin', 'Administrador'] as $roleName) {
            $this->assertTrue(
                Role::findByName($roleName)->hasPermissionTo(self::PERMISSION),
                "El rol {$roleName} no recibio el permiso de editar apoderados."
            );
        }

        // Queda enlazado al modulo Academico para que el editor de roles lo
        // agrupe en su carpeta.
        $this->assertSame(1, DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('permissions.name', self::PERMISSION)
            ->where('model_has_permissions.model_type', Modulo::class)
            ->where('model_has_permissions.model_id', 'M007')
            ->count());
    }

    public function test_la_ruta_el_boton_y_el_modal_van_atados_al_mismo_permiso(): void
    {
        $routes = file_get_contents(base_path('Modules/Academic/Routes/web.php'));
        $view = file_get_contents(base_path('Modules/Academic/Resources/assets/js/Pages/School/Students/Edit.vue'));

        // La ruta del endpoint exige el permiso nuevo...
        $this->assertStringContainsString('permission:' . self::PERMISSION, $routes);
        $this->assertStringContainsString('guardians_person_update', $routes);

        // ...y el boton de la columna ACCIONES solo se pinta con ese permiso,
        // llamando a la ruta de edicion (nunca con el permiso del alumno).
        $this->assertStringContainsString("hasPermission('" . self::PERMISSION . "')", $view);
        $this->assertStringContainsString('v-if="canEditGuardianData"', $view);
        $this->assertStringContainsString("route('aca_school_students_guardians_person_update'", $view);
    }

    // -----------------------------------------------------------------
    // Editar el apoderado
    // -----------------------------------------------------------------

    public function test_edita_los_datos_de_la_persona_y_del_vinculo(): void
    {
        $guardianId = $this->guardian();

        $response = $this->controller()->updateGuardianPerson(
            Request::create('/academic/school/students/guardians/1/person', 'PUT', [
                'document_type_id' => 1,
                'number' => '87654321',
                'names' => 'Rosa Elena',
                'father_lastname' => 'Quispe',
                'mother_lastname' => 'Mamani',
                'gender' => 'F',
                'birthdate' => '1985-03-12',
                'telephone' => '987654321',
                'email' => 'rosa.quispe@example.com',
                'address' => 'Av. Los Andes 123',
                'relationship' => 'madre',
                'is_primary' => true,
                'status' => true,
            ]),
            $guardianId
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) json_decode((string) $response->getContent(), true)['success']);

        $guardian = AcaSchoolStudentGuardian::with('person')->find($guardianId);

        $this->assertSame('madre', $guardian->relationship);
        $this->assertTrue($guardian->is_primary);
        $this->assertTrue($guardian->status);

        $person = $guardian->person;
        $this->assertSame('Rosa Elena', $person->names);
        $this->assertSame('Quispe', $person->father_lastname);
        $this->assertSame('Mamani', $person->mother_lastname);
        $this->assertSame('Quispe Mamani Rosa Elena', $person->full_name);
        $this->assertSame('87654321', $person->number);
        $this->assertSame('rosa.quispe@example.com', $person->email);
        $this->assertSame('987654321', $person->telephone);
        $this->assertSame('Av. Los Andes 123', $person->address);
        $this->assertSame('1985-03-12', (string) $person->birthdate);
    }

    public function test_solo_puede_quedar_un_apoderado_principal(): void
    {
        $primero = $this->guardian(true);
        $segundo = $this->guardian(false, 'Pedro Apoderado');

        $this->controller()->updateGuardianPerson(
            Request::create('/academic/school/students/guardians/2/person', 'PUT', $this->payload(['is_primary' => true])),
            $segundo
        );

        $this->assertFalse(AcaSchoolStudentGuardian::find($primero)->is_primary);
        $this->assertTrue(AcaSchoolStudentGuardian::find($segundo)->is_primary);
    }

    public function test_rechaza_el_documento_de_otra_persona(): void
    {
        $guardianId = $this->guardian();
        Person::create([
            'document_type_id' => 1,
            'number' => '11223344',
            'names' => 'Otro',
            'father_lastname' => 'Apoderado',
            'mother_lastname' => 'Ajeno',
        ]);

        $response = $this->controller()->updateGuardianPerson(
            Request::create('/academic/school/students/guardians/1/person', 'PUT', $this->payload(['number' => '11223344'])),
            $guardianId
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'Ese numero de documento ya pertenece a otra persona.',
            json_decode((string) $response->getContent(), true)['message']
        );
    }

    public function test_exige_parentesco_valido(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->updateGuardianPerson(
            Request::create('/academic/school/students/guardians/1/person', 'PUT', $this->payload(['relationship' => 'vecino'])),
            $this->guardian()
        );
    }

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'document_type_id' => 1,
            'number' => '87654321',
            'names' => 'Rosa Elena',
            'father_lastname' => 'Quispe',
            'mother_lastname' => 'Mamani',
            'gender' => 'F',
            'telephone' => '987654321',
            'email' => 'rosa.quispe@example.com',
            'address' => 'Av. Los Andes 123',
            'relationship' => 'madre',
            'is_primary' => false,
            'status' => true,
        ], $overrides);
    }

    /**
     * Crea un apoderado del alumno 1 y devuelve el id del vinculo.
     */
    private function guardian(bool $primary = false, string $fullName = 'Rosa Quispe'): int
    {
        $person = Person::create([
            'document_type_id' => 1,
            'number' => '8765432' . random_int(1, 9),
            'names' => 'Rosa',
            'father_lastname' => 'Quispe',
            'mother_lastname' => 'Mamani',
            'full_name' => $fullName,
        ]);

        return (int) DB::table('aca_school_student_guardians')->insertGetId([
            'school_id' => 1,
            'student_id' => 1,
            'guardian_person_id' => $person->id,
            'relationship' => 'otro',
            'is_primary' => $primary,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function controller(): AcaSchoolStudentController
    {
        return new AcaSchoolStudentController(app(SchoolContextService::class));
    }

    /**
     * Esquema minimo: personas, catalogo de documentos, apoderados, spatie y el
     * modulo Academico (para el enlace del permiso).
     */
    private function createGuardianSchema(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->string('number')->nullable();
            $table->string('names')->nullable();
            $table->string('father_lastname')->nullable();
            $table->string('mother_lastname')->nullable();
            $table->string('short_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('gender', 1)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('identity_document_type', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
        });

        DB::table('identity_document_type')->insert(['id' => 1, 'description' => 'DNI']);

        // El accesor `formatted_name` de Person lee el parametro P000020.
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->unique();
            $table->string('description')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
        });

        DB::table('parameters')->insert([
            'parameter_code' => 'P000020',
            'description' => 'Formato del nombre',
            'value_default' => '1',
        ]);

        Schema::create('aca_school_student_guardians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('guardian_person_id');
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $tables = config('permission.table_names');

        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['roles'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['model_has_permissions'], function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->string('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create($tables['role_has_permissions'], function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('modulos', function (Blueprint $table) {
            $table->string('identifier', 4)->primary();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('modulos')->insert(['identifier' => 'M007', 'description' => 'Academico']);
    }
}
