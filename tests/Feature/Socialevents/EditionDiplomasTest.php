<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Diplomas de torneo: premiados, diseños por premio, payload de render y
 * emisión con datos congelados.
 */
class EditionDiplomasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private EvenEvent $event;

    private EventEdition $edition;

    private EventTeam $team;

    private Person $player;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create(['email_verified_at' => now()]);

        foreach ([
            'even_dashboard',
            'even_ediciones_diplomas',
            'even_ediciones_diplomas_disenar',
            'even_ediciones_premiados',
        ] as $name) {
            $this->user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->event = EvenEvent::create(['title' => 'Copa de prueba']);

        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Edición 2026',
            'start_date' => '2026-01-10',
            'regular_half_minutes' => 20,
            'regular_halves' => 2,
        ]);

        $this->team = EventTeam::create(['name' => 'Deportivo Prueba', 'short_name' => 'DPR']);

        EventEditionTeam::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
            'points' => 21,
            'matches_played' => 9,
            'goals_for' => 18,
            'goals_against' => 6,
            'goal_difference' => 12,
        ]);

        $this->player = Person::create([
            'full_name' => 'Luis Ramos',
            'names' => 'Luis',
            'father_lastname' => 'Ramos',
            'mother_lastname' => 'Torres',
            'number' => '70000001',
        ]);

        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
            'person_id' => $this->player->id,
            'jersey_number' => '10',
            'position' => 'Delantero',
            'registration_date' => '2026-01-05',
        ]);
    }

    private function teamAward(array $overrides = []): EventEditionAward
    {
        return EventEditionAward::create(array_merge([
            'edition_id' => $this->edition->id,
            'award_key' => 'champion',
            'label' => 'Campeón',
            'recipient_type' => 'team',
            'team_id' => $this->team->id,
            'recipient_name' => 'Deportivo Prueba',
            'team_name' => 'Deportivo Prueba',
            'source' => 'manual',
            'place' => 1,
        ], $overrides));
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $elements
     */
    private function template(EventEditionAward $award, bool $active = true, ?array $elements = null): EventEditionDiplomaTemplate
    {
        return EventEditionDiplomaTemplate::create([
            // El modelo pertenece al torneo y declara su tipo: así se reutiliza
            // en otras ediciones.
            'event_id' => $this->event->id,
            'edition_id' => $this->edition->id,
            'award_key' => $award->award_key,
            'diploma_type' => $award->isTeamAward() ? 'team' : 'player',
            'year' => 2026,
            'name' => 'Diseño de prueba',
            'background_path' => 'socialevents/diplomas/'.$this->edition->id.'/front_test.png',
            'background_width' => 1200,
            'background_height' => 800,
            'layout' => [
                'version' => 1,
                'elements' => $elements ?? [
                    [
                        'id' => 'ganador',
                        'type' => 'text',
                        'x' => 100,
                        'y' => 200,
                        'width' => 700,
                        'text' => '{{premio_ganador}}',
                        'fontFamily' => 'Poppins Light',
                        'fontSize' => 60,
                        'color' => '#0b1f2b',
                        'align' => 'center',
                        'lineHeight' => 1.2,
                        'rotation' => 0,
                        'visible' => true,
                    ],
                    [
                        'id' => 'plantel',
                        'type' => 'squad',
                        'x' => 80,
                        'y' => 420,
                        'width' => 1000,
                        'columns' => 2,
                        'fontFamily' => 'Poppins Light',
                        'fontSize' => 20,
                        'color' => '#0b1f2b',
                        'align' => 'left',
                        'lineHeight' => 1.4,
                        'gap' => 20,
                        'showJerseyNumber' => true,
                        'showPosition' => false,
                        'sortBy' => 'number',
                        'title' => 'PLANTEL',
                        'titleFontSize' => 22,
                        'rotation' => 0,
                        'visible' => true,
                    ],
                    [
                        'id' => 'qr',
                        'type' => 'qr',
                        'x' => 1000,
                        'y' => 640,
                        'size' => 120,
                        'mode' => 'landing',
                        'rotation' => 0,
                        'visible' => true,
                    ],
                ],
            ],
            'is_active' => $active,
            'created_by' => $this->user->id,
        ]);
    }

    private function payloadUrl(EventEditionAward $award): string
    {
        return route('even_ediciones_diploma_payload', [$this->edition->id, $award->id]);
    }

    public function test_crea_premio_de_equipo_y_de_jugador(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_premiados_store', $this->edition->id), [
                'award_key' => 'champion',
                'label' => 'Campeón',
                'recipient_type' => 'team',
                'team_id' => $this->team->id,
                'place' => 1,
            ])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_premiados_store', $this->edition->id), [
                'award_key' => 'top_scorer',
                'label' => 'Goleador',
                'recipient_type' => 'player',
                'person_id' => $this->player->id,
                'meta' => ['goles' => 7],
            ])
            ->assertOk();

        $this->assertDatabaseHas('socialevent_edition_awards', [
            'edition_id' => $this->edition->id,
            'award_key' => 'champion',
            'recipient_name' => 'Deportivo Prueba',
        ]);

        $this->assertDatabaseHas('socialevent_edition_awards', [
            'edition_id' => $this->edition->id,
            'award_key' => 'top_scorer',
            'person_id' => $this->player->id,
            'team_name' => 'Deportivo Prueba',
        ]);
    }

    public function test_no_permite_dos_premios_con_la_misma_clave_en_la_edicion(): void
    {
        $this->teamAward();

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_premiados_store', $this->edition->id), [
                'award_key' => 'champion',
                'label' => 'Campeón repetido',
                'recipient_type' => 'team',
                'team_id' => $this->team->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('award_key');
    }

    public function test_sugiere_campeon_desde_la_tabla_de_posiciones(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('even_ediciones_premiados_sugerencias', $this->edition->id))
            ->assertOk()
            ->assertJsonPath('candidates.champion.0.recipient_name', 'Deportivo Prueba')
            ->assertJsonPath('candidates.champion.0.team_id', $this->team->id);
    }

    public function test_valida_el_layout_y_permite_varios_modelos_vigentes(): void
    {
        $award = $this->teamAward();
        $first = $this->template($award);
        $second = $this->template($award, false);

        $this->actingAs($this->user)
            ->putJson(route('even_ediciones_diplomas_update', [$this->edition->id, $first->id]), [
                'layout' => [
                    'version' => 1,
                    'elements' => [
                        ['id' => 'x', 'type' => 'inventado', 'x' => 10, 'y' => 10],
                    ],
                ],
            ])
            ->assertStatus(422);

        $this->actingAs($this->user)
            ->putJson(route('even_ediciones_diplomas_update', [$this->edition->id, $first->id]), [
                'layout' => [
                    'version' => 1,
                    'elements' => [
                        [
                            'id' => 'ganador',
                            'type' => 'text',
                            'x' => 10,
                            'y' => 20,
                            'width' => 400,
                            'text' => '{{premio_ganador}}',
                            'fontFamily' => 'Poppins Light',
                            'fontSize' => 40,
                            'color' => '#000000',
                            'align' => 'center',
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_diplomas_activar', [$this->edition->id, $second->id]))
            ->assertOk();

        // Regla nueva: puede haber varios modelos vigentes del mismo tipo y la
        // edición elige cuál usar en cada premio.
        $this->assertTrue($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_payload_resuelve_tokens_y_compone_el_plantel(): void
    {
        $award = $this->teamAward();
        $this->template($award);

        $response = $this->actingAs($this->user)->getJson($this->payloadUrl($award))->assertOk();

        $elements = collect($response->json('payload.side.elements'))->keyBy('id');

        $this->assertSame('Deportivo Prueba', $elements['ganador']['text']);
        $this->assertSame(1200, $response->json('payload.side.width'));
        $this->assertSame('PLANTEL', $elements['plantel']['title']);
        $this->assertCount(1, $elements['plantel']['items']);
        $this->assertStringContainsString('Luis Ramos', $elements['plantel']['items'][0]['line']);
        $this->assertStringContainsString('10.', $elements['plantel']['items'][0]['line']);
        $this->assertStringContainsString('/torneos/', $elements['qr']['qr_text']);
        // El editor usa estos valores para dibujar el texto del layout con sus
        // variables resueltas mientras se diseña.
        $this->assertSame('Deportivo Prueba', $response->json('payload.token_values.premio_ganador'));
        $this->assertNotEmpty($response->json('payload.token_values.torneo'));
    }

    public function test_payload_falla_sin_diseno_vigente(): void
    {
        $award = $this->teamAward();
        $this->template($award, false);

        $this->actingAs($this->user)
            ->getJson($this->payloadUrl($award))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_emision_congela_nombres_y_no_duplica(): void
    {
        $award = $this->teamAward();
        $template = $this->template($award);

        $first = $this->actingAs($this->user)
            ->postJson(route('even_ediciones_diploma_emitir', [$this->edition->id, $award->id]))
            ->assertOk();

        $this->assertDatabaseCount('socialevent_edition_diplomas', 1);
        $this->assertSame(32, strlen($first->json('payload.public_token') ?? ''));

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_diploma_emitir', [$this->edition->id, $award->id]))
            ->assertOk();

        $this->assertDatabaseCount('socialevent_edition_diplomas', 1);

        // El jugador cambia de nombre después de emitir: la reimpresión conserva
        // el dato congelado del diploma.
        $this->player->update(['full_name' => 'Luis Ramos Actualizado']);

        $this->actingAs($this->user)
            ->getJson($this->payloadUrl($award))
            ->assertOk()
            ->assertJsonPath('payload.award.recipient_name', 'Deportivo Prueba');

        $diploma = \Modules\Socialevents\Entities\EventEditionDiploma::first();
        $this->assertSame($template->id, $diploma->template_id);
        $this->assertCount(1, $diploma->squad_snapshot);
        $this->assertSame('Luis Ramos', $diploma->squad_snapshot[0]['name']);
    }

    public function test_eliminar_un_modelo_con_diplomas_lo_desactiva_sin_borrarlo(): void
    {
        $award = $this->teamAward();
        $template = $this->template($award, true);

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_diploma_emitir', [$this->edition->id, $award->id]))
            ->assertOk();

        $this->actingAs($this->user)
            ->deleteJson(route('even_ediciones_diplomas_destroy', [$this->edition->id, $template->id]))
            ->assertOk()
            ->assertJsonPath('deactivated', true);

        // El registro se conserva para que el diploma emitido siga teniendo su
        // modelo, pero deja de ofrecerse para nuevas emisiones.
        $this->assertDatabaseHas('socialevent_edition_diploma_templates', ['id' => $template->id]);
        $this->assertFalse($template->fresh()->is_active);
    }

    public function test_un_modelo_del_torneo_se_reutiliza_en_otra_edicion(): void
    {
        $template = $this->template($this->teamAward(), true);

        // Misma promoción, nueva edición del mismo torneo.
        $other = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2027,
            'name' => 'Edición 2027',
            'start_date' => '2027-01-10',
            'regular_half_minutes' => 20,
            'regular_halves' => 2,
        ]);

        $otherAward = EventEditionAward::create([
            'edition_id' => $other->id,
            'award_key' => 'champion',
            'label' => 'Campeón',
            'recipient_type' => 'team',
            'team_id' => $this->team->id,
            'recipient_name' => 'Deportivo Prueba',
            'team_name' => 'Deportivo Prueba',
            'place' => 1,
        ]);

        $this->actingAs($this->user)
            ->putJson(route('even_ediciones_premiados_template', [$other->id, $otherAward->id]), [
                'template_id' => $template->id,
            ])
            ->assertOk()
            ->assertJsonPath('template_id', $template->id);

        $this->actingAs($this->user)
            ->getJson(route('even_ediciones_diploma_payload', [$other->id, $otherAward->id]))
            ->assertOk()
            ->assertJsonPath('payload.template.id', $template->id)
            ->assertJsonPath('payload.award.recipient_name', 'Deportivo Prueba');
    }

    public function test_la_seleccion_explicita_gana_al_modelo_vigente_mas_reciente(): void
    {
        $award = $this->teamAward();
        $first = $this->template($award, true);
        $second = $this->template($award, true);

        $this->actingAs($this->user)
            ->putJson(route('even_ediciones_premiados_template', [$this->edition->id, $award->id]), [
                'template_id' => $first->id,
            ])
            ->assertOk();

        $this->actingAs($this->user)
            ->getJson($this->payloadUrl($award))
            ->assertOk()
            ->assertJsonPath('payload.template.id', $first->id);

        // Sin selección explícita se usa el vigente más reciente del tipo.
        $award->forceFill(['template_id' => null])->save();

        $this->actingAs($this->user)
            ->getJson($this->payloadUrl($award))
            ->assertOk()
            ->assertJsonPath('payload.template.id', $second->id);
    }

    public function test_no_asigna_un_modelo_de_jugador_a_un_premio_de_equipo(): void
    {
        $award = $this->teamAward();
        $playerTemplate = $this->template($award, true);
        $playerTemplate->update(['diploma_type' => 'player']);

        $this->actingAs($this->user)
            ->putJson(route('even_ediciones_premiados_template', [$this->edition->id, $award->id]), [
                'template_id' => $playerTemplate->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_id');
    }

    public function test_resuelve_las_variables_del_nuevo_flujo(): void
    {
        $award = $this->teamAward();

        $this->template($award, true, [[
            'id' => 'texto',
            'type' => 'text',
            'x' => 10,
            'y' => 10,
            'width' => 900,
            'text' => '{{nombre_equipo}} · {{edicion}} · {{puesto}} · {{tipo_premio}} · {{lista_jugadores}}',
            'fontFamily' => 'Poppins Light',
            'fontSize' => 30,
            'color' => '#000000',
            'align' => 'left',
        ]]);

        $response = $this->actingAs($this->user)->getJson($this->payloadUrl($award))->assertOk();
        $text = collect($response->json('payload.side.elements'))->firstWhere('id', 'texto')['text'];

        $this->assertStringContainsString('Deportivo Prueba', $text);
        $this->assertStringContainsString('Edición 2026', $text);
        $this->assertStringContainsString('1°', $text);
        $this->assertStringContainsString('Campeón', $text);
        $this->assertStringContainsString('Luis Ramos', $text);
        // Ninguna variable debe quedar impresa como llaves literales.
        $this->assertStringNotContainsString('{{', $text);
        $this->assertSame([], $response->json('payload.missing_tokens'));
    }

    public function test_reimprime_con_el_modelo_emitido_y_reemite_sin_duplicar(): void
    {
        $award = $this->teamAward();
        $first = $this->template($award, true);

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_diploma_emitir', [$this->edition->id, $award->id]))
            ->assertOk();

        // Se crea otro modelo del mismo tipo: la reimpresión debe seguir usando
        // el modelo con el que se emitió, no el más reciente.
        $second = $this->template($award, true);

        $this->actingAs($this->user)
            ->getJson($this->payloadUrl($award))
            ->assertOk()
            ->assertJsonPath('payload.template.id', $first->id);

        // Reemitir con una selección explícita actualiza el mismo diploma.
        $this->actingAs($this->user)
            ->putJson(route('even_ediciones_premiados_template', [$this->edition->id, $award->id]), [
                'template_id' => $second->id,
            ])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('even_ediciones_diploma_emitir', [$this->edition->id, $award->id]))
            ->assertOk()
            ->assertJsonPath('payload.template.id', $second->id);

        $this->assertDatabaseCount('socialevent_edition_diplomas', 1);
    }

    public function test_avisa_las_variables_que_quedaron_sin_dato(): void
    {
        $award = $this->teamAward();

        $this->template($award, true, [[
            'id' => 'texto',
            'type' => 'text',
            'x' => 10,
            'y' => 10,
            'width' => 900,
            'text' => 'Camiseta {{camiseta}}',
            'fontFamily' => 'Poppins Light',
            'fontSize' => 30,
            'color' => '#000000',
            'align' => 'left',
        ]]);

        $this->actingAs($this->user)
            ->getJson($this->payloadUrl($award))
            ->assertOk()
            ->assertJsonPath('payload.missing_tokens', ['camiseta']);
    }

    public function test_el_listado_de_modelos_del_torneo_esta_protegido(): void
    {
        $this->get(route('even_eventos_diplomas', $this->event->id))->assertRedirect();

        $this->actingAs($this->user)
            ->get(route('even_eventos_diplomas', $this->event->id))
            ->assertOk();

        // La pantalla de la edición también debe seguir respondiendo con el
        // nuevo listado de modelos reutilizables.
        $this->actingAs($this->user)
            ->get(route('even_ediciones_diplomas', $this->edition->id))
            ->assertOk();
    }

    public function test_crea_un_modelo_reutilizable_desde_el_torneo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user)
            ->post(route('even_eventos_diplomas_store', $this->event->id), [
                'diploma_type' => 'player',
                'year' => 2026,
                'edition_id' => $this->edition->id,
                'name' => 'Modelo individual',
                'background' => UploadedFile::fake()->image('fondo.png', 1200, 800),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('socialevent_edition_diploma_templates', [
            'event_id' => $this->event->id,
            'edition_id' => $this->edition->id,
            'diploma_type' => 'player',
            'year' => 2026,
            'name' => 'Modelo individual',
            'is_active' => true,
        ]);

        // El editor debe abrir con un premiado de muestra del tipo correcto.
        $template = EventEditionDiplomaTemplate::where('name', 'Modelo individual')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('even_eventos_diplomas_editar', [$this->event->id, $template->id]))
            ->assertOk();
    }

    public function test_sin_sesion_redirige_al_login(): void
    {
        $this->get(route('even_ediciones_diplomas', $this->edition->id))->assertRedirect();
    }
}
