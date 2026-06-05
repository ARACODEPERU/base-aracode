<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_errors', function (Blueprint $table) {
            $table->id();
            $table->text('message')->comment('Mensaje de error / exception');
            $table->timestamp('created_at')->useCurrent()->comment('Momento del registro del error');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_errors');
    }
};
