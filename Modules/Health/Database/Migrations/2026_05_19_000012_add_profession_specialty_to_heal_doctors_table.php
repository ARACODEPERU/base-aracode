<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_doctors', 'profession')) {
                $table->string('profession', 120)->nullable()->after('doctor_code');
            }

            if (!Schema::hasColumn('heal_doctors', 'specialty')) {
                $table->string('specialty', 120)->nullable()->after('profession');
            }

            if (!Schema::hasColumn('heal_doctors', 'attention_service_type')) {
                $table->string('attention_service_type', 80)->default('general')->after('specialty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_doctors', function (Blueprint $table) {
            if (Schema::hasColumn('heal_doctors', 'attention_service_type')) {
                $table->dropColumn('attention_service_type');
            }

            if (Schema::hasColumn('heal_doctors', 'specialty')) {
                $table->dropColumn('specialty');
            }

            if (Schema::hasColumn('heal_doctors', 'profession')) {
                $table->dropColumn('profession');
            }
        });
    }
};
