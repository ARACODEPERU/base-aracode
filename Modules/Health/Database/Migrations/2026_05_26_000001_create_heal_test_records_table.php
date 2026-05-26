<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_test_records', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 80)->index();
            $table->string('table_name', 80)->index();
            $table->unsignedBigInteger('record_id')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['table_name', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_test_records');
    }
};
