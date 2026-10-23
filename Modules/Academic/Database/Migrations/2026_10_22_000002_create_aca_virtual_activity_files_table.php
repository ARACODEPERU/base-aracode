<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_activity_files')) {
            return;
        }

        Schema::create('aca_virtual_activity_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('aca_virtual_activities')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedInteger('size_kb')->nullable();
            $table->timestamps();

            $table->index('activity_id', 'ix_aca_virtual_activity_file_activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_activity_files');
    }
};
