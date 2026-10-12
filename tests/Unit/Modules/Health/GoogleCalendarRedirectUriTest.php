<?php

namespace Tests\Unit\Modules\Health;

use Illuminate\Support\Facades\URL;
use Modules\Health\Services\GoogleCalendarService;
use Modules\Health\Services\GoogleCalendarSyncService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guardia de la URL de callback de Google Calendar.
 *
 * Google solo admite http:// en localhost y 127.0.0.1: en cualquier otro
 * dominio exige HTTPS. Cuando la URL no sirve, el consentimiento termina en la
 * pantalla "Acceso bloqueado - Error 400: redirect_uri_mismatch" y el usuario
 * nunca vuelve al sistema, asi que hay que detectarlo ANTES de mandarlo a
 * Google (es el caso de APP_URL=http://base_aracode.test en desarrollo).
 */
class GoogleCalendarRedirectUriTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceRootUrl(null);
        URL::forceScheme(null);

        parent::tearDown();
    }

    public function test_una_url_https_sirve(): void
    {
        $this->assertNull($this->problema('https://clinica.test'));
        $this->assertNull($this->problema('https://www.clinica.test'));
    }

    public function test_http_en_un_dominio_propio_se_detecta_y_se_explica(): void
    {
        $problem = $this->problema('http://base_aracode.test');

        $this->assertIsString($problem);
        $this->assertStringContainsString('redirect_uri_mismatch', $problem);
        $this->assertStringContainsString('http://base_aracode.test/health/google-calendar/callback', $problem);
        $this->assertStringContainsString('HTTPS', $problem);
    }

    public function test_localhost_y_loopback_son_la_excepcion_que_google_admite(): void
    {
        $this->assertNull($this->problema('http://localhost'));
        $this->assertNull($this->problema('http://localhost:8000'));
        $this->assertNull($this->problema('http://127.0.0.1:8000'));
    }

    public function test_el_boton_probar_conexion_reporta_este_problema(): void
    {
        URL::forceRootUrl('http://base_aracode.test');
        URL::forceScheme('http');

        $check = $this->comprobacionDelDiagnostico();

        $this->assertSame('redirect_uri', $check['code']);
        $this->assertSame('URL de callback', $check['label']);
        $this->assertFalse($check['ok'], 'con http:// en un dominio propio la comprobacion tiene que fallar');
        $this->assertStringContainsString('redirect_uri_mismatch', $check['message']);
        $this->assertNotNull($check['hint']);

        URL::forceRootUrl('https://clinica.test');
        URL::forceScheme('https');

        $check = $this->comprobacionDelDiagnostico();

        $this->assertTrue($check['ok'], 'con https:// la comprobacion tiene que pasar');
        $this->assertStringContainsString('https://clinica.test/health/google-calendar/callback', $check['message']);
        $this->assertNull($check['hint'], 'cuando todo esta bien no se muestra sugerencia');
    }

    /**
     * Comprobacion del diagnostico que alimenta el boton "Probar conexión".
     *
     * @return array{code: string, label: string, ok: bool, message: string, hint: ?string}
     */
    private function comprobacionDelDiagnostico(): array
    {
        $sync = app(GoogleCalendarSyncService::class);
        $method = new ReflectionMethod($sync, 'redirectUriCheck');

        return $method->invoke($sync);
    }

    /**
     * Fuerza la URL del sistema y devuelve el problema detectado (null si sirve).
     */
    private function problema(string $root): ?string
    {
        URL::forceRootUrl($root);
        URL::forceScheme(str_starts_with($root, 'https://') ? 'https' : 'http');

        return app(GoogleCalendarService::class)->redirectUriProblem();
    }
}
