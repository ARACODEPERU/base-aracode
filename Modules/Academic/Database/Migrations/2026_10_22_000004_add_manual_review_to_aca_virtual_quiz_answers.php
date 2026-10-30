<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aca_virtual_quiz_answers')) {
            return;
        }

        Schema::table('aca_virtual_quiz_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('aca_virtual_quiz_answers', 'manually_graded')) {
                // El docente revisó esta respuesta (típicamente una abierta).
                $table->boolean('manually_graded')->default(false)->after('is_correct');
            }

            if (! Schema::hasColumn('aca_virtual_quiz_answers', 'graded_by_person_id')) {
                $table->foreignId('graded_by_person_id')->nullable()->after('manually_graded')
                    ->constrained('people')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('aca_virtual_quiz_answers')) {
            return;
        }

        Schema::table('aca_virtual_quiz_answers', function (Blueprint $table) {
            if (Schema::hasColumn('aca_virtual_quiz_answers', 'graded_by_person_id')) {
                $table->dropConstrainedForeignId('graded_by_person_id');
            }

            if (Schema::hasColumn('aca_virtual_quiz_answers', 'manually_graded')) {
                $table->dropColumn('manually_graded');
            }
        });
    }
};
