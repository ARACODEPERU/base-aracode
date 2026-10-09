<?php

namespace Modules\Academic\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Fuente unica del codigo QR del carné escolar.
 *
 * El contenido del QR es el student_code tal cual (ej: 2026-0001) porque es lo
 * que YA esta impreso en los carnés repartidos: la porteria tiene que poder leer
 * esos carnés sin reimprimirlos. Por lo mismo, cualquier cambio de formato
 * (por ejemplo un payload firmado) debe convivir con este durante una temporada.
 *
 * Aqui viven las opciones de render que antes estaban duplicadas en el carné
 * individual y en la impresion masiva, para que el carné que se imprime y el
 * lector de porteria no se desincronicen.
 */
class StudentCardQr
{
    /**
     * Opciones de render compartidas por el carné individual y el masivo.
     */
    public static function options(): QROptions
    {
        return new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 10,
            'quietzoneSize' => 2,
            'imageBase64' => true,
        ]);
    }

    /**
     * Contenido que se codifica en el QR: el codigo del alumno sin tocar, tal
     * como esta impreso en los carnés que ya circulan.
     */
    public static function payload(?string $studentCode): string
    {
        return trim((string) $studentCode);
    }

    /**
     * Normaliza lo que devuelve un lector. La pistola suele agregar un retorno
     * de carro o espacios al final, y al escribir a mano se cuela alguna
     * minuscula. Los codigos del colegio son mayusculas sin espacios, asi que
     * normalizar en la lectura no altera ningun codigo valido.
     */
    public static function normalize(?string $raw): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $raw));
    }

    /**
     * Imagen PNG del QR en data URI, lista para DomPDF.
     */
    public static function dataUri(?string $studentCode): string
    {
        return (new QRCode(self::options()))->render(self::payload($studentCode));
    }
}
