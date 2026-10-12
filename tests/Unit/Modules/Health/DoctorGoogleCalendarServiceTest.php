<?php

namespace Tests\Unit\Modules\Health;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Health\Entities\HealDoctorGoogleAccount;
use Modules\Health\Entities\HealSetting;
use Modules\Health\Services\DoctorGoogleCalendarService;
use Modules\Health\Support\GoogleCalendarOptions;
use Tests\TestCase;

/**
 * Contrato del enlace de la agenda de Google de cada doctor.
 *
 * Usa sqlite en memoria con la migración real de heal_doctor_google_accounts.
 * La revocación de Google se simula con Http::fake: no sale nada a la red.
 */
class DoctorGoogleCalendarServiceTest extends TestCase
{
    private DoctorGoogleCalendarService $doctors;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('heal_doctor_google_accounts');
        Schema::dropIfExists('heal_settings');
        Schema::dropIfExists('heal_activities');
        Schema::dropIfExists('heal_doctors');

        Schema::create('heal_doctors', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_code')->nullable();
        });

        Schema::create('heal_settings', function (Blueprint $table) {
            $table->id();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // HealSetting registra su actividad (LogsActivity): la tabla debe existir aunque no se pruebe.
        Schema::create('heal_activities', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('actor_person_id')->nullable();
            $table->string('actor_display')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('patient_display')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // La migración real, no una copia: si cambia la tabla, la prueba lo nota.
        $migration = require base_path('Modules/Health/Database/Migrations/2026_10_12_000001_create_heal_doctor_google_accounts_table.php');
        $migration->up();

        DB::table('heal_doctors')->insert([
            ['id' => 1, 'doctor_code' => 'D1'],
            ['id' => 2, 'doctor_code' => 'D2'],
        ]);

        $this->doctors = app(DoctorGoogleCalendarService::class);
    }

    public function test_el_doctor_sin_cuenta_no_tiene_agenda_activa(): void
    {
        $this->assertNull($this->doctors->activeAccount(1));
        $this->assertFalse($this->doctors->status(1)['connected']);
    }

    public function test_el_token_se_guarda_cifrado_y_nunca_sale_en_el_estado(): void
    {
        $this->enlazar(1, 'doctor1@clinica.test', 'refresh-secreto');

        $raw = DB::table('heal_doctor_google_accounts')->where('doctor_id', 1)->value('refresh_token');
        $this->assertNotSame('refresh-secreto', $raw, 'el refresh token no puede quedar en texto plano');

        $status = $this->doctors->status(1);
        $this->assertTrue($status['connected']);
        $this->assertSame('doctor1@clinica.test', $status['email']);
        $this->assertArrayNotHasKey('refresh_token', $status);
        $this->assertStringNotContainsString('refresh-secreto', json_encode($status));

        // Solo el servicio de envio (no la pantalla) recibe el token descifrado.
        $account = $this->doctors->activeAccount(1);
        $this->assertSame('refresh-secreto', $account->refreshToken);
        $this->assertSame('doctor1@clinica.test', $account->calendarId);
        $this->assertSame(1, $account->doctorId);
        $this->assertFalse($account->isPrincipal());
    }

    public function test_un_calendario_no_puede_quedar_enlazado_a_dos_doctores(): void
    {
        $this->enlazar(1, 'compartido@clinica.test', 'tok-1');

        $this->expectException(QueryException::class);

        $this->enlazar(2, 'compartido@clinica.test', 'tok-2');
    }

    public function test_la_busqueda_por_calendario_solo_devuelve_cuentas_activas(): void
    {
        $this->enlazar(1, 'a@clinica.test', 'tok');

        $this->assertSame(1, $this->doctors->accountForCalendar('a@clinica.test')->doctorId);
        $this->assertNull($this->doctors->accountForCalendar('otro@clinica.test'));

        HealDoctorGoogleAccount::where('doctor_id', 1)->update(['is_active' => false]);

        $this->assertNull($this->doctors->accountForCalendar('a@clinica.test'));
        $this->assertNull($this->doctors->activeAccount(1));
    }

    public function test_desenlazar_revoca_el_permiso_y_borra_la_cuenta(): void
    {
        Http::fake(['*' => Http::response('', 200)]);
        $this->enlazar(1, 'a@clinica.test', 'tok');

        $this->assertNull($this->doctors->disconnect(1));

        $this->assertNull($this->doctors->accountFor(1));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'revoke'));
    }

    public function test_el_resultado_del_envio_queda_registrado_y_se_limpia_al_funcionar(): void
    {
        $this->enlazar(1, 'a@clinica.test', 'tok');

        $this->doctors->recordSync(1, 'Google rechazó el evento');
        $this->assertSame('Google rechazó el evento', $this->doctors->status(1)['lastError']);

        $this->doctors->recordSync(1, null);
        $this->assertNull($this->doctors->status(1)['lastError']);
        $this->assertNotNull($this->doctors->status(1)['lastSyncAt']);
    }

    public function test_las_opciones_tienen_valores_por_defecto_y_acotan_los_minutos(): void
    {
        HealSetting::create(['settings' => ['colors' => ['primary' => '#000000']]]);

        $this->assertSame(
            ['allow_doctor_connection' => true, 'principal_fallback' => true, 'confirm_minutes' => 5],
            GoogleCalendarOptions::read()
        );

        GoogleCalendarOptions::save([
            'allow_doctor_connection' => false,
            'principal_fallback' => false,
            'confirm_minutes' => 99,
        ]);

        $saved = GoogleCalendarOptions::read();
        $this->assertFalse($saved['allow_doctor_connection']);
        $this->assertFalse($saved['principal_fallback']);
        $this->assertSame(30, $saved['confirm_minutes'], 'el maximo son 30 minutos');

        $settings = HealSetting::first()->settings;
        $this->assertSame('#000000', $settings['colors']['primary'], 'guardar opciones no pisa otros ajustes');

        GoogleCalendarOptions::save([
            'allow_doctor_connection' => true,
            'principal_fallback' => true,
            'confirm_minutes' => 0,
        ]);

        $this->assertSame(1, GoogleCalendarOptions::read()['confirm_minutes'], 'el minimo es 1 minuto');
    }

    private function enlazar(int $doctorId, string $calendarId, string $token): void
    {
        HealDoctorGoogleAccount::create([
            'doctor_id' => $doctorId,
            'calendar_id' => $calendarId,
            'refresh_token' => $token,
            'account_email' => $calendarId,
            'is_active' => true,
            'connected_at' => now(),
        ]);
    }
}
