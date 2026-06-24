<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->enum('content_type', ['description', 'table'])->default('description')->after('text_align_description');
        });
    }

    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('content_type');
        });
    }
};
