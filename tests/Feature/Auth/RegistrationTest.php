<?php

use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Registro de organizadores
|--------------------------------------------------------------------------
|
| El alta crea el usuario responsable y su organización con prueba gratuita.
| La pantalla la elige la portada publicada (config/registro.php), pero el
| formulario y el envío son los mismos para todas las variantes.
|
*/

beforeEach(function () {
    Parameter::updateOrCreate(
        ['parameter_code' => 'PW00001'],
        ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
    );
});

function datosDeRegistro(array $cambios = []): array
{
    return array_merge([
        'organization_name' => 'Academia Local',
        'organization_type' => 'persona_natural',
        'ruc' => null,
        'first_name' => 'Ana',
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'Ruiz',
        'telefono' => '999888777',
        'tipo_identidad' => 'DNI',
        'numero_identidad' => '12345678',
        'sexo' => 'F',
        'direccion' => 'Av. Siempre Viva 123',
        'email' => 'ana@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'acepto' => '1',
    ], $cambios);
}

it('muestra la variante de torneos en su portada', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/RegisterPichanguero'));
});

it('responde 404 en las portadas que todavía no habilitan el registro', function () {
    Parameter::where('parameter_code', 'PW00001')->update(['value_default' => '3']);

    $this->get('/register')->assertNotFound();
});

it('crea el usuario responsable y su organización con la prueba gratuita', function () {
    $respuesta = $this->post('/register', datosDeRegistro());

    // El alta autentica al organizador y lo deja en su panel deportivo.
    $respuesta->assertRedirect(route('even_dashboard'));

    $this->assertAuthenticated();

    $usuario = User::where('email', 'ana@example.com')->first();
    $persona = Person::where('number', '12345678')->first();
    expect($usuario)->not->toBeNull()
        ->and($persona)->not->toBeNull()
        ->and((int) $usuario->person_id)->toBe((int) $persona->id)
        ->and($usuario->name)->toBe('Ana')
        ->and($usuario->email)->toBe('ana@example.com')
        ->and($usuario->socialevent_organization_id)->not->toBeNull()
        ->and($usuario->hasRole('sports_organizer'))->toBeTrue();

    $this->assertDatabaseHas('people', [
        'id' => $persona->id,
        'document_type_id' => '1',
        'number' => '12345678',
        'names' => 'Ana',
        'father_lastname' => 'Pérez',
        'mother_lastname' => 'Ruiz',
        'full_name' => 'Pérez Ruiz Ana',
        'email' => 'ana@example.com',
        'telephone' => '999888777',
        'address' => 'Av. Siempre Viva 123',
        'gender' => 'F',
    ]);

    $this->assertDatabaseHas('socialevent_organizations', [
        'id' => $usuario->socialevent_organization_id,
        'name' => 'Academia Local',
        'status' => 'trial',
    ]);
});

it('no crea la cuenta si el correo ya está registrado', function () {
    User::create([
        'name' => 'Otra',
        'email' => 'ana@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $this->post('/register', datosDeRegistro())
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

it('no crea la cuenta si el número de identidad ya está registrado', function () {
    User::create([
        'name' => 'Otra',
        'email' => 'otra@example.com',
        'numero_identidad' => '12345678',
        'password' => Hash::make('Password123!'),
    ]);

    $this->post('/register', datosDeRegistro())
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
});

it('no crea nada fuera del modo torneos aunque se envíe la petición', function () {
    Parameter::where('parameter_code', 'PW00001')->update(['value_default' => '1']);

    $this->post('/register', datosDeRegistro())->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
});
