<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentMatchResultPresenter;
use Tests\TestCase;

/**
 * Landing pública: el resultado del fixture muestra reglamentario, alargue y
 * penales solo con los datos reales del partido, sin inventar etapas.
 */
class TournamentLandingMatchResultTest extends TestCase
{
    use RefreshDatabase;

    private EventEdition $edition;

    private EventTeam $home;

    private EventTeam $away;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        DB::connection()->getPdo()->sqliteCreateFunction('NOW', fn () => now()->toDateTimeString());
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$partes) => implode('', $partes));

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
            'match_rate' => 5,
            'prepaid_balance' => 1000,
        ]);

        $evento = EvenEvent::create([
            'title' => 'Torneo de prueba',
            'organization_id' => $organization->id,
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $evento->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-08-01',
            'landing_published' => true,
            'public_slug' => 'copa-resultados',
        ]);

        $this->home = EventTeam::create([
            'name' => 'Promo 2010',
            'short_name' => 'P10',
            'organization_id' => $organization->id,
        ]);

        $this->away = EventTeam::create([
            'name' => 'Promoción 2004-2005',
            'short_name' => 'P04',
            'organization_id' => $organization->id,
        ]);

        foreach ([$this->home, $this->away] as $team) {
            EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $team->id]);
        }

        TournamentLandingCache::forget((int) $this->edition->id);
    }

    /**
     * Crea el partido con los campos reales del sistema. `score_h/a` es el
     * marcador final; `score_regular_h/a`, el del tiempo reglamentario.
     */
    private function createMatch(array $overrides = []): EventEditionMatch
    {
        return EventEditionMatch::create(array_merge([
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->home->id,
            'team_a_id' => $this->away->id,
            'phase' => 'final',
            'round_number' => 1,
            'match_date' => '2026-08-20 15:00:00',
            'status' => 'closed',
            'score_h' => 0,
            'score_a' => 0,
            'tie_format' => 'single',
            'decider' => 'normal',
        ], $overrides));
    }

    private function landingHtml(): string
    {
        TournamentLandingCache::forget((int) $this->edition->id);

        return $this->get(route('socialevents_torneos_landing', 'copa-resultados'))
            ->assertOk()
            ->getContent();
    }

    public function test_un_partido_normal_conserva_la_tarjeta_sin_detalles(): void
    {
        $this->createMatch(['score_h' => 6, 'score_a' => 6]);

        $html = $this->landingHtml();

        $this->assertStringContainsString('se-match__score">6<span class="se-match__score-sep">-</span>6', $html);
        $this->assertStringNotContainsString('se-match__details', $html);
        $this->assertStringNotContainsString('Tiempo reglamentario', $html);
        $this->assertStringNotContainsString('PEN.', $html);
    }

    public function test_un_partido_con_alargue_muestra_reglamentario_y_marcador_acumulado(): void
    {
        $this->createMatch([
            'score_h' => 2,
            'score_a' => 1,
            'score_regular_h' => 1,
            'score_regular_a' => 1,
            'extra_time_played' => true,
        ]);

        $html = $this->landingHtml();

        $this->assertStringContainsString('Tiempo reglamentario · 90′', $html);
        $this->assertStringContainsString('Tiempo extra · acumulado', $html);
        $this->assertStringContainsString('Resultado final', $html);
        $this->assertStringContainsString('se-match__detail-value">1 - 1</span>', $html);
        $this->assertStringContainsString('se-match__detail-value">2 - 1</span>', $html);
        $this->assertStringNotContainsString('Tanda de penales', $html);
        $this->assertStringNotContainsString('PEN.', $html);
    }

    public function test_penales_sin_alargue_muestran_reglamentario_tanda_y_final(): void
    {
        $this->createMatch([
            'score_h' => 0,
            'score_a' => 0,
            'extra_time_played' => false,
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'miss'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
            ],
        ]);

        $html = $this->landingHtml();

        $this->assertStringContainsString('Tiempo reglamentario · 90′', $html);
        $this->assertStringContainsString('Tanda de penales', $html);
        $this->assertStringContainsString('se-match__shootout">(4 - 3 PEN.)</div>', $html);
        $this->assertStringContainsString('se-match__detail-value">0 - 0 (4 - 3 PEN.)</span>', $html);
        $this->assertStringContainsString('Promo 2010 ganó por penales', $html);
        $this->assertStringNotContainsString('Tiempo extra · acumulado', $html);
    }

    public function test_penales_despues_del_alargue_muestran_las_tres_etapas(): void
    {
        $this->createMatch([
            'score_h' => 1,
            'score_a' => 1,
            'score_regular_h' => 1,
            'score_regular_a' => 1,
            'extra_time_played' => true,
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'miss'],
            ],
        ]);

        $html = $this->landingHtml();

        $this->assertStringContainsString('Tiempo reglamentario · 90′', $html);
        $this->assertStringContainsString('Tiempo extra · acumulado', $html);
        $this->assertStringContainsString('Tanda de penales', $html);
        $this->assertStringContainsString('se-match__detail-value">1 - 1 (4 - 3 PEN.)</span>', $html);
        $this->assertStringContainsString('se-match__shootout">(4 - 3 PEN.)</div>', $html);
    }

    public function test_la_tanda_respeta_el_orden_local_visitante_y_marca_al_ganador(): void
    {
        $this->createMatch([
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'miss'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'goal'],
                ['team' => 'local', 'result' => 'miss'],
                ['team' => 'visitor', 'result' => 'goal'],
            ],
        ]);

        $html = $this->landingHtml();

        $this->assertStringContainsString('Tanda de penales', $html);
        $this->assertStringContainsString('se-match__detail-value">2 - 4</span>', $html);
        $this->assertStringContainsString('se-match__detail-value">0 - 0 (2 - 4 PEN.)</span>', $html);
        $this->assertStringContainsString('Promoción 2004-2005 ganó por penales', $html);
        $this->assertStringContainsString('class="se-match__side se-match__side--away se-match__side--winner"', $html);
    }

    public function test_una_tanda_incompleta_no_se_muestra_ni_se_asume_cero(): void
    {
        $this->createMatch([
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => null],
            ],
        ]);

        $html = $this->landingHtml();

        $this->assertStringNotContainsString('Tanda de penales', $html);
        $this->assertStringNotContainsString('PEN.', $html);
        $this->assertStringNotContainsString('se-match__details', $html);
    }

    public function test_un_partido_pendiente_no_muestra_detalles_aunque_tenga_campos_extra(): void
    {
        $this->createMatch([
            'status' => 'pending',
            'extra_time_played' => true,
            'score_regular_h' => 0,
            'score_regular_a' => 0,
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'miss'],
            ],
        ]);

        $html = $this->landingHtml();

        $this->assertStringContainsString('se-match__score--vs">VS', $html);
        $this->assertStringNotContainsString('se-match__details', $html);
        $this->assertStringNotContainsString('Tiempo reglamentario', $html);
        $this->assertStringNotContainsString('PEN.', $html);
    }

    public function test_el_presentador_omite_el_reglamentario_si_falta_el_marcador(): void
    {
        $match = $this->createMatch([
            'score_h' => 2,
            'score_a' => 1,
            'extra_time_played' => true,
            'score_regular_h' => null,
            'score_regular_a' => null,
        ]);

        $result = TournamentMatchResultPresenter::forMatch($match->fresh());

        $this->assertTrue($result['has_details']);
        $this->assertSame(['extra', 'final'], array_column($result['details'], 'key'));
        $this->assertSame('2 - 1', $result['final_label']);
    }
}
