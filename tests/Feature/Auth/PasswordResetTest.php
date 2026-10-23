<?php

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

function activarModoTorneos(): void
{
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
    );
}

function usarModoGeneral(): void
{
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '1'],
    );
}

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    // La app envia el enlace con su Mailable propio (App\Mail\ResetPassword),
    // no con la notificacion stock de Laravel.
    Mail::assertQueued(App\Mail\ResetPassword::class);
});

test('el modo torneos usa la recuperación con la piel de Pichanguero', function () {
    activarModoTorneos();

    $this->get('/forgot-password')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPasswordOrganizer'));
});

test('los demás modos conservan la recuperación general', function () {
    usarModoGeneral();

    $this->get('/forgot-password')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
});

test('el modo torneos usa el correo de restablecimiento de Pichanguero', function () {
    Mail::fake();
    activarModoTorneos();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Mail::assertQueued(App\Mail\ResetPassword::class, function ($mail) {
        return $mail->vista === 'emails.reset_password_pichanguero';
    });
});

test('los demás modos conservan el correo de restablecimiento general', function () {
    Mail::fake();
    usarModoGeneral();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Mail::assertQueued(App\Mail\ResetPassword::class, function ($mail) {
        return $mail->vista === 'emails.reset_password';
    });
});

test('otros flujos conservan el correo general aunque el modo sea torneos', function () {
    activarModoTorneos();

    $user = User::factory()->create();
    $mail = new App\Mail\ResetPassword($user);

    expect($mail->vista)->toBe('emails.reset_password');
});

test('el correo de Pichanguero incluye la marca y el enlace de restablecimiento', function () {
    activarModoTorneos();

    $user = User::factory()->create();
    $mail = new App\Mail\ResetPassword($user, 'emails.reset_password_pichanguero');
    $html = $mail->render();

    expect($html)
        ->toContain('Pichanguero')
        ->toContain('Restablecer contraseña')
        ->toContain($mail->url);
});

test('el modo torneos usa la nueva contraseña con la piel de Pichanguero', function () {
    activarModoTorneos();

    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/ResetPasswordOrganizer')
            ->where('email', $user->email)
            ->where('token', $token));
});

test('los demás modos conservan la nueva contraseña general', function () {
    usarModoGeneral();

    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword'));
});

test('reset password screen can be rendered', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $url = null;
    Mail::assertQueued(App\Mail\ResetPassword::class, function ($mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    $token = basename((string) parse_url($url, PHP_URL_PATH));

    $response = $this->get('/reset-password/'.$token);

    $response->assertStatus(200);
});

test('password can be reset with valid token', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $url = null;
    Mail::assertQueued(App\Mail\ResetPassword::class, function ($mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    $token = basename((string) parse_url($url, PHP_URL_PATH));

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nueva-contrasena',
        'password_confirmation' => 'nueva-contrasena',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertTrue(
        \Illuminate\Support\Facades\Hash::check('nueva-contrasena', $user->fresh()->password)
    );
});
