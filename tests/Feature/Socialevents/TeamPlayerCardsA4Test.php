<?php

namespace Tests\Feature\Socialevents;

use App\Models\IdentityDocumentType;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Impresión masiva de carnets (fotochets) de un equipo en un PDF A4.
 *
 * Fija que solo se imprimen jugadores del equipo indicado, que la selección
 * vacía se rechaza y que el PDF se guarda en temp_pdfs y se devuelve por URL.
 */
class TeamPlayerCardsA4Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private EventEdition $edition;

    private EventTeam $team;

    private EventTeam $otroEquipo;

    private Person $jugador;

    private Person $jugadorAjeno;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Modo deportivo activo: PW00001 = 2 (torneos).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $organizacion = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'read_permissions' => json_encode(['teams']),
            'match_rate' => 5,
            'prepaid_balance' => 100,
        ]);

        $rol = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);
        $rol->givePermissionTo(Permission::firstOrCreate(['name' => 'even_ediciones_equipo_jugadores', 'guard_name' => 'web']));

        $this->user = User::factory()->create([
            'socialevent_organization_id' => $organizacion->id,
            'socialevent_organization_role' => 'owner',
        ]);
        $this->user->assignRole($rol);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        IdentityDocumentType::firstOrCreate(
            ['id' => '1'],
            ['description' => 'DNI', 'long_description' => 'Documento nacional de identidad', 'sunat_code' => '1', 'number_characters' => 8],
        );

        $evento = EvenEvent::create(['title' => 'Torneo de prueba', 'organization_id' => $organizacion->id]);
        $this->edition = EventEdition::create([
            'event_id' => $evento->id,
            'year' => 2026,
            'name' => 'Edición 2026',
            'start_date' => '2026-01-10',
        ]);

        $this->team = EventTeam::create(['organization_id' => $organizacion->id, 'name' => 'Equipo Rojo', 'status' => 1]);
        $this->otroEquipo = EventTeam::create(['organization_id' => $organizacion->id, 'name' => 'Equipo Azul', 'status' => 1]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->team->id]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->otroEquipo->id]);

        $this->jugador = $this->crearJugador('Juan Carlos', $this->team, '12345678');
        $this->jugadorAjeno = $this->crearJugador('Pedro', $this->otroEquipo, '87654321');
    }

    private function crearJugador(string $nombres, EventTeam $equipo, string $numero): Person
    {
        $persona = Person::create([
            'names' => $nombres,
            'father_lastname' => 'Pérez',
            'mother_lastname' => 'Gómez',
            'full_name' => $nombres . ' Pérez Gómez',
            'document_type_id' => 1,
            'number' => $numero,
        ]);
        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $equipo->id,
            'person_id' => $persona->id,
            'registration_date' => '2026-01-11',
        ]);

        return $persona;
    }

    private function url(): string
    {
        return "/socialevents/editions/{$this->edition->id}/teams/{$this->team->id}/players/pdf-cards";
    }

    public function test_genera_el_pdf_a4_con_los_jugadores_seleccionados(): void
    {
        $respuesta = $this->actingAs($this->user)
            ->postJson($this->url(), ['player_ids' => [$this->jugador->id]])
            ->assertOk()
            ->assertJsonStructure(['url']);

        // El PDF queda guardado en temp_pdfs del disco público.
        $archivos = Storage::disk('public')->files('temp_pdfs');
        $this->assertCount(1, $archivos);
        $this->assertStringStartsWith('fotochets_equipo_', basename($archivos[0]));
    }

    public function test_rechaza_una_seleccion_vacia(): void
    {
        $this->actingAs($this->user)
            ->postJson($this->url(), ['player_ids' => []])
            ->assertStatus(422);
    }

    public function test_no_imprime_jugadores_de_otro_equipo(): void
    {
        // Solo el jugador ajeno está seleccionado: no pertenece al equipo indicado.
        $this->actingAs($this->user)
            ->postJson($this->url(), ['player_ids' => [$this->jugadorAjeno->id]])
            ->assertStatus(422);
    }
}
