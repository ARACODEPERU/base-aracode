<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de la boleta emitida a nombre de una tercera persona:
     * el cliente confirma la negociacion pero pide que la boleta
     * salga a nombre de otra persona (por ejemplo un familiar).
     */
    public function up(): void
    {
        Schema::table('commercial_negotiation_invoices', function (Blueprint $table) {
            $table->string('boleta_documento_tipo', 10)->nullable()->after('invoice_type');
            $table->string('boleta_numero', 20)->nullable()->after('boleta_documento_tipo');
            $table->string('boleta_nombre')->nullable()->after('boleta_numero');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiation_invoices', function (Blueprint $table) {
            $table->dropColumn(['boleta_documento_tipo', 'boleta_numero', 'boleta_nombre']);
        });
    }
};
