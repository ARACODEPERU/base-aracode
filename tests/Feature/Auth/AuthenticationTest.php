<?php

use App\Models\Parameter;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Inertia\Testing\AssertableInertia as Assert;

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

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
