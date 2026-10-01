<?php

namespace Tests\Unit\Modules\Security;

use Mockery;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Jobs\SendSecurityErrorAlert;
use Modules\Security\Services\ErrorAlertService;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Entrega de la alerta encolada: el job reparte el mismo texto entre los
 * chat_id y aísla el fallo de uno sin dejar de avisar a los demás.
 */
class SendSecurityErrorAlertJobTest extends TestCase
{
    use BuildsSecurityAlertSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSecurityAlertSchema();
        $this->createSecurityAlertSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSecurityAlertSchema();

        parent::tearDown();
    }

    public function test_entrega_el_mensaje_a_cada_chat(): void
    {
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendFormatted')
            ->twice()
            ->with(Mockery::type('string'), 'Aviso', true)
            ->andReturn([]);

        $this->app->instance(TelegramBotService::class, $bot);

        (new SendSecurityErrorAlert(['1', '2'], 'Aviso'))->handle(app(ErrorAlertService::class));

        $this->assertTrue(true);
    }

    public function test_aisla_el_fallo_de_un_chat_sin_perder_a_los_demas(): void
    {
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendFormatted')
            ->twice()
            ->andReturnUsing(function (string $chatId) {
                if ($chatId === '1') {
                    throw new RuntimeException('chat invalido');
                }

                return [];
            });

        $this->app->instance(TelegramBotService::class, $bot);

        (new SendSecurityErrorAlert(['1', '2'], 'Aviso'))->handle(app(ErrorAlertService::class));

        $this->assertTrue(true);
    }

    public function test_no_envia_nada_con_una_lista_vacia(): void
    {
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldNotReceive('sendFormatted');

        $this->app->instance(TelegramBotService::class, $bot);

        (new SendSecurityErrorAlert([], 'Aviso'))->handle(app(ErrorAlertService::class));

        $this->assertTrue(true);
    }
}
