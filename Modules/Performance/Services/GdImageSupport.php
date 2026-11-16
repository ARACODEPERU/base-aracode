<?php

namespace Modules\Performance\Services;

/**
 * Apertura y guardado con GD compartido por el redimensionador y el
 * compresor. Centraliza qué formatos se pueden procesar y con qué calidad.
 */
class GdImageSupport
{
    /**
     * Formatos que GD puede abrir en esta instalación.
     *
     * @return array<int, string>
     */
    public static function extensions(): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (function_exists('imagecreatefromwebp')) {
            $extensions[] = 'webp';
        }

        return $extensions;
    }

    /**
     * @return resource|\GdImage|null
     */
    public static function open(string $absolute, string $extension)
    {
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolute) ?: null,
            'png'         => @imagecreatefrompng($absolute) ?: null,
            'webp'        => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($absolute) ?: null) : null,
            'gif'         => @imagecreatefromgif($absolute) ?: null,
            default       => null,
        };
    }

    /**
     * @param  resource|\GdImage  $image
     */
    public static function save($image, string $path, string $extension, int $quality): bool
    {
        $quality = min(100, max(30, $quality));

        return match ($extension) {
            'jpg', 'jpeg' => @imagejpeg($image, $path, $quality),
            'png'         => @imagepng($image, $path, (int) round((100 - $quality) / 100 * 9)),
            'webp'        => function_exists('imagewebp') ? @imagewebp($image, $path, $quality) : false,
            'gif'         => @imagegif($image, $path),
            default       => false,
        };
    }

    /**
     * Copia con transparencia cuando el formato la admite.
     *
     * @param  resource|\GdImage  $source
     * @return resource|\GdImage
     */
    public static function canvas(int $width, int $height, string $extension)
    {
        $canvas = imagecreatetruecolor($width, $height);

        if (in_array($extension, ['png', 'webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        }

        return $canvas;
    }
}
