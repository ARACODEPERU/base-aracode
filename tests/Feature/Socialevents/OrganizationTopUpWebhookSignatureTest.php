<?php

namespace Tests\Feature\Socialevents;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;
use Tests\TestCase;

/**
 * El webhook de recargas de Socialevents comparte el secreto de MercadoPago
 * administrado en IntegrationHub.
 */
class OrganizationTopUpWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_topup';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.webhook_secret' => '']);

        // La migración de Fase 1 ya siembra la integración MercadoPago.
        $integration = Integration::query()->where('name', 'MercadoPago')->first()
            ?? Integration::create([
                'name' => 'MercadoPago',
                'url_base' => 'https://api.mercadopago.com',
                'execution_type' => 'manual',
                'is_active' => true,
            ]);

        $integration->auths()->updateOrCreate(
            ['field_name' => 'webhook_secret'],
            [
                'field_value' => self::SECRET,
                'field_type' => 'password',
                'auth_location' => 'header',
                'is_enabled' => true,
            ]
        );

        $this->app->forgetInstance(IntegrationCredentialResolver::class);
    }

    public function test_acepta_una_firma_valida_con_el_secreto_del_hub(): void
    {
        $this->postJson($this->url(), ['type' => 'merchant_order'], $this->firma(123))
            ->assertOk()
            ->assertJsonPath('received', true);
    }

    public function test_rechaza_una_firma_invalida(): void
    {
        $this->postJson($this->url(), ['type' => 'merchant_order'], [
            'x-signature' => 'ts=123,v1=firma-falsa',
            'x-request-id' => 'req-123',
        ])->assertStatus(401);
    }

    public function test_rechaza_si_no_hay_secreto(): void
    {
        Integration::query()->where('name', 'MercadoPago')->firstOrFail()
            ->auths()->where('field_name', 'webhook_secret')->update(['field_value' => null]);

        $this->app->forgetInstance(IntegrationCredentialResolver::class);

        $this->postJson($this->url(), ['type' => 'merchant_order'], $this->firma(123))
            ->assertStatus(401);
    }

    private function url(): string
    {
        return route('even_organizations.topup.webhook') . '?data.id=123';
    }

    /**
     * @return array<string, string>
     */
    private function firma(int $id): array
    {
        $ts = (string) now()->timestamp;
        $manifest = "id:{$id};request-id:req-{$id};ts:{$ts};";

        return [
            'x-signature' => 'ts=' . $ts . ',v1=' . hash_hmac('sha256', $manifest, self::SECRET),
            'x-request-id' => 'req-' . $id,
        ];
    }
}
