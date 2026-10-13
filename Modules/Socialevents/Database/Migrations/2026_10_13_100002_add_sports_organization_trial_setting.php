<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialevent_organization_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('trial_days')->default(30);
            $table->decimal('default_match_rate', 10, 2)->default(5);
            $table->timestamps();
        });

        DB::table('socialevent_organization_settings')->insert([
            'trial_days' => 30,
            'default_match_rate' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_settings');
    }
};
