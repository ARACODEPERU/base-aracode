<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->foreignId('user_id_registers')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
        Schema::table('aca_students', function (Blueprint $table) {
            $table->foreignId('user_id_registers')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->dropForeign(['user_id_registers']);
            $table->dropColumn('user_id_registers');
        });
        Schema::table('aca_students', function (Blueprint $table) {
            $table->dropForeign(['user_id_registers']);
            $table->dropColumn('user_id_registers');
        });
    }
};
