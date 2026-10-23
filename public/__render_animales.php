<?php

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ViewErrorBag;

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

URL::forceRootUrl('http://127.0.0.1:8099');
URL::forceScheme('http');

$session = $app['session']->driver();
$session->setId('animales-test');
$session->start();

$request = Illuminate\Http\Request::create('http://127.0.0.1:8099/');
$request->setLaravelSession($session);
$app->instance('request', $request);

view()->share('errors', new ViewErrorBag());

$animals = collect(config('colegio_animales.animals', []))
    ->map(function (array $animal): array {
        $animal['photo_url'] = asset($animal['photo']);
        $animal['audio_url'] = asset($animal['audio']);

        return $animal;
    })
    ->values()
    ->all();

$portal = [
    'school' => [
        'name' => 'Colegio de prueba',
        'initial' => 'C',
        'logo_url' => null,
        'phone' => null,
        'phone_digits' => null,
        'email' => null,
    ],
    'student' => [
        'name' => 'Ana',
        'full_name' => 'Ana Pérez',
        'code' => 'A001',
        'avatar_url' => null,
    ],
    'grade' => [
        'label' => 'Inicial · 4 años A',
    ],
    'progress' => [
        'level' => 2,
    ],
];

echo view('colegio.juegos.memoria-animales', [
    'portal' => $portal,
    'dockActive' => 'juegos',
    'animals' => $animals,
])->render();
