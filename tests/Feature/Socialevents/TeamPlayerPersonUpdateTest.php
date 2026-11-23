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
 * Edición de los datos personales de un jugador desde Ediciones → Equipos → Jugadores.
 *
 * Fija que el servidor recalcula full_name, rechaza datos incompletos, no permite
 * editar a un jugador ajeno al equipo y exige el permiso de jugadores.
 */
class TeamPlayerPersonUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/socialevents/editions/teams/player/person/update';

    private SocialeventOrganization $organization;

    private User $user;

    private EventEdition $edition;

    private EventTeam $team;

    private Person $player;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo deportivo activo: PW00001 = 2 (torneos).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        // Organización con prueba vigente, para que pueda escribir.
        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'read_permissions' => json_encode(['teams']),
            'match_rate' => 5,
            'prepaid_balance' => 100,
        ]);

        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'even_ediciones_equipo_jugadores', 'guard_name' => 'web']));

        $this->user = User::factory()->create([
            'socialevent_organization_id' => $this->organization->id,
            'socialevent_organization_role' => 'owner',
        ]);
        $this->user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Tipo de documento DNI (id 1) para validar document_type_id.
        IdentityDocumentType::firstOrCreate(
            ['id' => '1'],
            ['description' => 'DNI', 'long_description' => 'Documento nacional de identidad', 'sunat_code' => '1', 'number_characters' => 8],
        );

        $event = EvenEvent::create(['title' => 'Torneo de prueba', 'organization_id' => $this->organization->id]);
        $this->edition = EventEdition::create([
            'event_id' => $event->id,
            'year' => 2026,
            'name' => 'Edición 2026',
            'start_date' => '2026-01-10',
        ]);

        $this->team = EventTeam::create([
            'organization_id' => $this->organization->id,
            'name' => 'Equipo Rojo',
            'status' => 1,
        ]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->team->id]);

        $this->player = Person::create([
            'names' => 'Juan Carlos',
            'father_lastname' => 'Pérez',
            'mother_lastname' => 'Gómez',
            'full_name' => 'Juan Carlos Pérez Gómez',
            'document_type_id' => 1,
            'number' => '12345678',
        ]);
        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
            'person_id' => $this->player->id,
            'registration_date' => '2026-01-11',
        ]);
    }

    private function datosValidos(array $extra = []): array
    {
        return array_merge([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
            'player_id' => $this->player->id,
            'names' => 'Luis',
            'father_lastname' => 'Torres',
            'mother_lastname' => 'Ruiz',
            'document_type_id' => 1,
            'number' => '87654321',
            'birthdate' => '2010-05-20',
        ], $extra);
    }

    public function test_actualiza_los_datos_del_jugador_y_recalcula_el_nombre_completo(): void
    {
        $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, $this->datosValidos())
            ->assertRedirect()
            ->assertSessionHas('success', 'Jugador actualizado correctamente');

        $this->player->refresh();
        $this->assertSame('Luis', $this->player->names);
        $this->assertSame('Torres', $this->player->father_lastname);
        $this->assertSame('Ruiz', $this->player->mother_lastname);
        $this->assertSame('Luis Torres Ruiz', $this->player->full_name);
        $this->assertSame('87654321', $this->player->number);
        $this->assertSame('2010-05-20', $this->player->birthdate);
    }

    public function test_guarda_la_foto_cuando_se_envia_una_imagen(): void
    {
        Storage::fake('public');

        // PNG de 1x1 píxel en base64.
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, $this->datosValidos(['image' => $png]))
            ->assertRedirect();

        $this->player->refresh();
        $this->assertNotNull($this->player->image);
        $this->assertStringStartsWith('players/', $this->player->image);
        Storage::disk('public')->assertExists($this->player->image);
    }

    public function test_rechaza_datos_incompletos_sin_cambiar_al_jugador(): void
    {
        $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, $this->datosValidos(['names' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['names']);

        $this->assertSame('Juan Carlos', $this->player->fresh()->names);
    }

    public function test_no_edita_a_un_jugador_que_no_pertenece_al_equipo(): void
    {
        $otro = Person::create([
            'names' => 'Ajeno',
            'father_lastname' => 'Externo',
            'full_name' => 'Ajeno Externo',
            'document_type_id' => 1,
            'number' => '11112222',
        ]);

        $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, $this->datosValidos(['player_id' => $otro->id]))
            ->assertNotFound();

        $this->assertSame('Ajeno', $otro->fresh()->names);
    }

    public function test_genera_el_fotochet_del_jugador_con_su_url(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->getJson('/socialevents/editions/' . $this->edition->id . '/teams/' . $this->team->id . '/players/' . $this->player->id . '/pdf-card')
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->assertStringContainsString('temp_pdfs/fotochet_', $response->json('url'));
    }

    public function test_no_genera_el_fotochet_de_un_jugador_fuera_del_equipo(): void
    {
        $otro = Person::create([
            'names' => 'Ajeno',
            'father_lastname' => 'Externo',
            'full_name' => 'Ajeno Externo',
            'document_type_id' => 1,
            'number' => '11112222',
        ]);

        $this->actingAs($this->user)
            ->getJson('/socialevents/editions/' . $this->edition->id . '/teams/' . $this->team->id . '/players/' . $otro->id . '/pdf-card')
            ->assertNotFound();
    }

    public function test_un_usuario_sin_permiso_de_jugadores_no_puede_editar(): void
    {
        $sinPermiso = User::factory()->create([
            'socialevent_organization_id' => $this->organization->id,
            'socialevent_organization_role' => 'owner',
        ]);

        $this->actingAs($sinPermiso)
            ->postJson(self::ENDPOINT, $this->datosValidos())
            ->assertForbidden();

        $this->assertSame('Juan Carlos', $this->player->fresh()->names);
    }
}
