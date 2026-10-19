<?php

namespace Tests\Unit\Modules\Health\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Entities\HealPatientCharge;
use Modules\Health\Entities\HealProcedure;
use Spatie\Permission\Models\Role;

/**
 * Esquema mínimo de Procedimientos/cobros para las pruebas.
 *
 * El suite no corre todas las migraciones de la aplicación sobre sqlite (hay
 * migraciones con esquema MySQL), así que cada prueba arma solo las tablas que
 * toca: las del módulo Salud, las de spatie que resuelven el rol del usuario y
 * lo mínimo de Sales para el comprobante asociado.
 *
 * De la reserva sí se ejecuta la migración real, para que la prueba note si la
 * tabla deja de coincidir con lo que usa el controlador.
 */
trait BuildsPatientChargeSchema
{
    private const RESERVATION_MIGRATION = 'Modules/Health/Database/Migrations/2026_10_19_000001_add_billing_reservation_to_heal_patient_charges.php';

    private function createPatientChargeSchema(): void
    {
        Schema::create('roles', function (Blueprint $tabla) {
            $tabla->bigIncrements('id');
            $tabla->string('name');
            $tabla->string('guard_name');
            $tabla->timestamps();
            $tabla->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $tabla) {
            $tabla->bigIncrements('id');
            $tabla->string('name');
            $tabla->string('guard_name');
            $tabla->timestamps();
            $tabla->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $tabla) {
            $tabla->unsignedBigInteger('role_id');
            $tabla->string('model_type');
            $tabla->unsignedBigInteger('model_id');
            $tabla->primary(['role_id', 'model_type', 'model_id']);
        });

        Schema::create('model_has_permissions', function (Blueprint $tabla) {
            $tabla->unsignedBigInteger('permission_id');
            $tabla->string('model_type');
            $tabla->unsignedBigInteger('model_id');
            $tabla->primary(['permission_id', 'model_type', 'model_id']);
        });

        Schema::create('role_has_permissions', function (Blueprint $tabla) {
            $tabla->unsignedBigInteger('permission_id');
            $tabla->unsignedBigInteger('role_id');
            $tabla->primary(['permission_id', 'role_id']);
        });

        // El modelo Person no declara $table: Laravel pluraliza a `people`.
        Schema::create('people', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('full_name')->nullable();
            $tabla->string('number')->nullable();
            $tabla->string('document_type_id')->nullable();
            $tabla->string('ubigeo')->nullable();
            $tabla->string('telephone')->nullable();
            $tabla->string('email')->nullable();
            $tabla->string('address')->nullable();
            $tabla->timestamps();
        });

