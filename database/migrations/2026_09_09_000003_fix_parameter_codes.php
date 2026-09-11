<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reasigna los codigos de parametro para resolver la colision entre
     * la migracion seed (2023) y la migracion de robots/llms (2026).
     *
     * Mapeo:
     *   P000026 → robots.txt (nuevo, estaba ocupado por Plantilla A4)
     *   P000027 → llms.txt  (nuevo, estaba ocupado por Modulos activos)
     *   P000028 → Plantilla A4 para impresion (movida desde P000026)
     *   P000029 → TPV activar/desactivar (sin cambios)
     *   P000030 → Modulos activos (movida desde P000027)
     *   PW00001 → Pagina web principal (codigo nuevo estilo PW; 1 = Aracode Principal, 2 = Aracode torneos)
     *
     * Idempotente: solo ejecuta cambios si los codigos antiguos aun existen.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $publicPath = public_path();

        // ============================================================
        // PASO 1: Mover parametros existentes PRIMERO para liberar
        //         los codigos que robots.txt y llms.txt necesitan.
        // ============================================================

        // --- Mover Plantilla A4 de P000026 → P000028 ---
        // (solo si la fila en P000028 no existe ya: idempotente)
        $a4Exists = Parameter::where('parameter_code', 'P000028')->exists();

        $oldA4 = Parameter::where('parameter_code', 'P000026')
            ->where('description', 'Plantilla A4 para impresión de documentos de ventas')
            ->first();

        if ($oldA4 && !$a4Exists) {
            $oldA4->update(['parameter_code' => 'P000028']);
        }

        // --- Mover Modulos activos de P000027 → P000030 ---
        // (solo si la fila en P000030 no existe ya: idempotente)
        $modulesExists = Parameter::where('parameter_code', 'P000030')->exists();

        $oldModules = Parameter::where('parameter_code', 'P000027')
            ->where('description', 'Modulos activos')
            ->first();

        if ($oldModules && !$modulesExists) {
            $oldModules->update(['parameter_code' => 'P000030']);
        }

        // ============================================================
        // PASO 2: Asegurar "Pagina web principal" en su codigo nuevo
        //         PW00001. Si existe en otro codigo, se mueve; si no
        //         existe, se crea. Nunca se toca su valor actual.
        // ============================================================

        $webPage = Parameter::where('description', 'Pagina web principal')->first();

        if ($webPage) {
            if ($webPage->parameter_code !== 'PW00001') {
                // Puede haber una colision si PW00001 esta ocupado por otra cosa
                $occupant = Parameter::where('parameter_code', 'PW00001')->first();

                if ($occupant && $occupant->id !== $webPage->id) {
                    $occupant->delete();
                }

                $webPage->update(['parameter_code' => 'PW00001']);
            }
        } else {
            Parameter::create([
                'parameter_code'   => 'PW00001',
                'description'      => 'Pagina web principal',
                'control_type'     => 'sa',
                'json_query_data'  => '[{"value": "1","label": "Aracode Principal"},{"value": "2","label": "Aracode torneos"}]',
                'value_default'    => '1',
            ]);
        }

        // ============================================================
        // PASO 3: Crear robots.txt y llms.txt en los codigos que
        //         quedaron libres.
        // ============================================================

        // --- Crear P000026: robots.txt ---
        if (!Parameter::where('parameter_code', 'P000026')->exists()) {
            $robotsPath = $publicPath . '/robots.txt';
            $robotsContent = null;

            if (File::exists($robotsPath)) {
                $robotsContent = File::get($robotsPath);
            } else {
                $robotsContent = "User-agent: *\nAllow: /\n\nSitemap: https://academy.globalcpaperu.com/sitemap.xml\n";
                File::put($robotsPath, $robotsContent);
            }

            Parameter::create([
                'parameter_code'   => 'P000026',
                'description'     => 'Contenido del archivo robots.txt (ubicacion: public/robots.txt)',
                'control_type'    => 'tx',
                'json_query_data' => null,
                'value_default'   => $robotsContent,
            ]);
        }

        // --- Crear P000027: llms.txt ---
        if (!Parameter::where('parameter_code', 'P000027')->exists()) {
            $llmsPath = $publicPath . '/llms.txt';
            $llmsContent = null;

            if (File::exists($llmsPath)) {
                $llmsContent = File::get($llmsPath);
            } else {
                $llmsContent = "# Sitio Web\n\nDescripcion del sitio para asistentes de IA.\n";
                File::put($llmsPath, $llmsContent);
            }

            Parameter::create([
                'parameter_code'   => 'P000027',
                'description'     => 'Contenido del archivo llms.txt (ubicacion: public/llms.txt)',
                'control_type'    => 'tx',
                'json_query_data' => null,
                'value_default'   => $llmsContent,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        // --- Revertir pagina web: solo si sigue en PW00001 ---
        // (si ya fue editada o recreada con otro contenido, no se toca)
        $webPage = Parameter::where('parameter_code', 'PW00001')
            ->where('description', 'Pagina web principal')
            ->first();

        if ($webPage) {
            // Liberar P000028 por si el paso inverso de A4 necesita el codigo
            Parameter::where('parameter_code', 'P000028')
                ->where('description', 'Plantilla A4 para impresión de documentos de ventas')
                ->delete();

            $webPage->update(['parameter_code' => 'P000028']);
        }

        // --- Revertir Modulos activos de P000030 → P000027 ---
        Parameter::where('parameter_code', 'P000027')
            ->where('description', 'Contenido del archivo llms.txt (ubicacion: public/llms.txt)')
            ->delete();

        Parameter::where('parameter_code', 'P000030')
            ->where('description', 'Modulos activos')
            ->update(['parameter_code' => 'P000027']);

        // --- Revertir Plantilla A4 de P000028 → P000026 ---
        Parameter::where('parameter_code', 'P000026')
            ->where('description', 'Contenido del archivo robots.txt (ubicacion: public/robots.txt)')
            ->delete();

        Parameter::where('parameter_code', 'P000028')
            ->where('description', 'Plantilla A4 para impresión de documentos de ventas')
            ->update(['parameter_code' => 'P000026']);
    }
};
