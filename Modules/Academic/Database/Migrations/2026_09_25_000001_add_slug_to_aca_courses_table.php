<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->string('slug', 300)->nullable()->after('description');
        });

        // Backfill: slug inicial derivado de la descripcion, garantizando unicidad.
        $courses = DB::table('aca_courses')->select('id', 'description')->orderBy('id')->get();
        $used = [];

        foreach ($courses as $course) {
            $base = \Illuminate\Support\Str::slug($course->description ?: 'curso-' . $course->id);
            if ($base === '') {
                $base = 'curso-' . $course->id;
            }

            $slug = $base;
            $i = 2;
            while (isset($used[$slug]) || DB::table('aca_courses')->where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }

            $used[$slug] = true;
            DB::table('aca_courses')->where('id', $course->id)->update(['slug' => $slug]);
        }

        Schema::table('aca_courses', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
