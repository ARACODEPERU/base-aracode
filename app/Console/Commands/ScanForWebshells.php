<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScanForWebshells extends Command
{
    protected $signature = 'security:scan-webshells {--delete : Eliminar los archivos sospechosos encontrados}';

    protected $description = 'Detecta webshells (archivos .php maliciosos) en public/ y storage/app/public.';

    /**
     * Extensiones ejecutables típicas que un atacante intenta subir.
     */
    private array $dangerousExtensions = ['php', 'phtml', 'php5', 'php7', 'php8', 'phar', 'cgi', 'pl', 'sh'];

    /**
     * Patrones de código típicos de un webshell.
     */
    private array $suspiciousPatterns = [
        '/\b(eval|assert|system|shell_exec|passthru|proc_open|popen|exec)\s*\(/i',
        '/base64_decode\s*\(/i',
        '/\b(eval|assert)\s*\(\s*base64_decode/i',
        '/\$\b(_GET|_POST|_REQUEST|_COOKIE)\b\s*\[/',
        '/phpinfo\s*\(/i',
        '/\b(WSO|b374k|c99|r57|weevely|FilesMan|c0ntinuum)\b/i',
    ];

    public function handle(): int
    {
        $candidates = [];

        // 1. En la raíz de public/ solo debe existir index.php.
        foreach ($this->phpFilesInDirectory(public_path(), false) as $file) {
            if (realpath($file) === realpath(public_path('index.php'))) {
                continue;
            }
            $candidates[$file] = $this->isContentSuspicious($file);
        }

        // 2. En storage/app/public no debería existir NINGÚN archivo ejecutable.
        foreach ($this->phpFilesInDirectory(storage_path('app/public'), true) as $file) {
            $candidates[$file] = true;
        }

        if (empty($candidates)) {
            $this->info('No se encontraron webshells sospechosos.');

            return self::SUCCESS;
        }

        $this->warn(count($candidates).' archivo(s) sospechoso(s) encontrado(s):');

        foreach ($candidates as $file => $suspiciousContent) {
            $this->line('  - '.$file.($suspiciousContent ? '  [contenido sospechoso]' : ''));

            if ($this->option('delete')) {
                if (@unlink($file)) {
                    $this->warn('    Eliminado.');
                } else {
                    $this->error('    No se pudo eliminar.');
                }
            }
        }

        if (! $this->option('delete')) {
            $this->comment('Ejecuta de nuevo con --delete para eliminarlos.');
        }

        return self::SUCCESS;
    }

    /**
     * Devuelve los archivos con extensión ejecutable dentro de un directorio.
     */
    private function phpFilesInDirectory(string $dir, bool $recursive): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS))
            : new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (in_array(strtolower($file->getExtension()), $this->dangerousExtensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Comprueba si el contenido de un archivo contiene patrones de webshell.
     * Sirve para detectar un index.php u otro archivo que haya sido "backdoorizado".
     */
    private function isContentSuspicious(string $file): bool
    {
        $content = @file_get_contents($file);

        if ($content === false || trim($content) === '') {
            return true;
        }

        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
