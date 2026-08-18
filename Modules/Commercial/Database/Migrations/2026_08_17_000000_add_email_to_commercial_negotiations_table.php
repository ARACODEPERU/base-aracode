<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('contact_detail')->comment('Correo del futuro cliente para enviarle el enlace de la cotizacion');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
