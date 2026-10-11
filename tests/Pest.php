<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/function something() {
    // ..
}

/*
|--------------------------------------------------------------------------
| Tablas mínimas del sitio corporativo
|--------------------------------------------------------------------------
|
| WebPageController resuelve el listado de cursos del CMS en su constructor, así
| que renderizar cualquier página del sitio corporativo necesita las tablas del
| CMS aunque la página no muestre cursos. La home, además, lee el parámetro
| PW00001 para elegir entre la portada corporativa y la de torneos.
|
| Se crean a mano (y no con RefreshDatabase) porque la suite Feature de este
| repositorio no arranca en sqlite: hay migraciones de otros módulos que solo
| funcionan en MySQL (ALTER ... MODIFY, information_schema) y abortan la creación
| del esquema. Las páginas públicas que se comprueban con este helper no tocan
| ninguna otra tabla.
*/
function crearTablasCmsDelSitio(): void
{
    Schema::create('cms_sections', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('component_id')->nullable();
    });

    Schema::create('cms_items', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->text('content')->nullable();
    });

    Schema::create('cms_section_items', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->unsignedBigInteger('section_id');
        $tabla->unsignedBigInteger('item_id');
        $tabla->integer('position')->default(0);
    });

    Schema::create('parameters', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('parameter_code')->nullable();
        $tabla->string('value_default')->nullable();
    });
}