        Schema::create('users', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('name');
            $tabla->string('email')->unique();
            $tabla->string('password');
            $tabla->unsignedBigInteger('person_id')->nullable();
            $tabla->timestamps();
        });

        // Inertia comparte la empresa en cada render (AppServiceProvider).
        Schema::create('companies', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('name')->nullable();
            $tabla->timestamps();
        });

        // Person::full_name lee el formato de nombre configurado (P000020).
        Schema::create('parameters', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('parameter_code')->unique();
            $tabla->string('value_default')->nullable();
        });

        // Los modelos de Salud registran actividad: la tabla debe existir.
        Schema::create('heal_activities', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('actor_type')->nullable();
            $tabla->unsignedBigInteger('actor_id')->nullable();
            $tabla->unsignedBigInteger('actor_person_id')->nullable();
            $tabla->string('actor_display')->nullable();
            $tabla->string('activity_type')->nullable();
            $tabla->string('subject_type')->nullable();
            $tabla->unsignedBigInteger('subject_id')->nullable();
            $tabla->unsignedBigInteger('patient_id')->nullable();
            $tabla->string('patient_display')->nullable();
            $tabla->json('metadata')->nullable();
            $tabla->string('ip')->nullable();
            $tabla->string('user_agent')->nullable();
            $tabla->timestamps();
        });

        // Opciones de facturación de los cobros (BillingOptions).
        Schema::create('heal_settings', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->json('settings')->nullable();
            $tabla->timestamps();
        });

        Schema::create('heal_doctors', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->unsignedBigInteger('person_id')->nullable();
            $tabla->unsignedBigInteger('user_id')->nullable();
            $tabla->string('specialty')->nullable();
            $tabla->string('attention_service_type')->nullable();
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        Schema::create('heal_patients', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->unsignedBigInteger('person_id')->nullable();
            $tabla->unsignedBigInteger('user_id')->nullable();
            $tabla->string('patient_code')->nullable();
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        Schema::create('heal_attentions', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->timestamp('attention_at')->nullable();
            $tabla->string('service_type')->nullable();
            $tabla->unsignedBigInteger('patient_id');
            $tabla->unsignedBigInteger('doctor_id')->nullable();
            $tabla->unsignedBigInteger('history_id')->nullable();
            $tabla->unsignedBigInteger('user_id')->nullable();
            $tabla->timestamp('signed_at')->nullable();
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        Schema::create('heal_procedures', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('name', 160);
            $tabla->string('category', 80)->nullable();
            $tabla->text('description')->nullable();
            $tabla->decimal('default_price', 12, 2)->default(0);
            $tabla->string('currency_type_id', 3)->default('PEN');
            $tabla->boolean('is_consultation')->default(false);
            $tabla->boolean('is_active')->default(true);
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        Schema::create('sunat_currency_types', function (Blueprint $tabla) {
            $tabla->string('id');
            $tabla->boolean('active')->default(true);
            $tabla->string('symbol')->nullable();
            $tabla->string('description');
        });

        Schema::create('sale_documents', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->unsignedBigInteger('sale_id')->nullable();
            $tabla->integer('status')->default(0);
            $tabla->string('invoice_serie')->nullable();
            $tabla->string('number')->nullable();
            $tabla->timestamps();
        });

        // Misma forma que la migración real de heal_patient_charges.
        Schema::create('heal_patient_charges', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->unsignedBigInteger('patient_id');
            $tabla->unsignedBigInteger('attention_id')->nullable();
            $tabla->unsignedBigInteger('doctor_id')->nullable();
            $tabla->unsignedBigInteger('procedure_id')->nullable();
            $tabla->unsignedBigInteger('sale_id')->nullable();
            $tabla->unsignedBigInteger('sale_document_id')->nullable();
            $tabla->string('name_snapshot', 160);
            $tabla->text('description_snapshot')->nullable();
            $tabla->decimal('default_price', 12, 2)->default(0);
            $tabla->decimal('price', 12, 2)->default(0);
            $tabla->decimal('quantity', 12, 2)->default(1);
            $tabla->decimal('total', 12, 2)->default(0);
            $tabla->string('currency_type_id', 3)->default('PEN');
            $tabla->string('status', 20)->default('pending');
            $tabla->timestamp('charged_at')->nullable();
            $tabla->text('notes')->nullable();
            $tabla->unsignedBigInteger('created_by')->nullable();
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        DB::table('sunat_currency_types')->insert([
            ['id' => 'PEN', 'active' => true, 'symbol' => 'S/', 'description' => 'Soles'],
        ]);

        // La migración real: agrega billed_at y los índices del listado.
        (require base_path(self::RESERVATION_MIGRATION))->up();
    }

    private function dropPatientChargeSchema(): void
    {
        // Se tira por tablas en vez de correr los down() de la migración: lo que
        // prueba el suite son sus up() reales.
        foreach ([
            'heal_patient_charges',
            'sale_documents',
            'sunat_currency_types',
            'heal_procedures',
            'heal_attentions',
            'heal_patients',
            'heal_doctors',
            'heal_settings',
            'heal_activities',
            'parameters',
            'companies',
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'users',
            'people',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }

    /** Usuario administrador: puede elegir doctor y ver todos los cobros. */
    private function adminUser(): \App\Models\User
    {
        $user = \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@clinica.test',
            'password' => 'secreto-de-prueba',
        ]);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        return $user;
    }

    /** Doctor con su usuario vinculado, para probar el alcance por doctor. */
    private function doctorUser(string $name): array
    {
        $personId = $this->personFixture($name);

        $user = \App\Models\User::create([
            'name' => $name,
            'email' => strtolower($name) . '@clinica.test',
            'password' => 'secreto-de-prueba',
            'person_id' => $personId,
        ]);

        $doctorId = DB::table('heal_doctors')->insertGetId([
            'person_id' => $personId,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['user' => $user, 'doctor' => HealDoctor::find($doctorId)];
    }

    private function personFixture(string $name): int
    {
        return DB::table('people')->insertGetId([
            'full_name' => $name,
            'number' => '7000000' . random_int(1, 9),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function patientFixture(string $name = 'Ana Pérez'): HealPatient
    {
        $personId = DB::table('people')->insertGetId([
            'full_name' => $name,
            'number' => '8000000' . random_int(1, 9),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return HealPatient::create(['person_id' => $personId]);
    }

    private function procedureFixture(array $attributes = []): HealProcedure
    {
        return HealProcedure::create([
            'name' => $attributes['name'] ?? 'Consulta',
            'category' => 'Consulta',
            'default_price' => 50,
            'currency_type_id' => 'PEN',
            'is_consultation' => true,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

    private function attentionFixture(HealPatient $patient, ?HealDoctor $doctor = null): HealAttention
    {
        $attentionId = DB::table('heal_attentions')->insertGetId([
            'attention_at' => now()->subDay(),
            'service_type' => 'general',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return HealAttention::find($attentionId);
    }

    private function chargeFixture(HealPatient $patient, array $attributes = []): HealPatientCharge
    {
        $procedure = $this->procedureFixture();

        return HealPatientCharge::create([
            'patient_id' => $patient->id,
            'attention_id' => $attributes['attention_id'] ?? null,
            'doctor_id' => $attributes['doctor_id'] ?? null,
            'procedure_id' => $procedure->id,
            'sale_document_id' => $attributes['sale_document_id'] ?? null,
            'name_snapshot' => $procedure->name,
            'default_price' => $procedure->default_price,
            'price' => 50,
            'quantity' => 1,
            'total' => 50,
            'currency_type_id' => 'PEN',
            'status' => $attributes['status'] ?? 'pending',
            'billed_at' => $attributes['billed_at'] ?? null,
            'charged_at' => $attributes['charged_at'] ?? now(),
        ]);
    }

    /** Comprobante de venta con el estado indicado (3 = anulado). */
    private function saleDocumentFixture(int $status = 0): int
    {
        return DB::table('sale_documents')->insertGetId([
            'status' => $status,
            'invoice_serie' => 'B001',
            'number' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
