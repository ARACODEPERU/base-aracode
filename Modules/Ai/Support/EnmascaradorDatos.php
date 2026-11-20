<?php

namespace Modules\Ai\Support;

/**
 * Enmascara datos personales antes de enviar un texto a un proveedor de IA.
 *
 * Aracodito solo recibe la pregunta y el mapa de pantallas, pero la persona
 * puede escribir un DNI, un RUC, un teléfono o un correo. Se reemplazan en el
 * servidor, antes de salir del sistema.
 */
class EnmascaradorDatos
{
    public static function enmascarar(string $texto): string
    {
        // Correos electrónicos.
        $texto = preg_replace(
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            '***',
            $texto
        ) ?? $texto;

        // DNI (8), teléfono (9), RUC (11) y cualquier cifra larga.
        $texto = preg_replace('/\b\d{8,11}\b/', '***', $texto) ?? $texto;

        return $texto;
    }
}
