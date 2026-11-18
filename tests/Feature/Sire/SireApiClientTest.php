<?php

namespace Tests\Feature\Sire;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Sales\Entities\SireSubmissionLog;
use Modules\Sales\Services\Sire\SireApiClient;
use Modules\Sales\Services\Sire\SireApiException;
use Tests\TestCase;

/**
 * Cliente de token del SIRE: se cachea entre llamadas, se renueva ante un 401 y
 * cada llamada queda en la bitácora sin guardar secretos.
 */
class SireApiClientTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20611376031';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_el_token_se_pide_una_vez_y_se_reutiliza(): void
    {
        $company = $this->company();

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response([
                'access_token' => 'token-1',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
        ]);

        $client = app(SireApiClient::class);

        $this->assertSame('token-1', $client->token($company));
        $this->assertSame('token-1', $client->token($company));

        Http::assertSentCount(1);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/oauth2/token/')
            && $request['grant_type'] === 'password'
            && $request['scope'] === SireApiClient::SCOPE
            && $request['username'] === self::RUC . 'USUARIO1'
            && $request['client_id'] === 'cliente-prueba');

        $this->assertSame(1, SireSubmissionLog::where('action', 'token')->where('success', true)->count());
    }

    public function test_un_401_renueva_el_token_y_repite_la_llamada(): void
    {
        $company = $this->company();

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::sequence()
                ->push(['access_token' => 'token-viejo', 'expires_in' => 3600])
                ->push(['access_token' => 'token-nuevo', 'expires_in' => 3600]),
            'api-sire.sunat.gob.pe/*' => Http::sequence()
                ->push(['message' => 'Token inválido'], 401)
                ->push(['ok' => true], 200),
        ]);

        $datos = app(SireApiClient::class)->get($company, '/v1/prueba', [], 'consulta');

        $this->assertSame(['ok' => true], $datos);
        $this->assertSame(2, SireSubmissionLog::where('action', 'token')->count());
        $this->assertSame(2, SireSubmissionLog::where('action', 'consulta')->count());
        $this->assertFalse(SireSubmissionLog::where('action', 'consulta')->first()->success);
        $this->assertSame(401, SireSubmissionLog::where('action', 'consulta')->first()->http_status);

        // El segundo intento usa el token renovado.
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api-sire')
            && $request->hasHeader('Authorization', 'Bearer token-nuevo'));
    }

    public function test_expone_el_mensaje_de_sunat_cuando_rechaza_las_credenciales(): void
    {
        $company = $this->company();

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'El client_id no está autorizado',
            ], 400),
        ]);

        $this->expectException(SireApiException::class);
        $this->expectExceptionMessage('El client_id no está autorizado');

        app(SireApiClient::class)->token($company);
    }

    public function test_sin_credenciales_completas_falla_con_un_mensaje_claro(): void
    {
        $company = $this->company(['sire_sol_key' => null]);

        $this->expectException(SireApiException::class);
        $this->expectExceptionMessage('Faltan las credenciales del SIRE');

        app(SireApiClient::class)->token($company);
    }

    public function test_las_credenciales_quedan_cifradas_en_la_base_de_datos(): void
    {
        $company = $this->company();

        $guardado = (string) DB::table('companies')->where('id', $company->id)->value('sire_client_secret');

        $this->assertNotSame('secreto-prueba', $guardado);
        $this->assertStringNotContainsString('secreto-prueba', $guardado);
        $this->assertSame('secreto-prueba', $company->fresh()->sire_client_secret);
        $this->assertSame('clave-sol', $company->fresh()->sire_sol_key);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function company(array $attributes = []): Company
    {
        return Company::create(array_merge([
            'ruc' => self::RUC,
            'name' => 'ARACODE',
            'business_name' => 'ARACODE SMART SOLUTIONS SAC',
            'tradename' => 'ARACODE',
            'fiscal_address' => 'Av. Los Álamos 123',
            'mode' => 'demo',
            'sire_mode' => 'api',
            'sire_client_id' => 'cliente-prueba',
            'sire_client_secret' => 'secreto-prueba',
            'sire_sol_user' => 'USUARIO1',
            'sire_sol_key' => 'clave-sol',
        ], $attributes));
    }
}
