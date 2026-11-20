<?php

namespace Tests\Feature\Health;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Support\HealthAudit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La auditoría de Salud debe ser verificable: cada fila deja una huella
 * encadenada con la anterior y el comando de verificación detecta cambios.
 */
class HealthAuditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $usuario = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($usuario);
    }

    public function test_cada_entrada_nueva_queda_encadenada_y_verificable(): void
    {
        $primera = HealthAudit::record('created', [
            'subject_type' => HealActivity::class,
            'subject_id' => 1,
            'metadata' => ['subject_name' => 'Prueba 1'],
        ]);

        $segunda = HealthAudit::record('updated', [
            'subject_type' => HealActivity::class,
            'subject_id' => 1,
            'metadata' => ['subject_name' => 'Prueba 2'],
        ]);

        $this->assertNotNull($primera->entry_hash);
        $this->assertSame($primera->entry_hash, $segunda->previous_hash);
        $this->assertNotSame($primera->entry_hash, $segunda->entry_hash);
        $this->assertNotNull($segunda->request_id);
        $this->assertSame('GET', $segunda->http_method);

        $this->artisan('health:audit:verify')->assertExitCode(0);
    }

    public function test_el_verificador_detecta_una_fila_alterada(): void
    {
        $actividad = HealthAudit::record('created', [
            'subject_type' => HealActivity::class,
            'subject_id' => 10,
            'metadata' => ['subject_name' => 'Original'],
        ]);

        // Se altera la fila saltándose el modelo (como haría alguien con SQL).
        DB::table('heal_activities')
            ->where('id', $actividad->id)
            ->update(['metadata' => json_encode(['subject_name' => 'Cambiado'])]);

        $this->artisan('health:audit:verify')->assertExitCode(1);
    }

    public function test_los_datos_sensibles_no_se_guardan_en_la_auditoria(): void
    {
        $actividad = HealthAudit::record('updated', [
            'subject_type' => HealActivity::class,
            'subject_id' => 5,
            'metadata' => [
                'password' => 'secreto',
                'signature_pin_hash' => '$2y$10$hash',
                'access_token' => 'token',
                'campo_visible' => 'valor',
            ],
        ]);

        $metadata = $actividad->fresh()->metadata;

        $this->assertArrayNotHasKey('password', $metadata);
        $this->assertArrayNotHasKey('signature_pin_hash', $metadata);
        $this->assertArrayNotHasKey('access_token', $metadata);
        $this->assertSame('valor', $metadata['campo_visible']);
    }

    public function test_el_registro_no_se_puede_modificar_ni_borrar(): void
    {
        $actividad = HealthAudit::record('created', [
            'subject_type' => HealActivity::class,
            'subject_id' => 20,
            'metadata' => ['subject_name' => 'Inmutable'],
        ]);

        $this->expectException(\RuntimeException::class);
        $actividad->update(['activity_type' => 'deleted']);
    }
}
