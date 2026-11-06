<?php

namespace App\Helpers\Invoice\Sunat;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Servicio centralizado de archivos SUNAT: XML firmado y CDR.
 *
 * - Guarda el XML firmado y el ZIP devuelto por SUNAT en el disco publico de
 *   Laravel (storage/app/public/invoice por defecto).
 * - Descomprime el CDR (ApplicationResponse) y deja el XML suelto junto al ZIP,
 *   para que el usuario y el contador puedan descargarlo sin descomprimir.
 * - Devuelve rutas absolutas para seguir siendo compatible con los registros
 *   historicos (las columnas xml/cdr ya guardan rutas absolutas) y con
 *   `response()->download($path)`.
 */
class SunatFileStorage
{
    /** Prefijo que SUNAT usa para las constancias de recepcion. */
    public const CDR_PREFIX = 'R-';

    private const TAG_CODE = 'cbc:ResponseCode';

    private const TAG_DESCRIPTION = 'cbc:Description';

    private const TAG_NOTE = 'cbc:Note';

    private ?Filesystem $disk = null;

    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?: $this->configuredRoot();
    }

    public function root(): string
    {
        return $this->root;
    }

    public function disk(): Filesystem
    {
        return $this->disk ??= Storage::build([
            'driver' => 'local',
            'root' => $this->root,
            'throw' => false,
        ]);
    }

    /**
     * Guarda el XML firmado del comprobante. Devuelve la ruta absoluta o null.
     */
    public function storeXml(string $name, ?string $xml): ?string
    {
        if ($xml === null || trim($xml) === '') {
            return null;
        }

        return $this->put($this->baseName($name).'.xml', $xml);
    }

    /**
     * Guarda el ZIP del CDR, extrae el XML que contiene y lo deja accesible.
     *
     * @return array{zip: string|null, xml: string|null, code: string|null, description: string|null, notes: array<int, string>}
     */
    public function storeCdr(string $name, ?string $zip): array
    {
        $empty = ['zip' => null, 'xml' => null, 'code' => null, 'description' => null, 'notes' => []];

        if ($zip === null || $zip === '') {
            return $empty;
        }

        $base = $this->baseName($name);
        $zipPath = $this->put(self::CDR_PREFIX.$base.'.zip', $zip);

        if ($zipPath === null) {
            return $empty;
        }

        $contents = $this->cdrContents($zipPath);
        $xmlPath = null;

        if ($contents !== null && $contents['xml'] !== null) {
            $xmlPath = $this->put(self::CDR_PREFIX.$base.'.xml', $contents['xml']);
        }

        return [
            'zip' => $zipPath,
            'xml' => $xmlPath,
            'code' => $contents['code'] ?? null,
            'description' => $contents['description'] ?? null,
            'notes' => $contents['notes'] ?? [],
        ];
    }

    /**
     * Abre un CDR (ZIP) y extrae el XML, el codigo y la descripcion de SUNAT.
     *
     * @return array{xml: string|null, code: string|null, description: string|null, notes: array<int, string>}|null
     */
    public function cdrContents(?string $path): ?array
    {
        $absolute = $this->absolutePath($path);

        if ($absolute === null || ! class_exists(ZipArchive::class)) {
            return null;
        }

        $archive = new ZipArchive;

        if ($archive->open($absolute) !== true) {
            return null;
        }

        $xml = null;

        try {
            for ($i = 0; $i < $archive->numFiles; $i++) {
                $entry = (string) $archive->getNameIndex($i);

                if (strtolower(pathinfo($entry, PATHINFO_EXTENSION)) !== 'xml') {
                    continue;
                }

                $content = $archive->getFromIndex($i);

                if (is_string($content) && trim($content) !== '') {
                    $xml = $content;
                    break;
                }
            }
        } finally {
            $archive->close();
        }

        if ($xml === null) {
            return null;
        }

        return [
            'xml' => $xml,
            'code' => $this->tag($xml, self::TAG_CODE),
            'description' => $this->tag($xml, self::TAG_DESCRIPTION),
            'notes' => $this->tags($xml, self::TAG_NOTE),
        ];
    }

    /**
     * Ruta del XML extraido del CDR que acompana a un ZIP ya guardado.
     */
    public function cdrXmlPath(?string $cdrZipPath): ?string
    {
        $absolute = $this->absolutePath($cdrZipPath);

        if ($absolute === null) {
            return null;
        }

        $candidate = (string) preg_replace('/\.zip$/i', '.xml', $absolute);

        return is_file($candidate) ? $candidate : null;
    }

    /**
     * Resuelve una ruta guardada en base de datos (absoluta historica o
     * relativa al disco de SUNAT) a una ruta absoluta existente.
     */
    public function absolutePath(?string $path): ?string
    {
        $path = $path === null ? '' : trim($path);

        if ($path === '') {
            return null;
        }

        // Registros historicos: ya guardan la ruta absoluta.
        if (is_file($path)) {
            return $path;
        }

        try {
            if ($this->disk()->exists($path)) {
                return $this->absolute($path);
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    public function exists(?string $path): bool
    {
        return $this->absolutePath($path) !== null;
    }

    public function read(?string $path): ?string
    {
        $absolute = $this->absolutePath($path);

        if ($absolute === null) {
            return null;
        }

        $content = file_get_contents($absolute);

        return $content === false ? null : $content;
    }

    /**
     * Normaliza el nombre de archivo: quita extension y prefijo R- duplicado.
     */
    public function baseName(string $name): string
    {
        $name = basename(trim($name));
        $name = (string) preg_replace('/\.(xml|zip|pdf)$/i', '', $name);
        $name = (string) preg_replace('/^R-/i', '', $name);

        return $name !== '' ? $name : 'sunat-'.date('YmdHis');
    }

    private function put(string $filename, string $content): ?string
    {
        try {
            if (! $this->disk()->put($filename, $content)) {
                return null;
            }

            return $this->absolute($filename);
        } catch (Throwable $e) {
            Log::warning('SUNAT: no se pudo guardar el archivo '.$filename, [
                'error' => mb_substr($e->getMessage(), 0, 300),
            ]);

            return null;
        }
    }

    private function absolute(string $relative): string
    {
        return $this->root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relative, '/\\'));
    }

    private function configuredRoot(): string
    {
        try {
            $configured = config('filesystems.sunat_path');
        } catch (Throwable $e) {
            $configured = null;
        }

        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        return public_path('storage'.DIRECTORY_SEPARATOR.'invoice');
    }

    private function tag(string $xml, string $tag): ?string
    {
        $quoted = preg_quote($tag, '/');

        if (! preg_match('/<'.$quoted.'[^>]*>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*<\/'.$quoted.'>/s', $xml, $matches)) {
            return null;
        }

        $value = trim($matches[1]);

        return $value === '' ? null : html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return array<int, string>
     */
    private function tags(string $xml, string $tag): array
    {
        $quoted = preg_quote($tag, '/');

        if (! preg_match_all('/<'.$quoted.'[^>]*>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*<\/'.$quoted.'>/s', $xml, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($value) => trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8')),
            $matches[1]
        ), fn ($value) => $value !== ''));
    }
}
