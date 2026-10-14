<?php

use App\Models\Person;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Http\Controllers\AcaSchoolTestModeController;
use Modules\Academic\Services\SchoolTestDataService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Academico > Modos de Prueba: llenar, borrar y dejar en modo produccion
|--------------------------------------------------------------------------
|
| Corren como "Unit" y no como "Feature" porque la suite Feature de este
| repositorio no arranca en sqlite (migraciones MySQL-only de otros modulos):
| aqui se crean a mano las tablas que toca el servicio, igual que hacen el
| catalogo de productos y el banco de plantillas.
|
| Lo que se fija:
|  - fill() crea la estructura marcada (PRUEBA / TEST...) y la anota en el
|    registro de datos de prueba.
|  - Volver a llenar no duplica la estructura: completa lo que falta.
|  - purge() borra lo anotado y lo generado con ello (asistencia, cobros) sin
|    tocar nada real, y conserva los comprobantes ya enviados a SUNAT.
|  - lockProduction() borra y bloquea de forma definitiva: despues ni llenar ni
|    borrar ni volver a bloquear.
|  - La pantalla solo existe con tipo de negocio Colegio (5) o Colegio y
|    capacitaciones (6).
|
*/

uses(TestCase::class);

beforeEach(function () {
    foreach (['people', 'aca_teachers', 'aca_school_years', 'aca_school_levels', 'aca_school_grades',
        'aca_school_sections', 'aca_school_journeys', 'aca_school_areas', 'aca_school_students',
        'aca_school_enrollments', 'aca_school_student_guardians', 'aca_school_schedules',
        'aca_school_test_records', 'aca_school_test_mode', 'aca_school_attendances', 'aca_school_charges',
        'sales', 'sale_documents', 'sale_document_items', 'sale_products', 'parameters', 'aca_schools'] as $tabla) {
        Schema::dropIfExists($tabla);
    }

    Schema::create('aca_schools', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('name');
        $tabla->boolean('is_default')->default(false);
        $tabla->boolean('status')->default(true);
        $tabla->string('type')->default('privado');
        $tabla->timestamps();
    });

    Schema::create('parameters', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('parameter_code')->nullable();
        $tabla->string('value_default')->nullable();
    });

    Schema::create('people', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('document_type_id')->nullable();
        $tabla->string('number');
        $tabla->string('names')->nullable();
        $tabla->string('father_lastname')->nullable();
        $tabla->string('mother_lastname')->nullable();
        $tabla->string('short_name')->nullable();
        $tabla->string('full_name')->nullable();
        $tabla->string('gender')->nullable();
        $tabla->boolean('status')->default(true);
        $tabla->unsignedBigInteger('country_id')->default(1);
        $tabla->boolean('is_client')->nullable();
        $tabla->boolean('is_provider')->nullable();
        $tabla->string('ubigeo_description')->nullable();
        $tabla->date('birthdate')->nullable();
        $tabla->string('email')->nullable();
        $tabla->string('telephone')->nullable();
        $tabla->string('address')->nullable();
        $tabla->unsignedBigInteger('company_person_id')->nullable();
        $tabla->timestamps();
    });

    Schema::create('aca_teachers', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('teacher_code')->nullable();
        $tabla->unsignedBigInteger('person_id');
        $tabla->timestamps();
    });

    Schema::create('aca_school_years', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedSmallInteger('year');
        $tabla->string('status')->default('announced');
        $tabla->text('observations')->nullable();
        $tabla->timestamps();
    });

    Schema::create('aca_school_levels', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->string('code');
        $tabla->string('name');
        $tabla->unsignedSmallInteger('sort_order')->default(0);
        $tabla->boolean('status')->default(true);
        $tabla->string('scale')->nullable();
        $tabla->timestamps();
    });

    Schema::create('aca_school_grades', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('level_id');
        $tabla->string('name');
        $tabla->unsignedSmallInteger('sort_order')->default(0);
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_sections', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('grade_id');
        $tabla->string('name');
        $tabla->unsignedSmallInteger('capacity')->default(30);
        $tabla->string('shift')->default('manana');
        $tabla->unsignedBigInteger('tutor_person_id')->nullable();
        $tabla->unsignedBigInteger('auxiliary_person_id')->nullable();
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_journeys', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('level_id')->nullable();
        $tabla->string('shift')->default('manana');
        $tabla->time('entry_time');
        $tabla->time('exit_time');
        $tabla->unsignedSmallInteger('tolerance_minutes')->default(10);
        $tabla->time('recess_start')->nullable();
        $tabla->time('recess_end')->nullable();
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_areas', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->string('name');
        $tabla->string('level');
        $tabla->unsignedSmallInteger('sort_order')->default(0);
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_students', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('person_id');
        $tabla->string('student_code')->nullable();
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_enrollments', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('year_id');
        $tabla->unsignedBigInteger('student_id');
        $tabla->unsignedBigInteger('section_id');
        $tabla->string('type')->default('nueva');
        $tabla->string('status')->default('activo');
        $tabla->date('enrollment_date');
        $tabla->unsignedBigInteger('guardian_person_id')->nullable();
        $tabla->string('guardian_relationship')->nullable();
        $tabla->string('guardian_phone')->nullable();
        $tabla->text('observations')->nullable();
        $tabla->unsignedBigInteger('sale_note_id')->nullable();
        $tabla->unsignedBigInteger('document_id')->nullable();
        $tabla->unsignedBigInteger('user_id_registers')->nullable();
        $tabla->timestamps();
    });

    Schema::create('aca_school_student_guardians', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('student_id');
        $tabla->unsignedBigInteger('guardian_person_id')->nullable();
        $tabla->string('relationship')->default('otro');
        $tabla->boolean('is_primary')->default(false);
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_schedules', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('year_id');
        $tabla->unsignedBigInteger('section_id');
        $tabla->unsignedBigInteger('area_id')->nullable();
        $tabla->unsignedBigInteger('teacher_person_id')->nullable();
        $tabla->unsignedTinyInteger('weekday');
        $tabla->time('start_time');
        $tabla->time('end_time');
        $tabla->string('room')->nullable();
        $tabla->unsignedSmallInteger('sort_order')->default(0);
        $tabla->boolean('status')->default(true);
        $tabla->timestamps();
    });

    Schema::create('aca_school_test_records', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('run_id', 40);
        $tabla->unsignedBigInteger('school_id');
        $tabla->string('entity', 30);
        $tabla->unsignedBigInteger('record_id');
        $tabla->string('label', 180)->nullable();
        $tabla->timestamps();
    });

    Schema::create('aca_school_test_mode', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->timestamp('locked_at')->nullable();
        $tabla->unsignedBigInteger('locked_by_user_id')->nullable();
        $tabla->string('locked_by_name', 180)->nullable();
        $tabla->timestamp('last_fill_at')->nullable();
        $tabla->timestamp('last_purge_at')->nullable();
        $tabla->timestamps();
    });

    Schema::create('aca_school_attendances', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('year_id');
        $tabla->unsignedBigInteger('section_id');
        $tabla->unsignedBigInteger('enrollment_id');
        $tabla->date('attendance_date');
        $tabla->string('status');
        $tabla->timestamps();
    });

    Schema::create('aca_school_charges', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('school_id');
        $tabla->unsignedBigInteger('enrollment_id');
        $tabla->string('description');
        $tabla->decimal('amount', 10, 2);
        $tabla->string('status')->default('pendiente');
        $tabla->unsignedBigInteger('sale_id')->nullable();
        $tabla->unsignedBigInteger('sale_document_id')->nullable();
        $tabla->timestamps();
    });

    Schema::create('sales', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('client_id')->nullable();
        $tabla->timestamps();
    });

    Schema::create('sale_documents', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('sale_id')->nullable();
        $tabla->dateTime('invoice_send_date')->nullable();
        $tabla->timestamps();
    });

    Schema::create('sale_document_items', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('document_id')->nullable();
        $tabla->unsignedBigInteger('sale_id')->nullable();
        $tabla->timestamps();
    });

    Schema::create('sale_products', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('sale_id')->nullable();
        $tabla->timestamps();
    });

    // Colegio por defecto y tipo de negocio Colegio.
    DB::table('aca_schools')->insert(['id' => 1, 'name' => 'Mi Colegio', 'is_default' => true, 'status' => true, 'type' => 'privado']);
    DB::table('parameters')->insert(['parameter_code' => 'P000009', 'value_default' => '5']);
    DB::table('parameters')->insert(['parameter_code' => 'PTM0005', 'value_default' => '0']);
});

