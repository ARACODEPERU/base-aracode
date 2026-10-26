<?php

namespace Modules\Socialevents\Support;

/**
 * Fuentes disponibles para el editor de diplomas.
 *
 * Las primeras cinco ya existen en public/fonts (se cargan con @font-face en la
 * página del editor); las demás son familias seguras del sistema.
 */
final class DiplomaFonts
{
    public const FAMILIES = [
        'Poppins Light',
        'Pacifico Regular',
        'Intro Headr',
        'Old English',
        'Playwrite IN',
        'Arial',
        'Georgia',
        'Impact',
    ];

    /** Nombre de archivo en public/fonts por familia. */
    public const FILES = [
        'Poppins Light' => 'Poppins-Light.ttf',
        'Pacifico Regular' => 'Pacifico-Regular.ttf',
        'Intro Headr' => 'Intro-Headr.ttf',
        'Old English' => 'OLDENGL.TTF',
        'Playwrite IN' => 'PlaywriteIN-Regular.ttf',
    ];
}
