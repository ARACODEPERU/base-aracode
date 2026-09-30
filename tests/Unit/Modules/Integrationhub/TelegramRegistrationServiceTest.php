<?php

namespace Tests\Unit\Modules\Integrationhub;

use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Altas y bajas del vinculo persona <-> chat de Telegram.
 *
 * La promesa: el codigo del enlace es de un solo uso y caduca; un chat no puede
 * quedar vinculado a dos personas a la vez; y la baja (/baja) apaga el contacto
 * sin borrarlo, para poder reactivarlo con un enlace nuevo.
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

    public function test_emite_un_codigo_de_un_solo_uso_con_vigencia(): void
    {
        $contact = $this->service->issueCode(10, 'Ana Perez');

        $this->assertNotNull($contact);
        $this->assertSame(10, (int) $contact->person_id);
        $this->assertNull($contact->chat_id);
        $this->assertSame(48, strlen((string) $contact->registration_code));
        $this->assertTrue($contact->code_expires_at->isFuture());
        $this->assertSame('Ana Perez', $contact->telegram_first_name);
    }

    public function test_no_emite_codigo_si_la_persona_ya_tiene_chat_activo(): void
    {
        $this->service->attach(10, '555');

        $this->assertNull($this->service->issueCode(10));
    }

    public function test_vuelve_a_emitir_codigo_tras_una_baja(): void
    {
        $this->service->attach(10, '555');
        $this->service->deactivate('555');

        $contact = $this->service->issueCode(10);

        $this->assertNotNull($contact);
        $this->assertNotNull($contact->registration_code);
    }

    public function test_link_consume_el_codigo_y_guarda_el_chat(): void
    {
        $contact = $this->service->issueCode(20, 'Luis');
        $code = (string) $contact->registration_code;

        $linked = $this->service->link($code, '123456', 'luis_tg', 'Luis');

        $this->assertSame('123456', $linked->chat_id);
        $this->assertSame('luis_tg', $linked->telegram_username);
        $this->assertSame('active', $linked->status);
        $this->assertNull($linked->registration_code);
        $this->assertNull($linked->code_expires_at);
        $this->assertNotNull($linked->registered_at);

        // El codigo ya no sirve dos veces.
        $this->expectException(RuntimeException::class);
        $this->service->link($code, '999999');
    }

    public function test_un_codigo_inexistente_no_vincula_nada(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no existe o ya fue usado');

        $this->service->link('codigo-inventado', '123456');
    }

    public function test_un_codigo_vencido_no_vincula_y_se_limpia(): void
    {
        $contact = $this->service->issueCode(30);
        $contact->update(['code_expires_at' => now()->subMinute()]);

        try {
            $this->service->link((string) $contact->registration_code, '123456');
            $this->fail('Un codigo vencido no debia permitir el registro.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('expiró', $exception->getMessage());
        }

        $contact->refresh();

        $this->assertNull($contact->chat_id);
        $this->assertNull($contact->registration_code);
    }

    public function test_un_chat_no_puede_quedar_en_dos_personas(): void
    {
        $anterior = $this->service->attach(40, '777');

        $contact = $this->service->issueCode(41);
        $this->service->link((string) $contact->registration_code, '777', 'nuevo', 'Nuevo');

        $anterior->refresh();

        $this->assertNull($anterior->chat_id);
        $this->assertSame('inactive', $anterior->status);
        $this->assertSame('777', IntegrationTelegramContact::where('person_id', 41)->value('chat_id'));
    }

    public function test_la_baja_marca_el_contacto_como_inactivo(): void
    {
        $this->service->attach(50, '888');

        $this->assertTrue($this->service->deactivate('888'));
        $this->assertSame('inactive', IntegrationTelegramContact::where('person_id', 50)->value('status'));

        $this->assertFalse($this->service->deactivate('888999'));
    }

    public function test_attach_es_una_alternativa_directa_sin_codigo(): void
    {
        $contact = $this->service->attach(60, '999', 'pepe', 'Pepe');

        $this->assertSame('999', $contact->chat_id);
        $this->assertSame('active', $contact->status);
        $this->assertNotNull($contact->registered_at);
        $this->assertNull($contact->registration_code);
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
