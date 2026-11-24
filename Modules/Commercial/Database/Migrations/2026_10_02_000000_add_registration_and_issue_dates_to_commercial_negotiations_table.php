<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->timestamp('client_confirmed_at')->nullable()->after('verified_at')->comment('Fecha en que el cliente registro sus datos y envio la evidencia');
            $table->date('invoice_issue_date')->nullable()->after('client_confirmed_at')->comment('Fecha de emision del comprobante elegida por el administrador');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->dropColumn(['invoice_issue_date', 'client_confirmed_at']);
        });
    }
};
