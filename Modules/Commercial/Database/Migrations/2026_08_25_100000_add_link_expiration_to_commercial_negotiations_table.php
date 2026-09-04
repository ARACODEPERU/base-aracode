<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->integer('link_days')->nullable()->after('single_payment_days')->comment('Dias de vigencia del enlace publico');
            $table->timestamp('link_expires_at')->nullable()->after('link_days')->comment('Fecha/hora de expiracion del enlace publico');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->dropColumn(['link_expires_at', 'link_days']);
        });
    }
};
