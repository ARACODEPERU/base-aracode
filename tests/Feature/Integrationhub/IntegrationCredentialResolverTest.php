<?php

namespace Tests\Feature\Integrationhub;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;
use Tests\TestCase;

/**
 * Lectura de credenciales administradas desde IntegrationHub.
 */
class IntegrationCredentialResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resuelve_el_valor_guardado_en_la_integracion(): void
    {
        $integracion = Integration::create([
            'name' => 'ProveedorPrueba',
            'url_base' => 'https://api.example.com',
            'execution_type' => 'manual',
            'is_active' => true,
        ]);

        $integracion->auths()->create([
            'field_name' => 'webhook_secret',
            'field_value' => 'secreto-del-hub',
            'field_type' => 'password',
            'auth_location' => 'header',
            'is_enabled' => true,
        ]);

        $this->assertSame(
            'secreto-del-hub',
            app(IntegrationCredentialResolver::class)->value('ProveedorPrueba', 'webhook_secret', 'desde-env')
        );
    }

    public function test_usa_el_respaldo_cuando_la_integracion_no_tiene_valor(): void
    {
        // La migración de Fase 1 deja MercadoPago · webhook_secret vacío.
        $this->assertDatabaseHas('integrations', ['name' => 'MercadoPago']);

        $this->assertSame(
            'desde-env',
            app(IntegrationCredentialResolver::class)->value('MercadoPago', 'webhook_secret', 'desde-env')
        );
    }

    public function test_no_usa_campos_deshabilitados(): void
    {
        $integracion = Integration::create([
            'name' => 'ProveedorApagado',
            'url_base' => 'https://api.example.com',
            'execution_type' => 'manual',
            'is_active' => true,
        ]);

        $integracion->auths()->create([
            'field_name' => 'webhook_secret',
            'field_value' => 'no-debe-usarse',
            'field_type' => 'password',
            'auth_location' => 'header',
            'is_enabled' => false,
        ]);

        $this->assertSame(
            'desde-env',
            app(IntegrationCredentialResolver::class)->value('ProveedorApagado', 'webhook_secret', 'desde-env')
        );
    }

    public function test_sin_integracion_devuelve_null(): void
    {
        $this->assertNull(app(IntegrationCredentialResolver::class)->value('NoExiste', 'webhook_secret'));
    }
}
