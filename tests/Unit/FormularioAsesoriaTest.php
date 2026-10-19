<?php

use App\Mail\ContactFormMailable;
use App\Models\ContactMessage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Jobs\SendContactMessageTelegram;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Formulario de asesoría (el modal «Solicitar Asesoría»)
|--------------------------------------------------------------------------
|
| Los botones «Solicitar Asesoría» del sitio (navbar, hero, CTA y las tarjetas
| del ecosistema) abren el modal de components/v2/asesoria-modal.blade.php en
| vez de saltar a /contacto. El modal envía al MISMO destino que la página
| (`contacto_store`), así que el mensaje se guarda en `contact_messages`, que es
| lo que lista /cms/contact-messages.
|
| Estas pruebas fijan el contrato de ese flujo:
|  - El envío AJAX guarda la solicitud y responde JSON, no una redirección.
|  - Un formulario con errores responde 422 con los mensajes, sin guardar nada.
|  - El modal viaja en las páginas del sitio, con el formulario completo, y los
|    botones conservan su enlace a /contacto (el respaldo sin JavaScript).
|  - La lista de servicios del modal es la misma que la de /contacto: una sola
|    lista de servicios en todo el sitio, aunque se pinte dos veces.
|  - El campo trampa oculto descarta a los bots enviando por AJAX sin guardar
|    nada, y el límite de envíos por IP corta la ráfaga antes de que llene el
|    buzón del CMS.
|
| Corre como Unit y no como Feature por la misma razón que las del catálogo: la
| suite Feature no arranca en sqlite. La tabla del buzón se crea aquí tal como
| la crea su migración.
|
*/

uses(TestCase::class);

beforeEach(function () {
    crearTablasCmsDelSitio();

    Schema::create('contact_messages', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('name');
        $tabla->string('email');
        $tabla->string('phone')->nullable();
        $tabla->string('company')->nullable();
        $tabla->string('service')->nullable();
        $tabla->text('message');
        $tabla->enum('status', ['pending', 'read', 'replied'])->default('pending');
        $tabla->text('admin_notes')->nullable();
        $tabla->timestamps();
    });

    // El captcha se apaga por defecto: la suite no debe depender de las claves
    // del .env (en desarrollo están puestas y sin token real no hay envío
    // posible). Los casos que lo prueban lo encienden a propósito.
    config([
        'services.recaptcha.site_key' => null,
        'services.recaptcha.secret_key' => null,
    ]);
});

/**
 * Cuántos botones del sitio abren el modal («data-asesoria»). El sufijo -cerrar
 * de los botones del propio modal no cuenta: se busca el atributo suelto.
 */
function botonesDeAsesoria(string $html): int
{
    return substr_count($html, 'data-asesoria ');
}

it('guarda la solicitud del modal en el buzón que lista /cms/contact-messages', function () {
    Mail::fake();

    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'phone' => '+51 999 111 222',
        'company' => 'Empresa Uno',
        'service' => 'automatizacion',
        'message' => 'Quiero automatizar el área de compras.',
        // Sin esta aceptacion el controlador no guarda nada: es la prueba de que
        // el dato viajo con consentimiento.
        'acepto' => '1',
    ], [
        // El modal envía con estas cabeceras: el controlador tiene que
        // responder JSON, no una redirección.
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $respuesta->assertOk()->assertJson(['success' => true]);

    expect(ContactMessage::count())->toBe(1);

    $mensaje = ContactMessage::first();

    expect($mensaje->name)->toBe('Ana Quispe');
    expect($mensaje->email)->toBe('ana@empresa.test');
    expect($mensaje->phone)->toBe('+51 999 111 222');
    expect($mensaje->company)->toBe('Empresa Uno');
    expect($mensaje->service)->toBe('automatizacion');
    expect($mensaje->message)->toBe('Quiero automatizar el área de compras.');

    // Entra como pendiente: es el estado con el que el CMS abre su listado.
    expect($mensaje->status)->toBe('pending');

    // Y dispara el mismo aviso por correo que el formulario de /contacto.
    Mail::assertQueued(ContactFormMailable::class);
});

