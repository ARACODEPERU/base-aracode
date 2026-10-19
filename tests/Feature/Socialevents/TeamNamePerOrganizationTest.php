<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * El nombre del equipo es único dentro de cada organización, no en todo el sistema.
 */
class TeamNamePerOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );
    }

    private function organizador(string $nombre): User
    {
        $organizacion = SocialeventOrganization::create([
            'name' => $nombre,
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
        ]);

        $user = User::factory()->create(['socialevent_organization_id' => $organizacion->id]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'even_equipos_nuevo', 'guard_name' => 'web']));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function registrar(User $user, string $nombre)
    {
        return $this->actingAs($user)->post('/socialevents/teams/store', [
            'name' => $nombre,
            'short_name' => 'TOC',
        ]);
    }

    public function test_dos_organizaciones_pueden_registrar_el_mismo_nombre(): void
    {
        $primera = $this->organizador('Organización A');
        $segunda = $this->organizador('Organización B');

        $this->registrar($primera, 'Treinta y Ocho')->assertRedirect();
        $this->registrar($segunda, 'Treinta y Ocho')->assertRedirect();

        $this->assertSame(2, EventTeam::where('name', 'Treinta y Ocho')->count());
    }

    public function test_la_misma_organizacion_no_repite_el_nombre_y_muestra_un_error_claro(): void
    {
        $organizador = $this->organizador('Organización A');
        $this->registrar($organizador, 'Treinta y Ocho')->assertRedirect();

        $this->registrar($organizador, 'Treinta y Ocho')->assertSessionHasErrors(['name']);

        $this->assertSame(1, EventTeam::where('name', 'Treinta y Ocho')->count());
    }
}
