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
use Modules\Socialevents\Support\TournamentDateLabels;
use Modules\Socialevents\Support\TournamentLandingCache;
use Tests\TestCase;

/**
 * Landing pública: los cuartos de final a ida y vuelta se muestran como una fila
 * por llave con los dos partidos (ida y vuelta), y el resto de fases conserva la
 * lista de partidos de siempre.
 */
class TournamentLandingQuarterfinalTest extends TestCase
{
    use RefreshDatabase;

    private EventEdition $edition;

    private EventTeam $equipoA;

    private EventTeam $equipoB;

    private EventTeam $equipoC;

    private EventTeam $equipoD;

    protected function setUp(): void
    {
        parent::setUp();

        // La landing compila assets con @vite; en pruebas se omite el manifiesto.
        $this->withoutVite();

        // SQLite de pruebas no trae NOW() (sí existe en MySQL): se registra como función local.
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
            'public_slug' => 'copa-de-prueba',
        ]);

        $this->equipoA = EventTeam::create(['name' => 'Promo 2001', 'short_name' => 'P01', 'organization_id' => $organization->id]);
        $this->equipoB = EventTeam::create(['name' => 'Promo 2005', 'short_name' => 'P05', 'organization_id' => $organization->id]);
        $this->equipoC = EventTeam::create(['name' => 'Promo 2002', 'short_name' => 'P02', 'organization_id' => $organization->id]);
        $this->equipoD = EventTeam::create(['name' => 'Promo 2010', 'short_name' => 'P10', 'organization_id' => $organization->id]);

        foreach ([$this->equipoA, $this->equipoB, $this->equipoC, $this->equipoD] as $equipo) {
            EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $equipo->id]);
        }

        TournamentLandingCache::forget((int) $this->edition->id);
    }

    /**
     * Crea un cruce con las dos piernas enlazadas igual que lo hace el sistema:
     * la vuelta apunta a la ida con `tie_id`, tiene los equipos invertidos y va
     * en la jornada siguiente.
     *
     * @return array{ida: EventEditionMatch, vuelta: EventEditionMatch}
     */
    private function crearCruce(
        string $phase,
        EventTeam $local,
        EventTeam $visitante,
        string $formato,
        string $fechaIda,
        string $fechaVuelta,
        int $jornada = 1,
        ?array $marcadorIda = null,
    ): array {
        // `score_h` y `score_a` son NOT NULL: solo se escriben cuando el partido ya se jugó.
        $ida = EventEditionMatch::create(array_merge([
            'edition_id' => $this->edition->id,
            'team_h_id' => $local->id,
            'team_a_id' => $visitante->id,
            'phase' => $phase,
            'round_number' => $jornada,
            'match_date' => $fechaIda,
            'status' => $marcadorIda ? 'closed' : 'pending',
            'tie_format' => $formato,
            'decider' => 'normal',
            'leg' => $formato === 'home_and_away' ? 1 : null,
        ], $marcadorIda ? ['score_h' => $marcadorIda[0], 'score_a' => $marcadorIda[1]] : []));

        if ($formato !== 'home_and_away') {
            return ['ida' => $ida, 'vuelta' => null];
        }

        $vuelta = EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $visitante->id,
            'team_a_id' => $local->id,
            'phase' => $phase,
            'round_number' => $jornada + 1,
            'match_date' => $fechaVuelta,
            'status' => 'pending',
            'score_h' => 0,
            'score_a' => 0,
            'tie_format' => 'home_and_away',
            'decider' => 'normal',
            'tie_id' => $ida->id,
            'leg' => 2,
        ]);

        return ['ida' => $ida, 'vuelta' => $vuelta];
    }

    private function landingHtml(): string
    {
        TournamentLandingCache::forget((int) $this->edition->id);

        return $this->get(route('socialevents_torneos_landing', 'copa-de-prueba'))
            ->assertOk()
            ->getContent();
    }

    public function test_cuartos_ida_y_vuelta_se_muestran_en_una_fila_por_llave(): void
    {
        $this->crearCruce('quarterfinals', $this->equipoA, $this->equipoB, 'home_and_away', '2026-08-10 10:00:00', '2026-08-17 10:00:00');
        $this->crearCruce('quarterfinals', $this->equipoC, $this->equipoD, 'home_and_away', '2026-08-09 15:00:00', '2026-08-16 15:00:00');

        $html = $this->landingHtml();

        // Una fila por llave, no un partido por tarjeta.
        $this->assertSame(2, substr_count($html, 'class="se-tie"'));
        $this->assertSame(0, substr_count($html, 'class="se-round"'));

        // Los dos equipos y las dos piernas de cada llave.
        $this->assertStringContainsString('Promo 2001', $html);
        $this->assertStringContainsString('Promo 2005', $html);
        $this->assertStringContainsString('>Ida<', $html);
        $this->assertStringContainsString('>Vuelta<', $html);

        // Fecha y hora con el formato de las filas de cruce.
        $this->assertStringContainsString(TournamentDateLabels::shortDate('2026-08-10 10:00:00'), $html);
        $this->assertStringContainsString(TournamentDateLabels::time('2026-08-10 10:00:00'), $html);
        $this->assertStringContainsString(TournamentDateLabels::shortDate('2026-08-17 10:00:00'), $html);
    }

    public function test_cada_fila_junta_la_ida_y_la_vuelta_del_mismo_cruce(): void
    {
        $primera = $this->crearCruce('quarterfinals', $this->equipoA, $this->equipoB, 'home_and_away', '2026-08-10 10:00:00', '2026-08-17 10:00:00');
        $segunda = $this->crearCruce('quarterfinals', $this->equipoC, $this->equipoD, 'home_and_away', '2026-08-09 15:00:00', '2026-08-16 15:00:00');

        $html = $this->landingHtml();

        preg_match_all('/<article class="se-tie">(.*?)<\/article>/s', $html, $filas);

        $this->assertCount(2, $filas[1]);

        // La llave de Promo 2001 debe llevar SU vuelta (17 Ago), no la de la otra llave.
        $filaA = collect($filas[1])->first(fn ($fila) => str_contains($fila, 'Promo 2001'));
        $filaC = collect($filas[1])->first(fn ($fila) => str_contains($fila, 'Promo 2002'));

        $this->assertNotNull($filaA);
        $this->assertNotNull($filaC);

        $this->assertStringContainsString(TournamentDateLabels::shortDate($primera['vuelta']->match_date), $filaA);
        $this->assertStringContainsString(TournamentDateLabels::shortDate($segunda['vuelta']->match_date), $filaC);
        $this->assertStringNotContainsString(TournamentDateLabels::shortDate($segunda['vuelta']->match_date), $filaA);
    }

    public function test_un_resultado_ya_jugado_se_muestra_en_su_pierna(): void
    {
        $this->crearCruce(
            'quarterfinals',
            $this->equipoA,
            $this->equipoB,
            'home_and_away',
            '2026-08-10 10:00:00',
            '2026-08-17 10:00:00',
            1,
            [3, 1],
        );

        $html = $this->landingHtml();

        $this->assertStringContainsString('se-tie__leg-score', $html);
        $this->assertMatchesRegularExpression('/se-tie__leg-score">\s*3\s*<small>-<\/small>\s*1/s', $html);
        $this->assertStringContainsString('Finalizado', $html);
    }

    public function test_cuartos_a_partido_unico_conservan_la_lista_de_partidos(): void
    {
        $this->crearCruce('quarterfinals', $this->equipoA, $this->equipoB, 'single', '2026-08-10 10:00:00', '');
        $this->crearCruce('quarterfinals', $this->equipoC, $this->equipoD, 'single', '2026-08-11 10:00:00', '');

        $html = $this->landingHtml();

        $this->assertSame(0, substr_count($html, 'class="se-tie"'));
        $this->assertSame(2, substr_count($html, 'class="se-match '));
        $this->assertStringContainsString('Fecha 1', $html);
    }

    public function test_semifinales_a_ida_y_vuelta_conservan_la_lista_de_partidos(): void
    {
        $this->crearCruce('semifinals', $this->equipoA, $this->equipoB, 'home_and_away', '2026-08-10 10:00:00', '2026-08-17 10:00:00');

        $html = $this->landingHtml();

        // El diseño por llave es exclusivo de cuartos de final.
        $this->assertSame(0, substr_count($html, 'class="se-tie"'));
        $this->assertSame(2, substr_count($html, 'class="se-match '));
        $this->assertStringContainsString('Ida', $html);
        $this->assertStringContainsString('Vuelta', $html);
    }

    public function test_sin_fecha_ni_escudo_la_fila_muestra_estados_vacios(): void
    {
        $ida = EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'phase' => 'quarterfinals',
            'round_number' => 1,
            'match_date' => null,
            'status' => 'pending',
            'score_h' => 0,
            'score_a' => 0,
            'placeholder_h' => 'Ganador Llave 1',
            'placeholder_a' => 'Ganador Llave 2',
            'tie_format' => 'home_and_away',
            'decider' => 'normal',
            'leg' => 1,
        ]);
        EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'phase' => 'quarterfinals',
            'round_number' => 2,
            'match_date' => null,
            'status' => 'pending',
            'score_h' => 0,
            'score_a' => 0,
            'placeholder_h' => 'Ganador Llave 2',
            'placeholder_a' => 'Ganador Llave 1',
            'tie_format' => 'home_and_away',
            'decider' => 'normal',
            'tie_id' => $ida->id,
            'leg' => 2,
        ]);

        $html = $this->landingHtml();

        // Sin equipos asignados ni fecha, la fila sigue armada y sin datos inventados.
        $this->assertSame(1, substr_count($html, 'class="se-tie"'));
        $this->assertStringContainsString('Por definir', $html);
        $this->assertStringContainsString('Fecha por definir', $html);
        $this->assertStringContainsString('fa-question', $html);
        $this->assertStringNotContainsString('se-tie__leg-score', $html);
    }

    public function test_cuartos_mixtos_vuelven_a_la_lista_de_partidos(): void
    {
        // Un cruce a partido único deja la fase sin formato homogéneo: se usa la
        // presentación normal para no mezclar dos diseños en la misma fase.
        $this->crearCruce('quarterfinals', $this->equipoA, $this->equipoB, 'home_and_away', '2026-08-10 10:00:00', '2026-08-17 10:00:00');
        $this->crearCruce('quarterfinals', $this->equipoC, $this->equipoD, 'single', '2026-08-11 10:00:00', '');

        $html = $this->landingHtml();

        $this->assertSame(0, substr_count($html, 'class="se-tie"'));
        $this->assertSame(3, substr_count($html, 'class="se-match '));
    }

    public function test_sin_cuartos_la_landing_no_monta_filas_de_llave(): void
    {
        $this->crearCruce('final', $this->equipoA, $this->equipoB, 'single', '2026-08-20 10:00:00', '');

        $html = $this->landingHtml();

        $this->assertSame(0, substr_count($html, 'class="se-tie"'));
        $this->assertSame(1, substr_count($html, 'class="se-match '));
    }
}
