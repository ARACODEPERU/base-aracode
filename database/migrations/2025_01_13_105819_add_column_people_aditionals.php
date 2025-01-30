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
        Schema::table('people', function (Blueprint $table) {
            $table->unsignedBigInteger('company_person_id')->nullable()->comment('se registra la empresa que pertenese');
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('profession', 100)->nullable();
            $table->string('company', 200)->nullable();
            $table->foreign('company_person_id', 'people_company_person_id_fk')->references('id')->on('people')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign('people_company_person_id_fk');
            $table->dropColumn('company');
            $table->dropColumn('profession');
            $table->dropColumn('industry');
            $table->dropColumn('company_person_id');
        });
    }
};
