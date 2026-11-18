<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Comando de diagnóstico de Fase 0 para suscripciones recurrentes.
 *
 * No toca la base ni cobra: solo valida el token, las APIs de Suscripciones y
 * la presencia del secreto del webhook, con respuestas falsas de MercadoPago.
 */
class MercadoPagoSubscriptionsCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporta_cuenta_y_disponibilidad_de_suscripciones(): void
    {
        config([
            'services.mercadopago.token' => 'TEST-TOKEN',
            'services.mercadopago.webhook_secret' => 'TEST-SECRET',
        ]);

        Http::fake([
            'api.mercadopago.com/users/me' => Http::response([
                'id' => 123456,
                'nickname' => 'ARACODE',
                'site_id' => 'MPE',
                'email' => 'cuenta@example.com',
            ], 200),
            'api.mercadopago.com/preapproval/search*' => Http::response([
                'paging' => ['total' => 0],
                'results' => [],
            ], 200),
            'api.mercadopago.com/preapproval_plan/search*' => Http::response([
                'paging' => ['total' => 2],
                'results' => [],
            ], 200),
        ]);

        $this->artisan('academic:mercadopago-subscriptions-check')
            ->expectsOutputToContain('MPE')
            ->expectsOutputToContain('suscripciones (preapproval): disponible')
            ->expectsOutputToContain('registros actuales: 2')
            ->expectsOutputToContain('Secreto de webhook configurado desde .env')
            ->assertExitCode(0);
    }

    public function test_sin_token_avisa_y_falla(): void
    {
        config([
            'services.mercadopago.token' => '',
            'services.mercadopago.webhook_secret' => '',
        ]);

        Http::fake();

        $this->artisan('academic:mercadopago-subscriptions-check')
            ->expectsOutputToContain('MERCADOPAGO_TOKEN no está configurado')
            ->assertExitCode(1);
    }

    public function test_avisa_si_falta_el_secreto_del_webhook(): void
    {
        config([
            'services.mercadopago.token' => 'TEST-TOKEN',
            'services.mercadopago.webhook_secret' => '',
        ]);

        Http::fake([
            'api.mercadopago.com/users/me' => Http::response([
                'id' => 123456,
                'nickname' => 'ARACODE',
                'site_id' => 'MPE',
            ], 200),
            'api.mercadopago.com/preapproval/search*' => Http::response(['paging' => ['total' => 0]], 200),
            'api.mercadopago.com/preapproval_plan/search*' => Http::response(['paging' => ['total' => 0]], 200),
        ]);

        $this->artisan('academic:mercadopago-subscriptions-check')
            ->expectsOutputToContain('Falta el secreto del webhook')
            ->expectsOutputToContain('IntegrationHub')
            ->assertExitCode(0);
    }
}
