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
     *   P000027 → llms.txt  (ya correcto en la BD)
     *   P000028 → Plantilla A4 para impresion (movida desde P000026)
     *   P000029 → TPV activar/desactivar (sin cambios)
     *   P000030 → Modulos activos (movida desde P000027)
     *   P000031 → Pagina web principal (movida desde P000028)
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
        $oldA4 = Parameter::where('parameter_code', 'P000026')
            ->where('description', 'Plantilla A4 para impresión de documentos de ventas')
            ->first();

        if ($oldA4) {
            $oldA4->update(['parameter_code' => 'P000028']);
        }

        // --- Mover Modulos activos de P000027 → P000030 ---
        $oldModules = Parameter::where('parameter_code', 'P000027')
            ->where('description', 'Modulos activos')
            ->first();

        if ($oldModules) {
            $oldModules->update(['parameter_code' => 'P000030']);
        }

        // --- Mover Pagina web de P000028 → P000031 ---
        // Solo si aun es Pagina web principal (podria haber sido
        // reemplazado por Plantilla A4 movida arriba en el paso 1)
        $oldWebPage = Parameter::where('parameter_code', 'P000028')
            ->where('description', 'Pagina web principal')
            ->first();

        if ($oldWebPage) {
            $oldWebPage->update(['parameter_code' => 'P000031']);
        }

        // ============================================================
        // PASO 2: Ahora crear robots.txt y llms.txt en los codigos
        //         que quedaron libres.
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

        // Revertir movimientos (en orden inverso para evitar colisiones)
        Parameter::where('parameter_code', 'P000031')
            ->where('description', 'Pagina web principal')
            ->update(['parameter_code' => 'P000028']);

        Parameter::where('parameter_code', 'P000030')
            ->where('description', 'Modulos activos')
            ->update(['parameter_code' => 'P000027']);

        Parameter::where('parameter_code', 'P000028')
            ->where('description', 'Plantilla A4 para impresión de documentos de ventas')
            ->update(['parameter_code' => 'P000026']);

        Parameter::where('parameter_code', 'P000027')->delete();
        Parameter::where('parameter_code', 'P000026')->delete();
    }
};
