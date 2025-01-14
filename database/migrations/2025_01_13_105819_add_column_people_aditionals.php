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
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('profession', 100)->nullable();
            $table->foreign('person_id', 'people_person_id_fk')->references('id')->on('people')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign('people_person_id_fk');
            $table->dropColumn('profession');
            $table->dropColumn('industry');
            $table->dropColumn('person_id');
        });
    }
};
