<?php

use App\Support\PaginaPrincipal;
use App\Support\RegistroVariantes;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Variantes de la pantalla de registro
|--------------------------------------------------------------------------
|
| Fija el contrato de config/registro.php: cada portada publicada tiene una
| pantalla asignada, cada variante apunta a un componente que existe y solo la
| portada de torneos permite crear cuentas (el resto sigue en 404).
|
*/

uses(TestCase::class);

it('asigna una variante a cada portada publicada', function () {
    foreach (array_keys(PaginaPrincipal::paginas()) as $portada) {
        expect(RegistroVariantes::clave((string) $portada))->not->toBe('');
    }
});

it('resuelve la pantalla que corresponde a cada portada', function () {
    expect(RegistroVariantes::componente('1'))->toBe('Auth/Register')
        ->and(RegistroVariantes::componente('2'))->toBe('Auth/RegisterPichanguero')
        ->and(RegistroVariantes::componente('3'))->toBe('Auth/RegisterKapta')
        ->and(RegistroVariantes::componente('4'))->toBe('Auth/RegisterKirafact')
        ->and(RegistroVariantes::componente('5'))->toBe('Auth/RegisterPichanguero');
});

it('cae en la variante corporativa cuando la portada no está en el catálogo', function () {
    expect(RegistroVariantes::clave('99'))->toBe('corporativo');
});

it('solo habilita el registro en la portada de torneos', function () {
    expect(RegistroVariantes::habilitado('1'))->toBeFalse()
        ->and(RegistroVariantes::habilitado('2'))->toBeTrue()
        ->and(RegistroVariantes::habilitado('3'))->toBeFalse()
        ->and(RegistroVariantes::habilitado('4'))->toBeFalse()
        ->and(RegistroVariantes::habilitado('5'))->toBeFalse();
});

it('tiene un componente Vue para cada variante', function () {
    foreach (RegistroVariantes::catalogo() as $variante) {
        $ruta = resource_path('js/Pages/' . $variante['componente'] . '.vue');

        expect(is_file($ruta))->toBeTrue("Falta el componente {$ruta}");
    }
});
