<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMedia;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Support\TournamentLandingCache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * API de administración del móvil: edición de jugadores en cancha y galería de
 * la landing (fotos tomadas desde el celular).
 */
class AdminEditionPlayersApiTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    private EvenEvent $event;

    private EventEdition $edition;

    private EventTeam $team;

    private Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();

        $this->event = EvenEvent::create(['title' => 'Evento de prueba']);

        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-01-10',
        ]);

        $this->team = EventTeam::create([
            'name' => 'Equipo de prueba',
            'short_name' => 'EQP',
        ]);

        EventEditionTeam::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
        ]);

        // Jugador registrado solo con el nombre completo, sin apellidos ni
        // nombres separados: así quedan los que se crean desde la app.
        $this->person = Person::create([
            'full_name' => 'Carlos torres lopez',
            'document_type_id' => 1,
            'number' => '87772821',
            'gender' => 'M',
            'status' => '1',
        ]);

        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
            'person_id' => $this->person->id,
            'jersey_number' => '7',
            'position' => 'Extremo derecho',
            'role_in_team' => 'Ninguno',
            'registration_date' => '2026-01-11',
        ]);
    }

    public function test_edita_un_jugador_con_los_campos_de_nombre_vacios_sin_perder_su_nombre(): void
    {
        $response = $this->putJson($this->playerUrl(), [
            'father_lastname' => '',
            'mother_lastname' => '',
            'names' => '',
            'dni' => '87772821',
            'gender' => 'M',
            'jersey_number' => '10',
            'position' => 'Delantero',
            'role_in_team' => 'Capitán',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.full_name', 'Carlos torres lopez');
        $response->assertJsonPath('data.jersey_number', '10');
        $response->assertJsonPath('data.position', 'Delantero');
        $response->assertJsonPath('data.role_in_team', 'Capitán');

        $this->person->refresh();
        $this->assertSame('Carlos torres lopez', $this->person->full_name);
    }

    public function test_guarda_el_telefono_del_jugador_y_lo_devuelve_en_la_lista(): void
    {
        $response = $this->putJson($this->playerUrl(), [
            'father_lastname' => '',
            'mother_lastname' => '',
            'names' => '',
            'dni' => '87772821',
            'gender' => 'M',
            'telephone' => '987654321',
            'jersey_number' => '1',
            'position' => 'Arquero',
            'role_in_team' => 'Ninguno',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.telephone', '987654321');

        $this->person->refresh();
        $this->assertSame('987654321', $this->person->telephone);
        // Editar solo el teléfono no debe alterar el nombre ya registrado.
        $this->assertSame('Carlos torres lopez', $this->person->full_name);

        $this->getJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/players")
            ->assertOk()
            ->assertJsonPath('data.0.telephone', '987654321');
    }

    /**
     * La tabla de jugadores por edición tiene clave primaria compuesta y no
     * tiene columna `id`; si el modelo no la declara, el update termina en
     * "Unknown column 'id' in 'where clause'". Esta prueba fija ese contrato.
     */
    public function test_la_actualizacion_filtra_por_la_clave_compuesta_y_no_por_id(): void
    {
        $this->assertFalse(
            Schema::hasColumn('event_edition_team_players', 'id'),
            'La tabla de jugadores por edición no debe tener columna id.'
        );

        $player = EventEditionTeamPlayer::where('edition_id', $this->edition->id)
            ->where('team_id', $this->team->id)
            ->where('person_id', $this->person->id)
            ->firstOrFail();

        $player->update(['jersey_number' => '1', 'position' => 'Arquero']);

        $guardado = EventEditionTeamPlayer::where('edition_id', $this->edition->id)
            ->where('team_id', $this->team->id)
            ->where('person_id', $this->person->id)
            ->firstOrFail();

        $this->assertSame('1', (string) $guardado->jersey_number);
        $this->assertSame('Arquero', $guardado->position);

        // Guardar y refrescar el modelo tampoco puede pasar por `id`.
        $player->refresh();
        $this->assertSame('Arquero', $player->position);
        $this->assertTrue($player->exists);
    }

    public function test_recalcula_el_nombre_completo_cuando_se_completan_los_apellidos(): void
    {
        $response = $this->putJson($this->playerUrl(), [
            'father_lastname' => 'Torres',
            'mother_lastname' => 'López',
            'names' => 'Carlos Alberto',
            'dni' => '87772821',
            'gender' => 'M',
            'jersey_number' => '7',
            'position' => 'Extremo derecho',
            'role_in_team' => 'Ninguno',
        ]);

        $response->assertOk();

        $this->person->refresh();
        $this->assertSame('Torres López Carlos Alberto', $this->person->full_name);
        $this->assertSame('Torres', $this->person->father_lastname);
    }

    public function test_un_usuario_sin_rol_administrador_no_puede_editar_jugadores(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson($this->playerUrl(), [
            'jersey_number' => '10',
        ])->assertForbidden();
    }

    public function test_lista_los_jugadores_de_la_edicion_con_su_equipo_y_permite_buscar(): void
    {
        $response = $this->getJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/players");

        $response->assertOk();
        $response->assertJsonPath('data.0.full_name', 'Carlos torres lopez');
        $response->assertJsonPath('data.0.team_name', 'Equipo de prueba');
        $response->assertJsonPath('data.0.jersey_number', '7');

        $this->getJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/players?q=carlos")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/players?q=87772821")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/players?q=noexiste")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_sube_fotos_a_la_galeria_de_la_edicion_y_limpia_la_cache_de_la_landing(): void
    {
        Storage::fake('public');

        $match = EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'status' => 'finished',
            'phase' => 'league',
        ]);

        Cache::put(TournamentLandingCache::key($this->edition->id), ['previo'], 60);

        $response = $this->postJson($this->galleryUrl(), [
            'media_date' => '2026-02-14',
            'match_id' => $match->id,
            'images' => [self::PNG_1X1],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('uploaded', 1);
        $response->assertJsonPath('data.0.media_date', '2026-02-14');

        $media = EventEditionMedia::where('edition_id', $this->edition->id)->first();
        $this->assertNotNull($media);
        $this->assertSame('image', $media->type);
        $this->assertSame($match->id, $media->match_id);
        Storage::disk('public')->assertExists($media->file_path);
        $this->assertFalse(Cache::has(TournamentLandingCache::key($this->edition->id)));

        // La lista devuelve la foto recién subida con su URL pública.
        $listado = $this->getJson($this->galleryUrl());
        $listado->assertOk();
        $listado->assertJsonPath('data.total', 1);
        $listado->assertJsonPath('data.media.0.id', $media->id);
        $this->assertStringContainsString('storage/'.$media->file_path, $listado->json('data.media.0.url'));
    }

    public function test_rechaza_un_partido_que_no_pertenece_a_la_edicion(): void
    {
        Storage::fake('public');

        $otraEdicion = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Otra edición',
            'start_date' => '2026-03-01',
        ]);

        $partidoAjeno = EventEditionMatch::create([
            'edition_id' => $otraEdicion->id,
            'status' => 'pending',
            'phase' => 'league',
        ]);

        $this->postJson($this->galleryUrl(), [
            'media_date' => '2026-02-14',
            'match_id' => $partidoAjeno->id,
            'images' => [self::PNG_1X1],
        ])->assertStatus(422);

        $this->assertSame(0, EventEditionMedia::count());
    }

    public function test_elimina_una_foto_de_la_galeria(): void
    {
        Storage::fake('public');

        $media = EventEditionMedia::create([
            'edition_id' => $this->edition->id,
            'media_date' => '2026-02-14',
            'type' => 'image',
            'file_path' => 'socialevents/galleries/'.$this->edition->id.'/foto.png',
            'file_name' => 'foto.png',
            'mime_type' => 'image/png',
        ]);

        Storage::disk('public')->put($media->file_path, 'contenido');

        $this->deleteJson($this->galleryUrl()."/{$media->id}")
            ->assertOk();

        $this->assertNull(EventEditionMedia::find($media->id));
        Storage::disk('public')->assertMissing($media->file_path);

        $this->deleteJson($this->galleryUrl()."/{$media->id}")
            ->assertNotFound();
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Sanctum::actingAs($user);

        return $user;
    }

    private function playerUrl(): string
    {
        return "/api/socialevents/v1/admin/editions/{$this->edition->id}"
            ."/teams/{$this->team->id}/players/{$this->person->id}";
    }

    private function galleryUrl(): string
    {
        return "/api/socialevents/v1/admin/editions/{$this->edition->id}/gallery";
    }
}
