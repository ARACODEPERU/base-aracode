<?php

namespace Tests\Unit\Modules\Integrationhub;

use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Entities\IntegrationTelegramRegistrationSession;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Altas, bajas y conversacion de registro del vinculo persona <-> chat.
 *
 * La promesa: /start abre una conversacion que caduca y se puede cerrar; un
 * chat no puede quedar vinculado a dos personas a la vez; y la baja (/baja)
 * apaga el contacto sin borrarlo, para poder reactivarlo escribiendo /start.
 */
class TelegramRegistrationServiceTest extends TestCase
{
    use BuildsTelegramBotSchema;

    private TelegramRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramSchema();
        $this->createTelegramSchema();

        $this->service = app(TelegramRegistrationService::class);
    }

    protected function tearDown(): void
    {
        $this->dropTelegramSchema();

        parent::tearDown();
    }

    public function test_abre_una_sesion_de_registro_con_vigencia(): void
    {
        $session = $this->service->beginSession('555', 'ana_tg', 'Ana');

        $this->assertSame('555', $session->chat_id);
        $this->assertSame('awaiting_document', $session->step);
        $this->assertSame(0, $session->attempts);
        $this->assertSame('ana_tg', $session->telegram_username);
        $this->assertTrue($session->expires_at->isFuture());
    }

    public function test_volver_a_empezar_reinicia_la_sesion(): void
    {
        $this->service->beginSession('555');
        $session = $this->service->sessionFor('555');
        $this->service->countAttempt($session);

        $reiniciada = $this->service->beginSession('555', 'ana_tg', 'Ana');

        $this->assertSame(1, IntegrationTelegramRegistrationSession::count());
        $this->assertSame(0, $reiniciada->attempts);
    }

    public function test_una_sesion_vencida_se_descarta(): void
    {
        $this->service->beginSession('555');
        IntegrationTelegramRegistrationSession::where('chat_id', '555')
            ->update(['expires_at' => now()->subMinute()]);

        $this->assertNull($this->service->sessionFor('555'));

        // Se borra al pasar: el siguiente mensaje suelto no se lee como documento.
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
    }

    public function test_cuenta_los_intentos_y_tiene_un_tope(): void
    {
        $session = $this->service->beginSession('555');

        $this->assertSame(1, $this->service->countAttempt($session));
        $this->assertSame(2, $this->service->countAttempt($session));
        $this->assertSame(5, $this->service->maxAttempts());
    }

    public function test_cerrar_la_sesion_la_elimina(): void
    {
        $this->service->beginSession('555');

        $this->service->closeSession('555');

        $this->assertNull($this->service->sessionFor('555'));
    }

    public function test_attach_guarda_el_chat_de_la_persona(): void
    {
        $contact = $this->service->attach(60, '999', 'pepe', 'Pepe');

        $this->assertSame('999', $contact->chat_id);
        $this->assertSame('active', $contact->status);
        $this->assertNotNull($contact->registered_at);

        $this->assertSame(60, (int) $this->service->findByChatId('999')->person_id);
    }

    public function test_un_chat_no_puede_quedar_en_dos_personas(): void
    {
        $anterior = $this->service->attach(40, '777');

        $this->service->attach(41, '777', 'nuevo', 'Nuevo');

        $anterior->refresh();

        $this->assertNull($anterior->chat_id);
        $this->assertSame('inactive', $anterior->status);
        $this->assertSame('777', IntegrationTelegramContact::where('person_id', 41)->value('chat_id'));
    }

    public function test_una_persona_que_vuelve_a_registrarse_reemplaza_su_chat(): void
    {
        $this->service->attach(50, '111');

        $contact = $this->service->attach(50, '222');

        $this->assertSame('222', $contact->chat_id);
        $this->assertSame(1, IntegrationTelegramContact::where('person_id', 50)->count());
    }

    public function test_attach_exige_persona_y_chat(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->attach(0, '555');
    }

    public function test_la_baja_marca_el_contacto_como_inactivo(): void
    {
        $this->service->attach(50, '888');

        $this->assertTrue($this->service->deactivate('888'));
        $this->assertSame('inactive', IntegrationTelegramContact::where('person_id', 50)->value('status'));

        $this->assertFalse($this->service->deactivate('888999'));
    }

    public function test_solo_los_contactos_activos_con_chat_estan_suscritos(): void
    {
        $this->service->attach(70, '101');
        $this->service->attach(71, '102');
        $this->service->deactivate('102');

        $subscribed = IntegrationTelegramContact::query()->subscribed()->pluck('person_id')->all();

        $this->assertSame([70], array_map('intval', $subscribed));
    }
}
