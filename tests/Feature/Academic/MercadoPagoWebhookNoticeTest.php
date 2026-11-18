<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * El estado del webhook de MercadoPago se muestra en el listado de planes y en
 * el Centro de Integraciones.
 */
class MercadoPagoWebhookNoticeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.webhook_secret' => '']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('aca_suscripciones', 'web');

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->givePermissionTo('aca_suscripciones');

        // La migración de Fase 1 deja la integración con el campo vacío.
        $integration = Integration::query()->where('name', 'MercadoPago')->firstOrFail();
        $integration->auths()->where('field_name', 'webhook_secret')->update(['field_value' => null]);
        $this->app->forgetInstance(IntegrationCredentialResolver::class);
    }

    public function test_el_listado_de_planes_avisa_que_falta_el_webhook(): void
    {
        $this->actingAs($this->user)
            ->get(route('aca_subscriptions_list'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Academic::Subscriptions/List', false)
                ->where('mercadopagoWebhook.configured', false)
                ->where('mercadopagoWebhook.integration_name', 'MercadoPago'));
    }

    public function test_el_centro_de_integraciones_muestra_el_webhook_pendiente(): void
    {
        $this->actingAs($this->user)
            ->get(route('integrationhub_listado'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Integrationhub::Integration/Index', false)
                ->where('mercadopagoWebhook.configured', false)
                ->where('mercadopagoWebhook.source', null));
    }

    public function test_ambos_listados_muestran_el_webhook_configurado(): void
    {
        Integration::query()->where('name', 'MercadoPago')->firstOrFail()
            ->auths()->where('field_name', 'webhook_secret')->update(['field_value' => 'whsec_listo']);
        $this->app->forgetInstance(IntegrationCredentialResolver::class);

        $this->actingAs($this->user)
            ->get(route('integrationhub_listado'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('mercadopagoWebhook.configured', true)
                ->where('mercadopagoWebhook.source', 'integrationhub'));

        $this->actingAs($this->user)
            ->get(route('aca_subscriptions_list'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('mercadopagoWebhook.configured', true));
    }
}
