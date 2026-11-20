<?php

namespace Tests\Feature\Sire;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Sales\Entities\SireSubmission;
use Modules\Sales\Services\Sire\SireApiException;
use Modules\Sales\Services\Sire\SirePresentacionService;
use Tests\TestCase;

/**
 * Presentación del SIRE por API: comparar, reemplazar (TUS y envío directo) y
 * aceptar la propuesta.
 *
 * El SIRE no tiene ambiente de pruebas: todo se prueba con `Http::fake` y
 * archivos en un disco falso, sin tocar SUNAT.
 */
class SirePresentacionTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20611376031';

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
        Storage::fake('local');

        $this->company = Company::create([
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
        ]);
    }

    public function test_comparar_descarga_la_propuesta_y_coincide_con_un_periodo_vacio(): void
    {
        Http::fake($this->fakesDescarga(''));

        $resultado = $this->servicio()->comparar($this->company, 'ventas', '2026-11');

        $this->assertTrue($resultado['ok']);
        $this->assertSame(0, $resultado['resumen']['erp_lineas']);
        $this->assertSame(0, $resultado['resumen']['sunat_lineas']);
        $this->assertStringContainsString('no tiene operaciones', $resultado['message']);
    }

    public function test_comparar_bloquea_cuando_el_erp_tiene_errores(): void
    {
        // Empresa sin RUC válido: el validador bloquea el archivo.
        $this->company->ruc = '123';
        $this->company->save();

        $resultado = $this->servicio()->comparar($this->company, 'ventas', '2026-11');

        $this->assertTrue($resultado['bloqueado']);
        $this->assertFalse($resultado['ok']);
        $this->assertNotEmpty($resultado['errors']);
        Http::assertNothingSent();
    }

    public function test_reemplazar_envia_el_zip_por_tus_y_guarda_el_ticket(): void
    {
        $this->propuestaGuardada('');

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*/propuesta/upload' => Http::response('', 201, [
                'Location' => 'https://api-sire.sunat.gob.pe/upload/abc',
            ]),
            '*/upload/abc' => Http::response(['numTicket' => 'T-REP'], 200),
            '*consultaestadotickets*' => Http::response(['estado' => '06']),
        ]);

        $resultado = $this->servicio()->reemplazar($this->company, 'ventas', '2026-11');

        $this->assertTrue($resultado['ok']);
        $this->assertTrue($resultado['procesado']);
        $this->assertSame('T-REP', $resultado['ticket']);
        $this->assertSame('06', $resultado['estado']);

        $submission = SireSubmission::query()->where('action', 'reemplazar')->latest('id')->firstOrFail();

        $this->assertSame('terminado', $submission->status);
        $this->assertSame('T-REP', $submission->ticket);
        $this->assertSame('ventas', $submission->tipo);
        $this->assertSame('202611', $submission->period);
        $this->assertNotNull($submission->file_hash);

        // Alta TUS con longitud y metadatos, luego el archivo por PATCH.
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'propuesta/upload')
            && $request->hasHeader('Tus-Resumable', '1.0.0')
            && $request->hasHeader('Upload-Length'));

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'upload/abc')
            && $request->method() === 'PATCH'
            && str_contains($request->body(), 'PK'));
    }

    public function test_si_el_upload_no_devuelve_location_envia_el_zip_en_el_post(): void
    {
        $this->propuestaGuardada('');

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*/propuesta/upload' => Http::response(['numTicket' => 'T-DIR'], 200),
            '*consultaestadotickets*' => Http::response(['estado' => '06']),
        ]);

        $resultado = $this->servicio()->reemplazar($this->company, 'compras', '2026-11');

        $this->assertTrue($resultado['ok']);
        $this->assertSame('T-DIR', $resultado['ticket']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'propuesta/upload')
            && str_contains($request->body(), 'PK'));
    }

    public function test_no_reemplaza_sin_confirmar_las_diferencias(): void
    {
        $this->propuestaGuardada($this->lineaVentas('F001', '1', '20123456789'));

        try {
            $this->servicio()->reemplazar($this->company, 'ventas', '2026-11');
            $this->fail('Se esperaba el bloqueo por diferencias.');
        } catch (SireApiException $e) {
            $this->assertStringContainsString('revisa la comparación', $e->getMessage());
            $this->assertSame(1, $e->payload()['resumen']['faltan_en_erp']);
        }

        $this->assertSame(0, SireSubmission::where('action', 'reemplazar')->count());

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*/propuesta/upload' => Http::response(['numTicket' => 'T-FORZADO'], 200),
            '*consultaestadotickets*' => Http::response(['estado' => '06']),
        ]);

        $resultado = $this->servicio()->reemplazar($this->company, 'ventas', '2026-11', true);

        $this->assertTrue($resultado['ok']);
        $this->assertSame('T-FORZADO', $resultado['ticket']);
    }

    public function test_aceptar_la_propuesta_guarda_el_ticket(): void
    {
        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*aceptarpropuesta*' => Http::response(['numTicket' => 'T-ACE'], 200),
            '*consultaestadotickets*' => Http::response(['estado' => '06']),
        ]);

        $resultado = $this->servicio()->aceptar($this->company, 'compras', '2026-11');

        $this->assertTrue($resultado['ok']);
        $this->assertTrue($resultado['procesado']);
        $this->assertSame('T-ACE', $resultado['ticket']);

        $submission = SireSubmission::query()->where('action', 'aceptar')->firstOrFail();

        $this->assertSame('terminado', $submission->status);
        $this->assertSame('compras', $submission->tipo);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'rce/propuesta/web/registroslibros/202611/aceptarpropuesta'));
    }

    public function test_consultar_el_estado_cierra_el_proceso(): void
    {
        $submission = SireSubmission::create([
            'company_id' => $this->company->id,
            'tipo' => 'ventas',
            'period' => '202611',
            'action' => 'reemplazar',
            'ticket' => 'T-ESTADO',
            'status' => 'en_proceso',
            'started_at' => now(),
        ]);

        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*consultaestadotickets*' => Http::response(['estado' => '06']),
        ]);

        $resultado = $this->servicio()->estado($this->company, $submission->id);

        $this->assertTrue($resultado['procesado']);
        $this->assertSame('terminado', $submission->fresh()->status);
        $this->assertNotNull($submission->fresh()->finished_at);
    }

    public function test_la_ruta_de_comparar_responde_por_http_con_el_tipo_de_la_ruta(): void
    {
        $usuario = $this->usuario(['sire_rvie_ventas']);

        Http::fake($this->fakesDescarga(''));

        $respuesta = $this->actingAs($usuario)->postJson(route('sire_rvie_ventas_comparar'), [
            'period' => '2026-11',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('success', true);
        $respuesta->assertJsonPath('tipo', 'ventas');
        $respuesta->assertJsonPath('resumen.erp_lineas', 0);
    }

    public function test_sin_permiso_sire_api_no_se_puede_reemplazar(): void
    {
        $usuario = $this->usuario(['sire_rvie_ventas']);

        $respuesta = $this->actingAs($usuario)->postJson(route('sire_rvie_ventas_reemplazar'), [
            'period' => '2026-11',
        ]);

        $respuesta->assertForbidden();
        $this->assertSame(0, SireSubmission::where('action', 'reemplazar')->count());
    }

    /**
     * @param  array<int, string>  $permisos
     */
    private function usuario(array $permisos): \App\Models\User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $usuario = \App\Models\User::factory()->create();

        foreach ($permisos as $permiso) {
            \Spatie\Permission\Models\Permission::findOrCreate($permiso, 'web');
            $usuario->givePermissionTo($permiso);
        }

        return $usuario;
    }

    /**
     * @return array<string, mixed>
     */
    private function fakesDescarga(string $texto): array
    {
        return [
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*exportapropuesta*' => Http::response(['numTicket' => 'T-EXP']),
            '*consultaestadotickets*' => Http::response([
                'estado' => '06',
                'nomArchivoReporte' => 'P202611.TXT',
                'codTipoArchivoReporte' => '0',
                'codProceso' => '3',
            ]),
            '*archivoreporte*' => Http::response($texto, 200, ['Content-Type' => 'text/plain']),
        ];
    }

    /**
     * Deja una propuesta ya descargada para que el reemplazo no consulte SUNAT
     * para obtenerla.
     */
    private function propuestaGuardada(string $texto): SireSubmission
    {
        $ruta = 'sire/' . $this->company->id . '/202611/ventas-propuesta-T-PROP.txt';

        Storage::disk('local')->put($ruta, $texto);

        return SireSubmission::create([
            'company_id' => $this->company->id,
            'tipo' => 'ventas',
            'period' => '202611',
            'action' => 'exportar',
            'status' => 'terminado',
            'ticket' => 'T-PROP',
            'file_name' => 'P202611.TXT',
            'file_path' => $ruta,
            'file_hash' => hash('sha256', $texto),
            'lines' => $texto === '' ? 0 : 1,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    private function lineaVentas(string $serie, string $numero, string $documento): string
    {
        $datos = array_fill(0, 33, '');
        $datos[0] = self::RUC;
        $datos[1] = 'ARACODE SMART SOLUTIONS SAC';
        $datos[2] = '202611';
        $datos[4] = '18/11/2026';
        $datos[6] = '01';
        $datos[7] = $serie;
        $datos[8] = $numero;
        $datos[10] = '6';
        $datos[11] = $documento;
        $datos[12] = 'CLIENTE PRUEBA SAC';
        $datos[14] = '84.75';
        $datos[16] = '15.25';
        $datos[25] = '100.00';
        $datos[26] = 'PEN';

        return implode('|', $datos) . "\r\n";
    }

    private function servicio(): SirePresentacionService
    {
        return app(SirePresentacionService::class);
    }
}
