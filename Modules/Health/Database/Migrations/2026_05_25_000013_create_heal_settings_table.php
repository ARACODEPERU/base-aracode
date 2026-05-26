<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_settings', function (Blueprint $table) {
            $table->id();
            $table->string('establishment_name', 255)->nullable()->default('Mi Consultorio');
            $table->string('establishment_logo', 500)->nullable();
            $table->string('representative', 255)->nullable();
            $table->string('notification_email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->json('working_hours')->nullable()->comment('Array of {start, end} time ranges e.g. [{"start":"08:00","end":"14:00"},{"start":"16:00","end":"20:00"}]');
            $table->json('settings')->nullable()->comment('Extra settings for future extensibility');
            $table->timestamps();
        });

        // Insert default settings row
        DB::table('heal_settings')->insert([
            'establishment_name' => 'Mi Consultorio',
            'establishment_logo' => null,
            'representative' => null,
            'notification_email' => null,
            'phone' => null,
            'address' => null,
            'working_hours' => json_encode([
                ['start' => '08:00', 'end' => '14:00'],
                ['start' => '16:00', 'end' => '20:00'],
            ]),
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_settings');
    }
};
