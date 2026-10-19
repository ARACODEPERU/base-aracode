<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Un mismo jugador (mismo DNI) puede inscribirse en torneos distintos sin duplicar la persona.
 */
class PlayerReuseAcrossEditionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mismo_dni_se_reutiliza_al_registrarse_en_otra_edicion(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Sanctum::actingAs($user);

        $event = EvenEvent::create(['title' => 'Torneo de prueba']);

        $edicionA = EventEdition::create([
            'event_id' => $event->id, 'year' => 2026, 'name' => 'Edición A', 'start_date' => '2026-01-10',
        ]);
        $edicionB = EventEdition::create([
            'event_id' => $event->id, 'year' => 2026, 'name' => 'Edición B', 'start_date' => '2026-06-10',
        ]);

        $equipoA = EventTeam::create(['name' => 'Equipo A', 'short_name' => 'EQA']);
        $equipoB = EventTeam::create(['name' => 'Equipo B', 'short_name' => 'EQB']);
        EventEditionTeam::create(['edition_id' => $edicionA->id, 'team_id' => $equipoA->id]);
        EventEditionTeam::create(['edition_id' => $edicionB->id, 'team_id' => $equipoB->id]);

        $jugador = [
            'father_lastname' => 'Torres',
            'mother_lastname' => 'Lopez',
            'names' => 'Carlos',
            'dni' => '87772821',
            'gender' => 'M',
        ];

        $this->postJson("/api/socialevents/v1/admin/editions/{$edicionA->id}/teams/{$equipoA->id}/players", $jugador)
            ->assertCreated();

        $this->postJson("/api/socialevents/v1/admin/editions/{$edicionB->id}/teams/{$equipoB->id}/players", $jugador)
            ->assertCreated();

        // Una sola persona con ese DNI, inscrita en ambas ediciones.
        $this->assertSame(1, Person::where('number', '87772821')->count());
        $persona = Person::where('number', '87772821')->firstOrFail();
        $this->assertDatabaseHas('event_edition_team_players', ['edition_id' => $edicionA->id, 'person_id' => $persona->id]);
        $this->assertDatabaseHas('event_edition_team_players', ['edition_id' => $edicionB->id, 'person_id' => $persona->id]);
    }
}