/** Servicio con el contexto limpio de cada prueba. */
function servicioModoPrueba(): SchoolTestDataService
{
    return app(SchoolTestDataService::class);
}

it('llena el colegio con datos marcados y los registra', function () {
    $servicio = servicioModoPrueba();
    $resultado = $servicio->fill(['sections_per_grade' => 1, 'students_per_section' => 2]);

    expect($resultado['total'])->toBeGreaterThan(0)
        ->and(DB::table('aca_school_levels')->count())->toBe(4)
        ->and(DB::table('aca_school_levels')->where('name', 'Estimulación')->exists())->toBeTrue()
        ->and(DB::table('aca_school_grades')->count())->toBe(16)
        ->and(DB::table('aca_school_sections')->count())->toBe(16)
        ->and(DB::table('aca_school_students')->count())->toBe(32)
        ->and(DB::table('aca_school_enrollments')->count())->toBe(32)
        ->and(DB::table('aca_school_student_guardians')->count())->toBe(32)
        ->and(DB::table('aca_teachers')->count())->toBe(5)
        ->and(DB::table('aca_school_areas')->count())->toBeGreaterThan(0)
        ->and(DB::table('aca_school_schedules')->count())->toBe(16 * 10)
        ->and(DB::table('aca_school_test_records')->count())->toBe($resultado['total']);

    expect(Person::where('number', 'like', 'TEST%')->count())->toBeGreaterThan(0)
        ->and(AcaSchoolStudent::where('student_code', 'like', 'PRUEBA-%')->count())->toBe(32)
        ->and(AcaSchoolGrade::where('name', 'like', 'PRUEBA%')->count())->toBe(16);

    // Secciones con letras reales (A, B, C...) y personas con nombres reales.
    expect(AcaSchoolSection::where('name', 'A')->count())->toBe(16)
        ->and(AcaSchoolSection::where('name', 'like', 'PRUEBA%')->count())->toBe(0)
        ->and(Person::where('full_name', 'like', '%PRUEBA%')->count())->toBe(0)
        ->and(Person::where('names', 'like', '%Alumno%')->count())->toBe(0);

    // El resumen que ve la pantalla sale del registro.
    expect($servicio->summary()['total'])->toBe($resultado['total']);
});

