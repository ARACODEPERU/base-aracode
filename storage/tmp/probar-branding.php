<?php

/*
 * Prueba del controlador de Branding contra la base local.
 *
 * Ejecuta el controlador real (validación incluida) sin pasar por el navegador:
 * guarda los colores de ARACODE, comprueba que rechaza un hex inválido y que
 * los valores vacíos devuelven la instalación a la paleta de fábrica. Deja los
 * colores corporativos guardados, que es el estado que quiere la instalación.
 *
 * Uso: php artisan tinker --execute="require 'storage/tmp/probar-branding.php';"
 */

use App\Http\Controllers\BrandingController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

function probarBranding(string $ruta, array $datos): array
{
    $peticion = Request::create($ruta, 'POST', $datos);

    try {
        $respuesta = (new BrandingController())->update($peticion);

        return ['ok' => true, 'redirige' => $respuesta->getTargetUrl()];
    } catch (ValidationException $e) {
        return ['ok' => false, 'errores' => array_keys($e->errors())];
    }
}

$resultados = [];

$resultados['guardar ARACODE'] = probarBranding('/branding/update', [
    'brand_primary' => '#0188EE',
    'brand_secondary' => '#4EAE33',
]);

$empresa = Company::first();
$resultados['en base de datos'] = [
    'brand_primary' => $empresa->brand_primary,
    'brand_secondary' => $empresa->brand_secondary,
];

$resultados['rechaza hex inválido'] = probarBranding('/branding/update', [
    'brand_primary' => 'azul',
    'brand_secondary' => '#4EAE33',
]);

$resultados['sin marca (vacío)'] = probarBranding('/branding/update', [
    'brand_primary' => '',
    'brand_secondary' => '',
]);

$resultados['vuelve a ARACODE'] = probarBranding('/branding/update', [
    'brand_primary' => '#0188EE',
    'brand_secondary' => '#4EAE33',
]);

echo json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
