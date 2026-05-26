<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_doctors', 'signature_pin_hash')) {
                $table->string('signature_pin_hash')->nullable()->after('doctor_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_doctors', function (Blueprint $table) {
            if (Schema::hasColumn('heal_doctors', 'signature_pin_hash')) {
                $table->dropColumn('signature_pin_hash');
            }
        });
    }
};
