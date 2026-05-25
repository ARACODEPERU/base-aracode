<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_attentions', 'appointment_id')) {
                $table->unsignedBigInteger('appointment_id')->nullable()->after('user_id');
                $table->foreign('appointment_id')->references('id')->on('dent_appointments')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (Schema::hasColumn('heal_attentions', 'appointment_id')) {
                $table->dropForeign(['appointment_id']);
                $table->dropColumn('appointment_id');
            }
        });
    }
};
