<?php

namespace Tests\Feature\Sire;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Sales\Entities\SireSubmission;
use Modules\Sales\Entities\SireSubmissionLog;
use Modules\Sales\Services\Sire\SireApiException;
use Modules\Sales\Services\Sire\SirePropuestaService;
use Tests\TestCase;

/**
 * Descarga de la propuesta del SIRE: exportar, consultar el ticket hasta `06`,
 * bajar el archivo (TXT o ZIP) y guardarlo con su hash y su bitácora.
 *
 * El SIRE no tiene ambiente de pruebas: todo el flujo se prueba con
 * `Http::fake` y fixtures, sin tocar SUNAT.
 */
class SirePropuestaTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20611376031';

    private const TEXTO = "20611376031|ARACODE SMART SOLUTIONS SAC|202611| |18/11/2026| |01|F001|1| |6|20123456789|CLIENTE SAC|0.00|84.75|0.00|15.25|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|100.00|PEN| | | | | |\r\n"
        . "20611376031|ARACODE SMART SOLUTIONS SAC|202611| |18/11/2026| |01|F001|2| |6|20123456789|OTRO SAC|0.00|42.37|0.00|7.63|0.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|50.00|PEN| | | | | |\r\n";

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

    public function test_descarga_la_propuesta_de_ventas_tras_consultar_el_ticket(): void
    {
        Http::fake($this->fakes('ventas'));

        $resultado = $this->service()->descargar($this->company, 'ventas', '2026-11', null, 3, 0);

        $this->assertTrue($resultado['descargado']);
        $this->assertSame('T-123', $resultado['ticket']);
        $this->assertSame('06', $resultado['estado']);
        $this->assertSame(2, $resultado['lineas']);

        $submission = SireSubmission::findOrFail($resultado['submission_id']);

        $this->assertSame('terminado', $submission->status);
        $this->assertSame('ventas', $submission->tipo);
        $this->assertSame('202611', $submission->period);
        $this->assertSame(hash('sha256', self::TEXTO), $submission->file_hash);
        $this->assertSame(2, $submission->lines);
        $this->assertSame(2, $submission->attempts); // una consulta con 05 y otra con 06
        Storage::disk('local')->assertExists($submission->file_path);
        $this->assertSame(self::TEXTO, Storage::disk('local')->get($submission->file_path));

        // Exportación, dos consultas de estado y la descarga, todas en bitácora.
        $this->assertSame(1, SireSubmissionLog::where('action', 'exportar')->where('success', true)->count());
        $this->assertSame(2, SireSubmissionLog::where('action', 'ticket')->count());
        $this->assertSame(1, SireSubmissionLog::where('action', 'descarga')->where('success', true)->count());

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'exportapropuesta')
            && str_contains($request->url(), 'codTipoArchivo=0'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'archivoreporte')
            && str_contains($request->url(), 'codLibro=140000'));
    }

    public function test_compras_usa_su_endpoint_y_marca_el_origen_del_envio(): void
    {
        Http::fake($this->fakes('compras'));

        $resultado = $this->service()->descargar($this->company, 'compras', '2026-11', null, 3, 0);

        $this->assertTrue($resultado['descargado']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'exportacioncomprobantepropuesta')
            && str_contains($request->url(), 'codOrigenEnvio=2'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'archivoreporte')
            && str_contains($request->url(), 'codLibro=080000'));
    }

    public function test_extrae_el_txt_cuando_sunat_entrega_un_zip(): void
    {
        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*exportapropuesta*' => Http::response(['numTicket' => 'T-ZIP']),
            '*consultaestadotickets*' => Http::response([
                'estado' => '06',
                'nomArchivoReporte' => 'P202611.zip',
                'codTipoArchivoReporte' => '1',
                'codProceso' => '3',
            ]),
            '*archivoreporte*' => Http::response($this->zip(self::TEXTO), 200, [
                'Content-Type' => 'application/zip',
            ]),
        ]);

        $resultado = $this->service()->descargar($this->company, 'ventas', '2026-11', null, 1, 0);

        $this->assertTrue($resultado['descargado']);
        $this->assertSame(2, $resultado['lineas']);

        $submission = SireSubmission::findOrFail($resultado['submission_id']);
        $this->assertSame(self::TEXTO, Storage::disk('local')->get($submission->file_path));
    }

    public function test_un_ticket_expirado_se_informa_y_no_descarga(): void
    {
        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*exportapropuesta*' => Http::response(['numTicket' => 'T-EXP']),
            '*consultaestadotickets*' => Http::response(['estado' => 'EXP']),
        ]);

        try {
            $this->service()->descargar($this->company, 'ventas', '2026-11', null, 2, 0);
            $this->fail('Se esperaba una excepción por ticket expirado.');
        } catch (SireApiException $e) {
            $this->assertStringContainsString('expiró', $e->getMessage());
            $this->assertSame('T-EXP', $e->ticket());
        }

        $submission = SireSubmission::query()->latest('id')->firstOrFail();

        $this->assertSame('expirado', $submission->status);
        $this->assertNull($submission->file_path);
        $this->assertSame(0, SireSubmissionLog::where('action', 'descarga')->count());
    }

    public function test_si_el_ticket_sigue_en_proceso_devuelve_el_numero_sin_error(): void
    {
        Http::fake([
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*exportapropuesta*' => Http::response(['numTicket' => 'T-05']),
            '*consultaestadotickets*' => Http::response(['estado' => '05']),
        ]);

        $resultado = $this->service()->descargar($this->company, 'ventas', '2026-11', null, 2, 0);

        $this->assertFalse($resultado['descargado']);
        $this->assertSame('T-05', $resultado['ticket']);
        $this->assertSame('En proceso', $resultado['estado_etiqueta']);
        $this->assertNull($resultado['archivo']);

        $submission = SireSubmission::findOrFail($resultado['submission_id']);
        $this->assertSame('en_proceso', $submission->status);
        $this->assertSame(2, $submission->attempts);
    }

    public function test_sin_credenciales_no_crea_proceso_ni_llama_a_sunat(): void
    {
        $this->company->sire_sol_key = null;
        $this->company->save();

        try {
            $this->service()->descargar($this->company, 'ventas', '2026-11', null, 1, 0);
            $this->fail('Se esperaba el error de credenciales.');
        } catch (SireApiException $e) {
            $this->assertStringContainsString('Faltan las credenciales', $e->getMessage());
        }

        $this->assertSame(0, SireSubmission::count());
        Http::assertNothingSent();
    }

    /**
     * @return array<string, mixed>
     */
    private function fakes(string $tipo): array
    {
        $exportacion = $tipo === 'compras' ? '*exportacioncomprobantepropuesta*' : '*exportapropuesta*';

        return [
            'api-seguridad.sunat.gob.pe/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            $exportacion => Http::response(['numTicket' => 'T-123']),
            '*consultaestadotickets*' => Http::sequence()
                ->push(['estado' => '05'])
                ->push([
                    'estado' => '06',
                    'nomArchivoReporte' => 'P202611.zip',
                    'codTipoArchivoReporte' => '1',
                    'codProceso' => $tipo === 'compras' ? '61' : '3',
                ]),
            '*archivoreporte*' => Http::response(self::TEXTO, 200, ['Content-Type' => 'text/plain']),
        ];
    }

    private function zip(string $texto): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'sire_test_') . '.zip';

        $zip = new \ZipArchive();
        $zip->open($ruta, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('P202611.TXT', $texto);
        $zip->close();

        $contenido = (string) file_get_contents($ruta);
        @unlink($ruta);

        return $contenido;
    }

    private function service(): SirePropuestaService
    {
        return app(SirePropuestaService::class);
    }
}
