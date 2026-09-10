<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Crea los parametros del sistema P000026 (robots.txt) y P000027 (llms.txt)
     * para gestionar los archivos de configuracion de IA y bots desde la UI.
     *
     * Idempotente: si los parametros ya existen, no se toca nada.
     * Si los archivos existen en public/, carga su contenido como valor por defecto.
     * Si no existen, crea los archivos con contenido por defecto.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $publicPath = public_path();

        // --- P000026: robots.txt ---
        if (!Parameter::where('parameter_code', 'P000026')->exists()) {
            $robotsPath = $publicPath . '/robots.txt';
            $robotsContent = null;

            if (File::exists($robotsPath)) {
                $robotsContent = File::get($robotsPath);
            } else {
                // Contenido por defecto si no existe el archivo
                $robotsContent = "User-agent: *\nAllow: /\n\nSitemap: https://academy.globalcpaperu.com/sitemap.xml\n";
                File::put($robotsPath, $robotsContent);
            }

            Parameter::create([
                'parameter_code'  => 'P000026',
                'description'    => 'Contenido del archivo robots.txt (ubicacion: public/robots.txt)',
                'control_type'   => 'tx',
                'json_query_data' => null,
                'value_default'  => $robotsContent,
            ]);
        }

        // --- P000027: llms.txt ---
        if (!Parameter::where('parameter_code', 'P000027')->exists()) {
            $llmsPath = $publicPath . '/llms.txt';
            $llmsContent = null;

            if (File::exists($llmsPath)) {
                $llmsContent = File::get($llmsPath);
            } else {
                // Contenido por defecto si no existe el archivo
                $llmsContent = "# Sitio Web\n\nDescripcion del sitio para asistentes de IA.\n";
                File::put($llmsPath, $llmsContent);
            }

            Parameter::create([
                'parameter_code'  => 'P000027',
                'description'    => 'Contenido del archivo llms.txt (ubicacion: public/llms.txt)',
                'control_type'   => 'tx',
                'json_query_data' => null,
                'value_default'  => $llmsContent,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', 'P000026')->delete();
        Parameter::where('parameter_code', 'P000027')->delete();
    }
};
