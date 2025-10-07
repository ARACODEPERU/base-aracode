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
        Schema::create('cms_advertisings', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->text('script_code')->nullable()->comment('codigo');
            $table->string('image')->nullable();
            $table->integer('views_total')->comment('total de visitas');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_advertisings');
    }
};
