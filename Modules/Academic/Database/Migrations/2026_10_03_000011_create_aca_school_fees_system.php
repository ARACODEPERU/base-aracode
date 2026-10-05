<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistema de tarifas y cobros de matricula:
     * - tipo de colegio (privado|nacional)
     * - conceptos de cobro configurables (fee_types)
     * - tarifas por año escolar con alcance jerarquico (fees)
     * - cronograma de mensualidades fijas (payment_schedules)
     * - cobros/pagos registrados (charges)
     *
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_schools') && ! Schema::hasColumn('aca_schools', 'type')) {
            Schema::table('aca_schools', function (Blueprint $table) {
                $table->string('type', 10)->default('privado')->comment('privado|nacional')->after('is_default');
            });
        }

        if (! Schema::hasTable('aca_school_fee_types')) {
            Schema::create('aca_school_fee_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 100);
                $table->boolean('is_recurring')->default(false)->comment('Mensualidad: genera cronograma de cuotas fijas');
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            DB::table('aca_school_fee_types')->insert([
                ['code' => 'matricula', 'name' => 'Matrícula', 'is_recurring' => false, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'mensualidad', 'name' => 'Mensualidad', 'is_recurring' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'auxiliar', 'name' => 'Auxiliar', 'is_recurring' => false, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'copias', 'name' => 'Copias y materiales de trabajo', 'is_recurring' => false, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'vigilancia', 'name' => 'Vigilancia del colegio', 'is_recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! Schema::hasTable('aca_school_fees')) {
            Schema::create('aca_school_fees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
                $table->foreignId('fee_type_id')->constrained('aca_school_fee_types')->cascadeOnDelete();
                $table->unsignedBigInteger('level_id')->nullable();
                $table->unsignedBigInteger('grade_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->foreign('level_id')->references('id')->on('aca_school_levels')->nullOnDelete();
                $table->foreign('grade_id')->references('id')->on('aca_school_grades')->nullOnDelete();
                $table->foreign('section_id')->references('id')->on('aca_school_sections')->nullOnDelete();
                $table->index(['year_id', 'fee_type_id']);
            });
        }

        if (! Schema::hasTable('aca_school_charges')) {
            Schema::create('aca_school_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->constrained('aca_school_enrollments')->cascadeOnDelete();
                $table->foreignId('fee_type_id')->nullable()->constrained('aca_school_fee_types')->nullOnDelete();
                $table->string('description', 200)->comment('Snapshot del concepto cobrado');
                $table->decimal('amount', 10, 2);
                $table->string('status', 10)->default('pagado')->comment('pendiente|pagado|anulado');
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method', 20)->nullable()->comment('efectivo|transferencia|yape|plin|otro');
                $table->string('reference', 50)->nullable()->comment('Nro de recibo/operacion');
                $table->unsignedBigInteger('payment_schedule_id')->nullable()->comment('Cuota de mensualidad origen');
                $table->unsignedBigInteger('created_by')->nullable()->comment('User id que registro el cobro');
                $table->timestamps();

                $table->index(['enrollment_id', 'fee_type_id']);
            });
        }

        if (! Schema::hasTable('aca_school_payment_schedules')) {
            Schema::create('aca_school_payment_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('aca_school_enrollments')->cascadeOnDelete();
                $table->foreignId('fee_type_id')->constrained('aca_school_fee_types')->cascadeOnDelete();
                $table->unsignedTinyInteger('installment')->comment('1 = marzo ... 10 = diciembre');
                $table->date('due_date');
                $table->decimal('amount', 10, 2);
                $table->unsignedBigInteger('charge_id')->nullable()->comment('Cobro con el que se pago la cuota');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->foreign('charge_id')->references('id')->on('aca_school_charges')->nullOnDelete();
                $table->unique(['enrollment_id', 'installment'], 'aca_school_payment_schedules_unique');
            });
        }

        // FK diferida: charges.payment_schedule_id -> payment_schedules (tabla creada arriba)
        if (Schema::hasTable('aca_school_charges') && Schema::hasTable('aca_school_payment_schedules')) {
            $fkExists = (bool) DB::selectOne(
                "SELECT COUNT(*) AS total FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'aca_school_charges'
                   AND COLUMN_NAME = 'payment_schedule_id'
                   AND REFERENCED_TABLE_NAME = 'aca_school_payment_schedules'"
            )->total;

            if (! $fkExists) {
                Schema::table('aca_school_charges', function (Blueprint $table) {
                    $table->foreign('payment_schedule_id')->references('id')->on('aca_school_payment_schedules')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // No se eliminan tablas (skill no-drop-tables); dejar todo intacto.
    }
};
