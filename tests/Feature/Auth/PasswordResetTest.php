<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

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
