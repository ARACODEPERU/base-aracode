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
        Schema::create('complaints_book_attentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complaints_book_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->nullable();
            $table->date('date_start');
            $table->date('date_end');
            $table->enum('priority',['Baja', 'Media', 'Alta', 'Crítica'])->nullable();
            $table->text('internal_management_notes');
            $table->text('consumer_response');
            $table->string('attached_file')->nullable();
            $table->timestamps();
            $table->foreign('complaints_book_id','complaints_book_id_fk')->references('id')->on('complaints_books')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints_book_attentions');
    }
};
