<?php

namespace Tests\Unit\Modules\Academic\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;

/**
 * Esquema mínimo que necesitan los avisos de asistencia al apoderado.
 *
 * El suite no puede correr todas las migraciones de la aplicación sobre sqlite,
 * así que se arman a mano las tablas que consulta SchoolGateNoticeService: los
 * parámetros del sistema, la persona, el colegio, la estructura académica, el
 * alumno, su matrícula, los apoderados y la asistencia de portería. La tabla de
 * entregas se crea con su migración real, para que la prueba detecte cualquier
 * cambio en ella.
 */
trait BuildsSchoolNoticeSchema
{
    private const SCHOOL_NOTICE_TABLES = [
        'aca_school_notice_deliveries',
        'aca_school_telegram_sessions',
        'aca_school_telegram_subscriptions',
        'aca_school_gate_attendances',
        'aca_school_student_guardians',
        'aca_school_enrollments',
        'aca_school_students',
        'aca_school_sections',
        'aca_school_grades',
        'aca_schools',
        'people',
        'parameters',
    ];

    private function createSchoolNoticeSchema(): void
    {
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->unique();
            $table->string('description')->nullable();
            $table->string('control_type')->nullable();
            $table->text('json_query_data')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('short_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('number')->nullable();
            $table->string('telephone')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_schools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_school_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('level_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_school_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_school_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('student_code')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('aca_school_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('year_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default('activo');
            $table->unsignedBigInteger('guardian_person_id')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->timestamps();
        });

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

        Schema::create('aca_school_gate_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('year_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('enrollment_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->date('attendance_date');
            $table->dateTime('entry_at')->nullable();
            $table->char('entry_status', 1)->nullable();
            $table->dateTime('exit_at')->nullable();
            $table->boolean('early_exit')->default(false);
            $table->unsignedSmallInteger('scans_count')->default(0);
            $table->string('last_event', 3)->nullable();
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('classroom_attendance_id')->nullable();
            $table->unsignedBigInteger('user_id_registers')->nullable();
            $table->timestamps();
        });

        // La migración real, no una copia: si cambia la tabla, la prueba lo nota.
        $migration = require base_path('Modules/Academic/Database/Migrations/2026_10_12_000002_create_aca_school_notice_deliveries_table.php');
        $migration->up();

        $telegramTablesMigration = require base_path('Modules/Academic/Database/Migrations/2026_10_18_000002_create_aca_school_telegram_tables.php');
        $telegramTablesMigration->up();
    }

    private function dropSchoolNoticeSchema(): void
    {
        foreach (self::SCHOOL_NOTICE_TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    /** Escribe el valor de un parámetro del canal. */
    private function setChannelParameter(string $code, ?string $value): void
    {
        DB::table('parameters')->updateOrInsert(
            ['parameter_code' => $code],
            ['value_default' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** Deja el canal listo: credenciales de SMSGate y todos los interruptores encendidos. */
    private function enableSchoolNoticeChannel(): void
    {
        $this->setChannelParameter('SC-00020', 'https://api.sms-gate.app/mobile/v1');
        $this->setChannelParameter('SC-00021', 'colegio');
        $this->setChannelParameter('SC-00022', 'secreto');
        $this->setChannelParameter('SC-00023', '1');
        $this->setChannelParameter('SC-00024', '1');
        $this->setChannelParameter('SC-00025', '1');
        $this->setChannelParameter('SC-00026', '1');
        $this->setChannelParameter('SC-00027', '1');
        $this->setChannelParameter('SC-00030', '0');
        $this->setChannelParameter('SC-00031', '0');
        $this->setChannelParameter('SC-00032', '2000');
    }

    /** Persona con su nombre y su teléfono. Devuelve el id de la persona. */
    private function createPerson(string $name, ?string $phone): int
    {
        return (int) DB::table('people')->insertGetId([
            'full_name' => $name,
            'telephone' => $phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Crea el colegio, la estructura, el alumno y su matrícula. Devuelve el id
     * del alumno y el de la matrícula.
     *
     * @return array{student_id: int, enrollment_id: int}
     */
    private function createEnrolledStudent(?string $enrollmentGuardianPhone = null, ?int $enrollmentGuardianPersonId = null): array
    {
        DB::table('aca_schools')->insert(['id' => 1, 'name' => 'I.E. San Martín', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('aca_school_grades')->insert(['id' => 1, 'name' => 'Primaria 3°', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('aca_school_sections')->insert(['id' => 1, 'school_id' => 1, 'grade_id' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()]);

        $studentPersonId = $this->createPerson('Ana Pérez', null);

        $studentId = (int) DB::table('aca_school_students')->insertGetId([
            'school_id' => 1,
            'person_id' => $studentPersonId,
            'student_code' => '2026-0001',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $enrollmentId = (int) DB::table('aca_school_enrollments')->insertGetId([
            'school_id' => 1,
            'year_id' => 1,
            'student_id' => $studentId,
            'section_id' => 1,
            'status' => 'activo',
            'guardian_person_id' => $enrollmentGuardianPersonId,
            'guardian_phone' => $enrollmentGuardianPhone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['student_id' => $studentId, 'enrollment_id' => $enrollmentId];
    }

    /** Agrega un apoderado al alumno. Devuelve el id de la persona. */
    private function addGuardian(int $studentId, string $name, ?string $phone, bool $primary = false, bool $status = true): int
    {
        $personId = $this->createPerson($name, $phone);

        DB::table('aca_school_student_guardians')->insert([
            'school_id' => 1,
            'student_id' => $studentId,
            'guardian_person_id' => $personId,
            'relationship' => 'madre',
            'is_primary' => $primary,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $personId;
    }

    /**
     * Crea (o actualiza) la asistencia de portería del alumno.
     *
     * @param array<string, mixed> $attributes
     */
    private function createGate(int $studentId, int $enrollmentId, array $attributes = []): AcaSchoolGateAttendance
    {
        $id = (int) DB::table('aca_school_gate_attendances')->insertGetId(array_merge([
            'school_id' => 1,
            'year_id' => 1,
            'section_id' => 1,
            'enrollment_id' => $enrollmentId,
            'student_id' => $studentId,
            'attendance_date' => now()->toDateString(),
            'entry_at' => now(),
            'entry_status' => AcaSchoolGateAttendance::ENTRY_ASISTENCIA,
            'early_exit' => false,
            'scans_count' => 1,
            'last_event' => AcaSchoolGateAttendance::EVENT_ENTRADA,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return AcaSchoolGateAttendance::with(['student.person', 'section.grade.level', 'school'])->find($id);
    }

    /** Entrega del aviso con su modelo. */
    private function findDelivery(int $id): ?AcaSchoolNoticeDelivery
    {
        return AcaSchoolNoticeDelivery::find($id);
    }
}
