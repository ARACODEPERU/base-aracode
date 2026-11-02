<?php

use App\Http\Controllers\ParametersController;
use App\Support\PaginaPrincipal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Desplegable de «Pagina web principal» en la pantalla de Parámetros
|--------------------------------------------------------------------------
|
| La lista de portadas vive en config/pagina_principal.php, pero durante un
| tiempo el desplegable se pintó con lo que había guardado en la base
| (json_query_data de PW00001). Si la migración corría con el config cacheado
| de una versión anterior, la base quedaba con las opciones viejas y SALUD no
| aparecía por más que se refrescara el navegador.
|
| Ahora el controlador arma esa fila con el catálogo del código. Esta prueba
| fija que:
|
|   1. la opción SALUD aparece aunque la fila guardada solo tenga 1 a 5;
|   2. el valor elegido por el usuario no se pisa;
|   3. el resto de parámetros sigue mostrando lo guardado en la base.
|
*/

uses(TestCase::class);

/**
 * Tabla de parámetros con las columnas que lee la pantalla.
 */
function crearTablaParametrosDelPanel(): void
{
    // El layout comparte la empresa activa desde AppServiceProvider.
    Schema::create('companies', function ($tabla) {
        $tabla->id();
        $tabla->string('name')->nullable();
    });

    Schema::create('parameters', function ($tabla) {
        $tabla->id();
        $tabla->string('parameter_code')->nullable();
        $tabla->string('description')->nullable();
        $tabla->string('control_type')->nullable();
        $tabla->text('json_query_data')->nullable();
        $tabla->string('value_default')->nullable();
        $tabla->timestamps();
    });
}

/**
 * Props que el controlador entrega a la página Inertia de Parámetros.
 *
 * Se invoca el controlador directamente y se pide la respuesta en formato
 * Inertia (JSON) para no depender del middleware ni de la vista raíz.
 *
 * @return array<string, mixed>
 */
function propsDelPanelDeParametros(): array
{
    $request = Request::create('/parameters/list', 'GET');
    $request->headers->set('X-Inertia', 'true');

    app()->instance('request', $request);

    $respuesta = app(ParametersController::class)->index()->toResponse($request);

    return json_decode($respuesta->getContent(), true, 512, JSON_THROW_ON_ERROR);
}

it('muestra SALUD aunque la fila guardada tenga solo las portadas anteriores', function () {
    crearTablaParametrosDelPanel();

    // Lo que dejó una migración ejecutada con el config cacheado viejo.
    DB::table('parameters')->insert([
        [
            'parameter_code' => PaginaPrincipal::PARAMETRO,
            'description' => 'Pagina web principal',
            'control_type' => 'sa',
            'json_query_data' => '[{"value":"1","label":"Aracode Principal"},{"value":"2","label":"Aracode torneos"},{"value":"3","label":"KAPTA LMS"},{"value":"4","label":"KIRAFACT"},{"value":"5","label":"Pichanguero"}]',
            'value_default' => '5',
        ],
        [
            'parameter_code' => 'P000030',
            'description' => 'Modulos activos',
            'control_type' => 'chq',
            'json_query_data' => '[{"value":"1","label":"Activo"}]',
            'value_default' => '1',
        ],
    ]);

    $parametros = propsDelPanelDeParametros()['props']['parameters'];

    $portada = collect($parametros)->firstWhere('parameter_code', PaginaPrincipal::PARAMETRO);

    expect($portada)->not->toBeNull();
    expect($portada['json_query_data'])->toBe(PaginaPrincipal::opcionesJson());
    expect($portada['json_query_data'])->toContain('SALUD');

    // El valor publicado no se pisa: el desplegable solo muestra las opciones.
    expect($portada['value_default'])->toBe('5');

    // Los demás parámetros siguen leyendo su lista guardada.
    $otro = collect($parametros)->firstWhere('parameter_code', 'P000030');

    expect($otro['json_query_data'])->toBe('[{"value":"1","label":"Activo"}]');
});
