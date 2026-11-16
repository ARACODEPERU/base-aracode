<?php

namespace Modules\Performance\Services;

/**
 * Detecta si un GIF es animado.
 *
 * GD no conserva la animación al redimensionar, así que los GIF con más de un
 * fotograma se dejan fuera. Para saberlo se recorren los bloques del archivo
 * (cabecera, extensiones, descriptores de imagen y sub-bloques) sin cargarlo en
 * memoria como imagen.
 */
class GifInspector
{
    public static function isAnimated(string $path): bool
    {
        return self::frameCount($path) > 1;
    }

    public static function frameCount(string $path): int
    {
        $data = @file_get_contents($path);

        if ($data === false || strlen($data) < 13 || substr($data, 0, 3) !== 'GIF') {
            return 0;
        }

        $length = strlen($data);
        $packed = ord($data[10]);
        $position = 13;

        // Tabla de colores global.
        if ($packed & 0x80) {
            $position += 3 * (2 ** (($packed & 0x07) + 1));
        }

        $frames = 0;

        while ($position < $length) {
            $block = ord($data[$position]);

            if ($block === 0x3B) {
                break;
            }

            if ($block === 0x21) {
                $position = self::skipSubBlocks($data, $position + 2, $length);

                continue;
            }

            if ($block === 0x2C) {
                $frames++;
                $position += 10;

                $localPacked = ord($data[$position - 1]);

                if ($localPacked & 0x80) {
                    $position += 3 * (2 ** (($localPacked & 0x07) + 1));
                }

                // Tamaño mínimo del código LZW y sus sub-bloques.
                $position = self::skipSubBlocks($data, $position + 1, $length);

                continue;
            }

            break;
        }

        return $frames;
    }

    private static function skipSubBlocks(string $data, int $position, int $length): int
    {
        while ($position < $length) {
            $size = ord($data[$position]);
            $position++;

            if ($size === 0) {
                break;
            }

            $position += $size;
        }

        return $position;
    }
}
