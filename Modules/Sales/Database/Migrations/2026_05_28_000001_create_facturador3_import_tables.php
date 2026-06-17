<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturador3_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phase', 50)->default('pending');
            $table->string('status', 50)->default('pending');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedBigInteger('processed_count')->default(0);
            $table->unsignedBigInteger('total_count')->default(0);
            $table->unsignedInteger('chunk_size')->default(1000);
            $table->unsignedBigInteger('last_processed_id')->default(0);
            $table->json('meta')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'phase']);
        });

        Schema::create('facturador3_import_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('f3_item_id')->unique();
            $table->unsignedBigInteger('product_id');
            $table->string('interne', 100);
            $table->boolean('is_product')->default(true);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('interne');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturador3_import_maps');
        Schema::dropIfExists('facturador3_import_jobs');
    }
};
