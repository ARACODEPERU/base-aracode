<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Utilidades para guardar la ficha de una persona sin borrar datos que el
 * formulario no envio.
 *
 * Regla: un campo ausente, vacio o con el texto "null" no viaja al update();
 * una ruta de imagen ya guardada tampoco se considera una imagen nueva.
 */
class PersonProfileData
{
    /**
     * Devuelve solo las columnas que el request trae con un valor real.
     *
     * @param  array<string, string>  $map  columna => nombre del input
     * @return array<string, mixed>
     */
    public static function onlyFilled(Request $request, array $map): array
    {
        $data = [];

        foreach ($map as $column => $input) {
            if (! $request->exists($input)) {
                continue;
            }

            $value = $request->input($input);

            if (! self::hasValue($value)) {
                continue;
            }

            $data[$column] = is_string($value) ? trim($value) : $value;
        }

        return $data;
    }

    /**
     * Indica si un valor sirve para guardarse (no es null, ni cadena vacia,
     * ni el literal "null" que puede llegar desde un formulario).
     */
    public static function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' && strtolower($value) !== 'null';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }

    /**
     * Guarda una imagen nueva y devuelve su ruta.
     *
     * Acepta un UploadedFile o un data URI base64. Si el input trae una ruta ya
     * guardada (por ejemplo `uploads/students/123.png`, que el formulario
     * inicializa desde props.person.image) devuelve null: el llamador debe
     * conservar la imagen actual.
     */
    public static function storeNewImage(
        Request $request,
        string $input,
        string $directory,
        string $fileBase
    ): ?string {
        $file = $request->file($input);

        if (! $file instanceof UploadedFile) {
            $file = self::fileFromDataUri($request->input($input));
        }

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? $extension
            : 'jpg';

        $base = Str::slug(pathinfo($fileBase, PATHINFO_FILENAME));
        $base = $base !== '' ? substr($base, 0, 80) : 'perfil';

        return $file->storeAs($directory, $base.'.'.$extension, 'public');
    }

    /**
     * Convierte un data URI de imagen en un archivo subido temporal. Cualquier
     * otro texto (incluida una ruta ya guardada) devuelve null.
     */
    private static function fileFromDataUri(mixed $value): ?UploadedFile
    {
        if (! is_string($value)
            || ! preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#s', $value, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false) {
            return null;
        }

        $extension = image_type_to_extension($info[2], false) ?: 'jpg';
        $temp = tempnam(sys_get_temp_dir(), 'perfil_');

        if ($temp === false || file_put_contents($temp, $binary) === false) {
            return null;
        }

        return new UploadedFile(
            $temp,
            'perfil.'.$extension,
            $info['mime'] ?? $matches[1],
            null,
            true
        );
    }
}
