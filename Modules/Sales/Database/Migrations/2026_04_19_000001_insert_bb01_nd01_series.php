<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $existsBB01 = DB::table('series')
            ->where('description', 'BB01')
            ->where('document_type_id', 3)
            ->exists();

        if (!$existsBB01) {
            DB::table('series')->insert([
                'document_type_id' => 3,
                'description' => 'BB01',
                'number' => 1,
                'user_id' => 3,
                'local_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $existsND01 = DB::table('series')
            ->where('description', 'ND01')
            ->where('document_type_id', 3)
            ->exists();

        if (!$existsND01) {
            DB::table('series')->insert([
                'document_type_id' => 3,
                'description' => 'ND01',
                'number' => 1,
                'user_id' => 3,
                'local_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('series')->where('description', 'BB01')->where('document_type_id', 3)->delete();
        DB::table('series')->where('description', 'ND01')->where('document_type_id', 3)->delete();
    }
};
