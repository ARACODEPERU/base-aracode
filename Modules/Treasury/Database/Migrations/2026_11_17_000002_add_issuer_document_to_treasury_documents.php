<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento del emisor del cheque o letra (DNI o RUC).
 *
 * Permite guardar el documento con el que se consultó al emisor en RENIEC o
 * SUNAT y mostrarlo junto a su nombre. Es aditiva e idempotente: los
 * documentos que ya existen quedan con el documento vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('treasury_documents')) {
            return;
        }

        Schema::table('treasury_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('treasury_documents', 'issuer_doc_type')) {
                $table->string('issuer_doc_type', 2)->nullable()->after('issuer')
                    ->comment('Tipo de documento del emisor: 1 DNI, 6 RUC');
            }

            if (! Schema::hasColumn('treasury_documents', 'issuer_doc_number')) {
                $table->string('issuer_doc_number', 15)->nullable()->after('issuer_doc_type')
                    ->comment('Número de DNI o RUC del emisor');
            }
        });
    }

    public function down(): void
    {
        // Aditiva: el documento del emisor es parte del historial y no se elimina.
    }
};
