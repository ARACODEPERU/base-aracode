<?php

use App\Models\Parameter;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Socialevents\Services\SportsOrganizationService;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('el modo torneos usa el login de organizadores', function () {
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
    );

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/LoginOrganizer'));
});

test('los demás modos conservan el login general', function () {
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '1'],
    );

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('el organizador deportivo aterriza en el dashboard de gestión deportiva', function () {
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
    );

    $user = User::factory()->create();
    app(SportsOrganizationService::class)->createOrganizationForUser($user, [
        'nombre' => 'Liga de Prueba',
    ]);
    $user->refresh();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('even_dashboard'));

    // El destino existe y el organizador puede abrirlo con su prueba vigente.
    $this->get(route('even_dashboard'))->assertOk();
});

test('el organizador suspendido aterriza en Mis pagos para regularizar su acceso', function () {
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
    );

    $user = User::factory()->create();
    $organizationId = app(SportsOrganizationService::class)->createOrganizationForUser($user, [
        'nombre' => 'Liga Suspendida',
    ]);
    DB::table('socialevent_organizations')->where('id', $organizationId)->update([
        'status' => 'suspended',
    ]);
    $user->refresh();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('even_organizations.topup.index'));

    // La pantalla de pagos sigue disponible pese a la suspensión.
    $this->get(route('even_organizations.topup.index'))->assertOk();
});

test('en modo torneos las cuentas sin organización conservan el dashboard general', function () {
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
    );

    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
