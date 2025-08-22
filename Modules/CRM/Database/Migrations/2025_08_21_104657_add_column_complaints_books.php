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
        Schema::table('complaints_book_attentions', function (Blueprint $table) {
            $table->enum('means_communication', [
                'Mensaje de correo electrónico',
                'Mensaje Whatsapp',
                'Mensaje texto',
                'Llamada telefónica',
                'Video Llamada'
            ])
            ->default('Mensaje de correo electrónico')
            ->comment('como se comunico con el cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints_book_attentions', function (Blueprint $table) {
            $table->dropColumn('means_communication');
        });
    }
};
