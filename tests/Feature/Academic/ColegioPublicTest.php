<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaSchool;
use Tests\TestCase;

class ColegioPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_institucional_muestra_los_datos_del_colegio_y_sus_niveles(): void
    {
        AcaSchool::defaultSchool()->update([
            'name' => 'Colegio Los Álamos',
            'address' => 'Av. Los Álamos 123',
            'phone' => '987 654 321',
            'email' => 'informes@losalamos.edu.pe',
            'modular_code' => '1234567',
            'type' => AcaSchool::TYPE_PRIVADO,
        ]);

        $response = $this->get(route('colegio.home'));

        $response->assertOk()
            ->assertSee('Colegio Los Álamos')
            ->assertSee('Av. Los Álamos 123')
            ->assertSee('987 654 321')
            ->assertSee('informes@losalamos.edu.pe')
            ->assertSee('1234567')
            ->assertSee('Privado')
            ->assertSee('Ingresar Inicial')
            ->assertSee('Ingresar Primaria')
            ->assertSee('Ingresar Secundaria')
            ->assertSee('Próximamente');
    }

    public function test_el_acceso_de_inicial_esta_habilitado_y_el_de_los_otros_niveles_todavia_no(): void
    {
        $response = $this->get(route('colegio.home'));

        $response->assertOk()
            ->assertSee('href="' . route('colegio.inicial.login') . '"', false);

        $this->get('/colegio/primaria/login')->assertNotFound();
        $this->get('/colegio/secundaria/login')->assertNotFound();
    }

    public function test_el_login_de_inicial_se_muestra_sin_autenticacion_con_sus_assets(): void
    {
        $response = $this->get(route('colegio.inicial.login'));

        $response->assertOk()
            ->assertSee('Aula Virtual · Inicial')
            ->assertSee('¡Hola! Vamos a aprender')
            ->assertSee('id="loginForm"', false)
            ->assertSee('id="panelStart"', false)
            ->assertSee('themes/colegio/js/vendor/anime.umd.min.js')
            ->assertSee('themes/colegio/js/login.js');
    }

    public function test_las_paginas_publicas_siguen_respondiendo_sin_colegio_registrado(): void
    {
        AcaSchool::query()->delete();

        $this->get(route('colegio.home'))->assertOk();
        $this->get(route('colegio.inicial.login'))->assertOk();
    }

    public function test_el_logo_del_colegio_se_publica_cuando_el_archivo_existe(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/schools/logo.png', 'archivo-de-prueba');

        AcaSchool::defaultSchool()->update(['logo' => 'uploads/schools/logo.png']);

        $this->get(route('colegio.home'))
            ->assertOk()
            ->assertSee('/storage/uploads/schools/logo.png');

        $this->get(route('colegio.inicial.login'))
            ->assertOk()
            ->assertSee('/storage/uploads/schools/logo.png');
    }
}
