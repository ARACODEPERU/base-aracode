<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aca_certificates_module_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('certificate_id')->unique();
            $table->string('fontfamily_module_description', 50)->nullable();
            $table->string('font_align_module_description', 10)->nullable();
            $table->string('font_vertical_module_description', 10)->nullable();
            $table->smallInteger('position_module_description_x')->nullable();
            $table->smallInteger('position_module_description_y')->nullable();
            $table->tinyInteger('font_size_module_description')->nullable();
            $table->smallInteger('max_width_module_description')->nullable();
            $table->string('text_align_module_description', 10)->nullable();
            $table->string('color_module_description', 10)->nullable();
            $table->boolean('visible_module_description')->default(true);
            $table->timestamps();
            
            $table->foreign('certificate_id')
                ->references('id')
                ->on('aca_certificates_parameters')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aca_certificates_module_config');
    }
};