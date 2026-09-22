<?php

namespace App\Support;

use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MailAttachmentResolver
{
    /**
     * Crea un adjunto obligatorio desde una ruta de Storage o una ruta persistente.
     * Las rutas absolutas solo pueden vivir dentro de storage/app o public/storage.
     */
    public static function required(string $path, string $name, string $disk = 'public'): Attachment
    {
        return self::resolve($path, $name, $disk, true);
    }

    /**
     * Crea un adjunto opcional. Si no existe, se omite sin interrumpir el correo.
     */
    public static function optional(?string $path, ?string $name, string $disk = 'public'): ?Attachment
    {
        if (! $path || ! $name) {
            return null;
        }

        return self::resolve($path, $name, $disk, false);
    }

    private static function resolve(string $path, string $name, string $disk, bool $required): ?Attachment
    {
        $name = self::safeName($name);

        if (self::isAbsolutePath($path)) {
            $realPath = realpath($path);

            if (! $realPath || ! is_file($realPath) || ! self::isAllowedAbsolutePath($realPath)) {
                if ($required) {
                    throw new RuntimeException("El adjunto requerido no existe o está fuera del almacenamiento permitido: {$name}");
                }

                return null;
            }

            return Attachment::fromPath($realPath)->as($name);
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            if ($required) {
                throw new RuntimeException("El adjunto requerido no existe en el disco {$disk}: {$path}");
            }

            return null;
        }

        return Attachment::fromStorageDisk($disk, $path)->as($name);
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }

    private static function isAllowedAbsolutePath(string $path): bool
    {
        $allowed = [
            realpath(storage_path('app')),
            realpath(public_path('storage')),
        ];

        foreach (array_filter($allowed) as $directory) {
            $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (str_starts_with($path, $directory)) {
                return true;
            }
        }

        return false;
    }

    private static function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: 'attachment';

        return substr($name, 0, 180);
    }
}
