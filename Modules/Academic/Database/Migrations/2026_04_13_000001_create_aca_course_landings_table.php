<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_course_landings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->unique();
            $table->string('url_slug', 500)->nullable();
            $table->date('banner_start_date')->nullable();
            $table->date('banner_end_date')->nullable();
            $table->integer('banner_duration')->nullable()->comment('Duración en días');
            $table->enum('banner_language', ['es', 'en', 'zh'])->default('es')->comment('es=Español, en=Inglés, zh=Mandarín');
            $table->boolean('is_published')->default(false);
            $table->json('professional_section')->nullable()->after('banner_language');
            $table->json('staff_section')->nullable()->after('professional_section');
            $table->json('results_section')->nullable()->after('staff_section');
            $table->json('testimonials_section')->nullable()->after('results_section');
            $table->json('study_plan_section')->nullable()->after('testimonials_section');
            $table->json('problem_section')->nullable()->after('study_plan_section');
            $table->json('investment_section')->nullable()->after('problem_section');
            $table->json('faq_section')->nullable()->after('investment_section');
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('aca_courses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_course_landings');
    }
};
