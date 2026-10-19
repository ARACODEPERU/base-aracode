<?php

namespace Tests\Unit\Modules\Health;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Health\Services\GoogleCalendarService;
use Modules\Health\Support\GoogleCalendarSyncTokenExpiredException;
use Tests\TestCase;

/**
 * Lectura de eventos de Google Calendar.
 *
 * Lo importante aquí es la autorecuperación: cuando el token de sincronización
 * caduca (HTTP 410 o motivo "fullSyncRequired"), el servicio tiene que avisar de
 * que ya no sirve para que la sincronización lo olvide y vuelva a leer la
 * ventana completa. Si no, el comando programado falla en cada corrida para
 * siempre. Nada sale a la red: Http::fake responde todo.
 */
class GoogleCalendarListEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Schema::dropIfExists('parameters');

        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->unique();
            $table->string('value_default')->nullable();
        });

        foreach ([
            'SC-00011' => 'client-id-de-prueba',
            'SC-00012' => 'client-secret-de-prueba',
            'SC-00013' => 'refresh-token-de-prueba',
            'SC-00014' => 'primary',
        ] as $code => $value) {
            DB::table('parameters')->insert(['parameter_code' => $code, 'value_default' => $value]);
        }

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'token-de-acceso',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    private function eventsUrl(): string
    {
        return 'https://www.googleapis.com/calendar/v3/calendars/primary/events*';
    }

    public function test_lee_los_eventos_cuando_google_responde_bien(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-de-acceso', 'expires_in' => 3600], 200),
            $this->eventsUrl() => Http::response(['items' => [['id' => 'evento-1']]], 200),
        ]);

        $page = app(GoogleCalendarService::class)->listEvents(['syncToken' => 'vigente']);

        $this->assertSame('evento-1', $page['items'][0]['id']);
    }

    public function test_un_token_caducado_por_http_410_avisa_que_hay_que_releer(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-de-acceso', 'expires_in' => 3600], 200),
            $this->eventsUrl() => Http::response(['error' => ['code' => 410, 'message' => 'Sync token is no longer valid']], 410),
        ]);

        $this->expectException(GoogleCalendarSyncTokenExpiredException::class);

        app(GoogleCalendarService::class)->listEvents(['syncToken' => 'caducado']);
    }

    public function test_el_motivo_full_sync_required_tambien_avisa_que_hay_que_releer(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-de-acceso', 'expires_in' => 3600], 200),
            $this->eventsUrl() => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'Sync token is no longer valid, a full sync is required.',
                    'errors' => [['domain' => 'global', 'reason' => 'fullSyncRequired']],
                ],
            ], 400),
        ]);

        $this->expectException(GoogleCalendarSyncTokenExpiredException::class);

        app(GoogleCalendarService::class)->listEvents(['syncToken' => 'caducado']);
    }

    public function test_un_error_real_de_google_no_se_confunde_con_el_token_caducado(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-de-acceso', 'expires_in' => 3600], 200),
            $this->eventsUrl() => Http::response(['error' => ['code' => 403, 'message' => 'Forbidden']], 403),
        ]);

        try {
            app(GoogleCalendarService::class)->listEvents(['syncToken' => 'vigente']);
            $this->fail('Debió lanzar el error de Google.');
        } catch (GoogleCalendarSyncTokenExpiredException $exception) {
            $this->fail('Un 403 no es un token caducado: no debe reintentarse la lectura completa.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 403', $exception->getMessage());
        }
    }
}
