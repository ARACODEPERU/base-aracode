<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('even_locals', 'socialevent_organization_id')) {
            Schema::table('even_locals', function (Blueprint $table) {
                $table->foreignId('socialevent_organization_id')->nullable()->after('created_by')
                    ->constrained('socialevent_organizations')->nullOnDelete();
            });
        }

        // Los locales creados por un organizador pasan a la organización de su creador.
        DB::table('even_locals')->whereNotNull('created_by')->orderBy('id')->get()->each(function ($local) {
            $organizacion = DB::table('users')->where('id', $local->created_by)->value('socialevent_organization_id');

            if ($organizacion) {
                DB::table('even_locals')->where('id', $local->id)->update(['socialevent_organization_id' => $organizacion]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('even_locals', 'socialevent_organization_id')) {
            Schema::table('even_locals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('socialevent_organization_id');
            });
        }
    }
};
