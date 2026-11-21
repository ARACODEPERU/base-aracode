<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los primeros textos creados por MCP se guardaron con is_file = 0, el mismo
 * código del frame de video. Se pasan a is_file = 6 solo los que no contienen
 * un iframe; los videos reales conservan su tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('aca_contents')) {
            return;
        }

        DB::table('aca_contents')
            ->where('is_file', 0)
            ->whereRaw('LOWER(content) NOT LIKE ?', ['%<iframe%'])
            ->whereRaw('LOWER(content) NOT LIKE ?', ['%&lt;iframe%'])
            ->update(['is_file' => 6]);
    }

    public function down(): void
    {
        // No se revierte: el tipo 6 es el tipo correcto para estos textos y
        // volver a 0 los mostraría otra vez como videos.
    }
};
