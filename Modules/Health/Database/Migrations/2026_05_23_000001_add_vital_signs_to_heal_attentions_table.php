<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_attentions', 'blood_pressure_systolic')) {
                $table->unsignedSmallInteger('blood_pressure_systolic')->nullable()->after('observations');
            }

            if (!Schema::hasColumn('heal_attentions', 'blood_pressure_diastolic')) {
                $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable()->after('blood_pressure_systolic');
            }

            if (!Schema::hasColumn('heal_attentions', 'heart_rate')) {
                $table->unsignedSmallInteger('heart_rate')->nullable()->after('blood_pressure_diastolic');
            }

            if (!Schema::hasColumn('heal_attentions', 'respiratory_rate')) {
                $table->unsignedSmallInteger('respiratory_rate')->nullable()->after('heart_rate');
            }

            if (!Schema::hasColumn('heal_attentions', 'height')) {
                $table->decimal('height', 5, 2)->nullable()->after('respiratory_rate');
            }

            if (!Schema::hasColumn('heal_attentions', 'weight')) {
                $table->decimal('weight', 5, 2)->nullable()->after('height');
            }

            if (!Schema::hasColumn('heal_attentions', 'body_mass_index')) {
                $table->decimal('body_mass_index', 5, 2)->nullable()->after('weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            foreach ([
                'body_mass_index',
                'weight',
                'height',
                'respiratory_rate',
                'heart_rate',
                'blood_pressure_diastolic',
                'blood_pressure_systolic',
            ] as $column) {
                if (Schema::hasColumn('heal_attentions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