it('no duplica la estructura cuando se llena otra vez', function () {
    $servicio = servicioModoPrueba();
    $servicio->fill(['sections_per_grade' => 1, 'students_per_section' => 2]);
    $segundo = $servicio->fill(['sections_per_grade' => 1, 'students_per_section' => 2]);

    expect($segundo['total'])->toBe(0)
        ->and(DB::table('aca_school_sections')->count())->toBe(16)
        ->and(DB::table('aca_school_students')->count())->toBe(32);
});

it('borra lo de prueba y lo generado sin tocar los datos reales', function () {
    $servicio = servicioModoPrueba();

    // Datos reales del colegio (no los del modo prueba).
    $realLevel = DB::table('aca_school_levels')->insertGetId([
        'school_id' => 1, 'code' => 'secundaria', 'name' => 'Secundaria', 'sort_order' => 1, 'status' => true,
    ]);
    $realGrade = AcaSchoolGrade::create(['school_id' => 1, 'level_id' => $realLevel, 'name' => 'REAL 1°', 'sort_order' => 1, 'status' => true]);
    $realSection = AcaSchoolSection::create(['school_id' => 1, 'grade_id' => $realGrade->id, 'name' => 'REAL A', 'capacity' => 30, 'shift' => 'manana', 'status' => true]);
    $realPerson = Person::create(['document_type_id' => '0', 'number' => 'REAL0001', 'names' => 'Real', 'father_lastname' => 'Real', 'mother_lastname' => 'Real', 'short_name' => 'Real', 'full_name' => 'Real Real Real', 'gender' => 'M', 'status' => true, 'country_id' => 1]);
    $realStudent = AcaSchoolStudent::create(['school_id' => 1, 'person_id' => $realPerson->id, 'student_code' => 'REAL-0001', 'status' => true]);
    $realYearId = DB::table('aca_school_years')->insertGetId([
        'school_id' => 1, 'year' => (int) now()->format('Y'), 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $realYear = AcaSchoolYear::find($realYearId);
    $realEnrollment = DB::table('aca_school_enrollments')->insertGetId([
        'school_id' => 1, 'year_id' => $realYear->id, 'student_id' => $realStudent->id, 'section_id' => $realSection->id,
        'type' => 'nueva', 'status' => 'activo', 'enrollment_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    $servicio->fill(['sections_per_grade' => 1, 'students_per_section' => 2]);

    // Datos generados al usar una matricula de prueba + cobro con comprobante.
    $prueba = DB::table('aca_school_enrollments')->where('id', '!=', $realEnrollment)->orderBy('id')->first();
    DB::table('aca_school_attendances')->insert([
        'school_id' => 1, 'year_id' => $prueba->year_id, 'section_id' => $prueba->section_id,
        'enrollment_id' => $prueba->id, 'attendance_date' => now()->toDateString(), 'status' => 'A',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $venta = DB::table('sales')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    $enviado = DB::table('sale_documents')->insertGetId(['sale_id' => $venta, 'invoice_send_date' => now(), 'created_at' => now(), 'updated_at' => now()]);
    $pendiente = DB::table('sale_documents')->insertGetId(['sale_id' => $venta, 'invoice_send_date' => null, 'created_at' => now(), 'updated_at' => now()]);
    $cobro = DB::table('aca_school_charges')->insertGetId([
        'school_id' => 1, 'enrollment_id' => $prueba->id, 'description' => 'PRUEBA mensualidad', 'amount' => 100,
        'status' => 'pendiente', 'sale_id' => $venta, 'sale_document_id' => $pendiente, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('sale_document_items')->insert(['document_id' => $pendiente, 'sale_id' => $venta, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('sale_products')->insert(['sale_id' => $venta, 'created_at' => now(), 'updated_at' => now()]);

    $resultado = $servicio->purge();

    // Se borro lo de prueba y lo generado con ello.
    expect(DB::table('aca_school_test_records')->count())->toBe(0)
        ->and(DB::table('aca_school_students')->where('student_code', 'like', 'PRUEBA-%')->count())->toBe(0)
        ->and(DB::table('aca_school_sections')->count())->toBe(1)
        ->and(DB::table('aca_school_attendances')->count())->toBe(0)
        ->and(DB::table('aca_school_charges')->where('id', $cobro)->count())->toBe(0)
        ->and(DB::table('aca_school_enrollments')->where('id', $prueba->id)->count())->toBe(0)
        ->and(DB::table('sale_documents')->where('id', $pendiente)->count())->toBe(0);

    // El comprobante ya enviado a SUNAT no se toca (ni su venta).
    expect(DB::table('sale_documents')->where('id', $enviado)->exists())->toBeTrue()
        ->and(DB::table('sales')->where('id', $venta)->exists())->toBeTrue()
        ->and($resultado['documents_kept'])->toBe(1);

    // Los datos reales siguen ahi.
    expect(DB::table('aca_school_levels')->where('id', $realLevel)->exists())->toBeTrue()
        ->and(DB::table('aca_school_grades')->where('id', $realGrade->id)->exists())->toBeTrue()
        ->and(DB::table('aca_school_sections')->where('id', $realSection->id)->exists())->toBeTrue()
        ->and(DB::table('aca_school_students')->where('id', $realStudent->id)->exists())->toBeTrue()
        ->and(DB::table('aca_school_enrollments')->where('id', $realEnrollment)->exists())->toBeTrue()
        ->and(DB::table('people')->where('id', $realPerson->id)->exists())->toBeTrue();
});

it('deja el sistema en modo produccion y bloquea todo de forma definitiva', function () {
    $servicio = servicioModoPrueba();
    $servicio->fill(['sections_per_grade' => 1, 'students_per_section' => 1]);

    $servicio->lockProduction(7, 'Admin Prueba');

    expect($servicio->isLocked())->toBeTrue()
        ->and($servicio->lockInfo()['locked'])->toBeTrue()
        ->and($servicio->lockInfo()['locked_by'])->toBe('Admin Prueba')
        ->and(DB::table('aca_school_test_records')->count())->toBe(0)
        ->and(DB::table('aca_school_test_mode')->where('locked_by_user_id', 7)->exists())->toBeTrue();

    // Ya no se puede volver a llenar: el bloqueo es definitivo.
    expect(fn () => $servicio->fill([]))->toThrow(RuntimeException::class);
});

it('no permite llenar, borrar ni bloquear otra vez en modo produccion', function () {
    $servicio = servicioModoPrueba();
    $servicio->lockProduction(1, 'Admin');

    $fallos = 0;

    foreach ([
        fn () => $servicio->fill([]),
        fn () => $servicio->purge(),
        fn () => $servicio->lockProduction(1, 'Admin'),
    ] as $accion) {
        try {
            $accion();
        } catch (RuntimeException $exception) {
            $fallos++;
        }
    }

    expect($fallos)->toBe(3);
});

it('solo esta disponible con tipo de negocio colegio', function () {
    expect(servicioModoPrueba()->isAvailable())->toBeTrue();

    DB::table('parameters')->where('parameter_code', 'P000009')->update(['value_default' => '6']);
    expect(servicioModoPrueba()->isAvailable())->toBeTrue();

    DB::table('parameters')->where('parameter_code', 'P000009')->update(['value_default' => '2']);
    expect(servicioModoPrueba()->isAvailable())->toBeFalse();
});

it('el controlador rechaza el modo prueba fuera del tipo de negocio colegio', function () {
    DB::table('parameters')->where('parameter_code', 'P000009')->update(['value_default' => '2']);

    $controlador = app(AcaSchoolTestModeController::class);

    expect(fn () => $controlador->index())->toThrow(HttpException::class)
        ->and(fn () => $controlador->fill(Request::create('/x', 'POST')))->toThrow(HttpException::class)
        ->and(fn () => $controlador->purge(Request::create('/x', 'POST')))->toThrow(HttpException::class)
        ->and(fn () => $controlador->production(Request::create('/x', 'POST')))->toThrow(HttpException::class);
});

it('exige la frase exacta y la contrasena antes de llenar, borrar o dejar en produccion', function () {
    $controlador = app(AcaSchoolTestModeController::class);

    // Sin la frase de confirmacion el borrado no pasa.
    expect(fn () => $controlador->purge(Request::create('/x', 'POST', ['confirmation' => 'ELIMINAR'])))->toThrow(ValidationException::class);

    // Dejar en produccion pide la frase exacta, la contrasena y la valida
    // contra el usuario conectado (sin sesion no hay contrasena valida).
    expect(fn () => $controlador->production(Request::create('/x', 'POST', [
        'confirmation' => 'Otra cosa',
        'password' => 'secreta',
    ])))->toThrow(ValidationException::class);

    expect(fn () => $controlador->production(Request::create('/x', 'POST', [
        'confirmation' => AcaSchoolTestModeController::PRODUCTION_CONFIRMATION,
        'password' => 'secreta',
    ])))->toThrow(ValidationException::class);

    // Los limites del llenado tambien se validan.
    expect(fn () => $controlador->fill(Request::create('/x', 'POST', ['students_per_section' => 999])))->toThrow(ValidationException::class);

    // Con datos validos el llenado responde JSON de exito.
    $respuesta = $controlador->fill(Request::create('/x', 'POST', ['sections_per_grade' => 1, 'students_per_section' => 1]));

    expect($respuesta->getStatusCode())->toBe(200)
        ->and($respuesta->getData(true)['success'])->toBeTrue()
        ->and(DB::table('aca_school_students')->count())->toBe(16);
});
