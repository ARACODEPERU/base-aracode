<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->text('fontfamily_description')->nullable();
            $table->text('font_align_description')->nullable()->after('fontfamily_description');
            $table->text('font_vertical_align_description')->nullable()->after('font_align_description');
            $table->integer('position_description_x')->nullable()->after('font_vertical_align_description');
            $table->integer('position_description_y')->nullable()->after('position_description_x');
            $table->integer('font_size_description')->nullable()->after('position_description_y');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn([
                'fontfamily_description',
                'font_align_description',
                'font_vertical_align_description',
                'position_description_x',
                'position_description_y',
                'font_size_description',
            ]);
        });
    }
};