it('avisa por Telegram a los destinatarios de alertas del sistema', function () {
    Mail::fake();
    Bus::fake();

    // El aviso usa los chat_id de Seguridad → Alertas: la tabla la crea su
    // migración real, igual que en producción.
    (require base_path('Modules/Security/Database/Migrations/2026_09_30_000030_create_security_alert_recipients_table.php'))->up();

    SecurityAlertRecipient::create([
        'name' => 'Equipo',
        'chat_id' => '123456',
        'is_active' => true,
    ]);

    $this->postJson(route('contacto_store'), [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Consulta que debe llegar al chat.',
        'acepto' => '1',
    ], [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertOk()->assertJson(['success' => true]);

    // El job lleva el chat de los destinatarios y los datos del contacto.
    Bus::assertDispatched(SendContactMessageTelegram::class, function ($job) {
        return $job->chatIds === ['123456']
            && str_contains($job->text, 'Ana Quispe')
            && str_contains($job->text, 'ana@empresa.test')
            && str_contains($job->text, 'Consulta que debe llegar al chat.');
    });
});

it('no guarda ni avisa por correo si falta la aceptación de la política', function () {
    Mail::fake();

    // El envío de un dato personal sin consentimiento no se guarda ni dispara el
    // aviso por correo: el error apunta a la casilla que falta.
    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Consulta sin aceptar la política.',
    ], [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $respuesta->assertStatus(422)
        ->assertJsonValidationErrors(['acepto' => 'Debe aceptar la Política de Privacidad para enviar sus datos.']);

    expect(ContactMessage::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('publica la casilla de consentimiento en la página de contacto y en el modal', function () {
    foreach (['/contacto', '/'] as $ruta) {
        $html = $this->get($ruta)->assertOk()->getContent();

        // La casilla viaja con el formulario, marcada como obligatoria, y el
        // enlace lleva a la política de privacidad publicada.
        expect($html)->toContain('name="acepto"');
        expect($html)->toContain('required');
        expect($html)->toContain(route('politica_privacidad'));
    }
});

it('devuelve los errores en JSON y no guarda nada', function () {
    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => '',
        'email' => 'no-es-un-email',
        'service' => '',
        'message' => '',
    ], [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    // La respuesta es la de validación de Laravel: los mensajes que el modal
    // pinta en su recuadro de error.
    $respuesta->assertStatus(422)
        ->assertJsonStructure(['message', 'errors'])
        ->assertJsonValidationErrors([
            'name' => 'El nombre es obligatorio.',
            'email' => 'Debe ingresar un email válido.',
            'service' => 'Seleccione un servicio.',
            'message' => 'El mensaje es obligatorio.',
        ]);

    expect(ContactMessage::count())->toBe(0);
});

it('publica el modal en las páginas del sitio, con el formulario y sus botones', function (string $ruta, int $botones) {
    $html = $this->get($ruta)->assertOk()->getContent();

    // El modal con el formulario completo, enviando al alta de /contacto.
    expect($html)->toContain('id="asesoriaModal"');
    expect($html)->toContain('action="'.route('contacto_store').'"');
    expect($html)->toContain('role="dialog"');

    foreach (['name', 'email', 'phone', 'company', 'service', 'message'] as $campo) {
        expect($html)->toContain('name="'.$campo.'"');
    }

    // Los botones que lo abren y el enlace de respaldo en el mismo enlace: sin
    // JavaScript el clic sigue llevando al formulario de /contacto.
    expect($html)->toContain('href="'.route('contacto').'" data-asesoria');
    expect(botonesDeAsesoria($html))->toBe($botones);
})->with([
    // La portada: navbar (escritorio y móvil), hero y el CTA del pie.
    ['/', 4],
    // El ecosistema: navbar, la tarjeta de Automatización y el CTA del pie.
    ['/soluciones', 4],
    // El servicio de páginas web: navbar y sus dos botones de asesoría.
    ['/paginas-web', 4],
]);

it('pinta la misma lista de servicios que la página de contacto', function () {
    $html = $this->get('/contacto')->getContent();

    // El primer selector de servicios del documento es el de la página; el del
    // modal se busca por su identificador.
    preg_match('#<select[^>]*name="service"[^>]*>(.*?)</select>#s', $html, $pagina);
    preg_match('#<select[^>]*id="asesoriaServicio"[^>]*>(.*?)</select>#s', $html, $modal);

    preg_match_all('#<option value="([^"]*)"#', $pagina[1], $deLaPagina);
    preg_match_all('#<option value="([^"]*)"#', $modal[1], $delModal);

    // Una sola lista de servicios para todo el sitio: si se agrega un servicio
    // en una página, la otra no puede quedarse atrás.
    expect($delModal[1])->not->toBeEmpty();
    expect($delModal[1])->toBe($deLaPagina[1]);
});

it('manda a Google el token que pide el modal antes de enviar', function () {
    config([
        'services.recaptcha.site_key' => 'clave-de-prueba',
        'services.recaptcha.secret_key' => 'secreto-de-prueba',
    ]);

    Http::fake([
        'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true], 200),
    ]);

    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Con token del modal.',
        'acepto' => '1',
        'g-recaptcha-response' => 'token-de-prueba',
    ]);

    $respuesta->assertOk()->assertJson(['success' => true]);

    // El token que el modal escribe en el campo es el que viaja a Google.
    Http::assertSent(function ($peticion) {
        return str_contains($peticion->url(), '/recaptcha/api/siteverify')
            && $peticion['response'] === 'token-de-prueba'
            && $peticion['secret'] === 'secreto-de-prueba';
    });

    expect(ContactMessage::count())->toBe(1);
});

it('descarta en silencio los envíos que rellenan el campo trampa', function () {
    Mail::fake();

    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => 'Bot Spam',
        'email' => 'bot@spam.test',
        'service' => 'otro',
        'message' => 'Visita mi sitio con ofertas.',
        // El campo que ninguna persona ve, rellenado: es un bot.
        'website' => 'https://spam.example',
    ], [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    // El bot recibe el mismo éxito que un envío bueno: no aprende cuál de los
    // campos lo delató, así que no vuelve a intentarlo rellenando otro.
    $respuesta->assertOk()->assertJson(['success' => true]);

    expect(ContactMessage::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('acepta el envío cuando el campo trampa viaja vacío', function () {
    Mail::fake();

    // El campo vacío es lo normal —así lo manda el formulario del sitio— y no
    // puede contar como spam: solo cuenta con contenido.
    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Consulta normal.',
        'acepto' => '1',
        'website' => '',
    ], [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $respuesta->assertOk()->assertJson(['success' => true]);

    expect(ContactMessage::count())->toBe(1);
});

it('lleva el campo trampa oculto en el modal y en la página de contacto', function (string $ruta, string $idCampo) {
    $html = $this->get($ruta)->assertOk()->getContent();

    // Dentro del contenedor que lo saca de la pantalla, sin foco y escondido a
    // los lectores de pantalla: una persona no puede rellenarlo, un bot que
    // completa todo lo que encuentra en el HTML sí.
    expect($html)->toContain('class="ara-trampa"');
    expect($html)->toContain('name="website" tabindex="-1"');
    expect($html)->toContain('id="'.$idCampo.'"');
})->with([
    ['/', 'asesoriaSitioWeb'],
    ['/contacto', 'contactoSitioWeb'],
]);

it('publica en la hoja que carga el navegador la regla que esconde la trampa', function () {
    // webpage-v2.css vive duplicado: el de resources es el original y el de
    // public el que llega al navegador. Si la regla solo estuviera en uno, la
    // trampa sería un campo de texto a la vista.
    foreach (['css/webpage-v2.css', '../resources/css/webpage-v2.css'] as $hoja) {
        $css = file_get_contents(public_path($hoja));

        expect($css)->toContain('.ara-trampa {');
        expect($css)->toContain('left: -9999px');
    }
});

it('corta por IP la ráfaga de envíos y lo avisa en español', function () {
    Mail::fake();

    $datos = [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Consulta repetida.',
        'acepto' => '1',
    ];

    // Los primeros envíos del minuto (el tope del limitador es 5) pasan, aunque
    // repitan los mismos datos.
    foreach (range(1, 5) as $envio) {
        $this->postJson(route('contacto_store'), $datos, [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();
    }

    // El siguiente no llega al buzón: lo corta el middleware, no el controlador.
    $respuesta = $this->postJson(route('contacto_store'), $datos, [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $respuesta->assertStatus(429)->assertJsonPath('success', false);

    // El aviso lo escribe RouteServiceProvider, en español y con la espera que
    // calcula el propio limitador.
    expect($respuesta->json('message'))->toContain('demasiadas solicitudes');
    expect($respuesta->headers->get('Retry-After'))->not->toBeNull();

    expect(ContactMessage::count())->toBe(5);
});

it('avisa con una redirección cuando el límite se pasa sin JavaScript', function () {
    Mail::fake();

    $datos = [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Consulta repetida.',
        'acepto' => '1',
    ];

    foreach (range(1, 5) as $envio) {
        $this->post(route('contacto_store'), $datos)
            ->assertRedirect(route('contacto'))
            ->assertSessionHas('success');
    }

    // Sin AJAX el envío se manda como un formulario normal: la respuesta tiene
    // que ser la página de contacto con un aviso, no un error del servidor.
    $this->post(route('contacto_store'), $datos)
        ->assertRedirect(route('contacto'))
        ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'demasiadas solicitudes'));

    expect(ContactMessage::count())->toBe(5);
});

it('no toca el buzón cuando el captcha que envía el modal está vacío', function () {
    // El modal pide el token justo antes de enviar. Si Google no responde, el
    // campo viaja vacío: el controlador lo rechaza antes de guardar nada.
    config([
        'services.recaptcha.site_key' => 'clave-de-prueba',
        'services.recaptcha.secret_key' => 'secreto-de-prueba',
    ]);

    $respuesta = $this->postJson(route('contacto_store'), [
        'name' => 'Ana Quispe',
        'email' => 'ana@empresa.test',
        'service' => 'otro',
        'message' => 'Sin token de captcha.',
        'acepto' => '1',
        'g-recaptcha-response' => '',
    ]);

    $respuesta->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['g-recaptcha-response']);

    expect(ContactMessage::count())->toBe(0);
});
