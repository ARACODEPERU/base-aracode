<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Contexto HTTP minimo para que asset()/url() funcionen.
$app->instance('request', Illuminate\Http\Request::create('http://base_aracode.test/certificado-validar', 'GET'));

try {
    $html = view('academic::certificado_validar.certificado_validar', [
        'person' => '',
        'certificates' => '',
        'course' => '',
        'module' => null,
        'moduleThemes' => collect(),
        'isEnrolled' => false,
    ])->render();

    echo "RENDER OK: ".strlen($html)." bytes\n";
    foreach (['Validar Certificado', '<x-', 'Unable to locate', 'page-body-wrapper', 'footer'] as $needle) {
        $found = str_contains($html, $needle) ? 'si' : 'no';
        echo "  contiene '{$needle}': {$found}\n";
    }
} catch (Throwable $e) {
    echo "FALLO: ".get_class($e).': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
