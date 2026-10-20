<?php

namespace Tests\Unit\Socialevents;

use Modules\Socialevents\Support\TournamentPhaseLabels;
use PHPUnit\Framework\TestCase;

/**
 * El catálogo de fases es la única fuente de verdad de los selectores.
 * Si apareciera un duplicado, el combo de Fase volvería a salir repetido.
 */
class TournamentPhaseLabelsTest extends TestCase
{
    public function test_options_no_tiene_claves_duplicadas(): void
    {
        $values = array_column(TournamentPhaseLabels::options(), 'value');

        $this->assertSame(array_values(array_unique($values)), $values);
    }

    public function test_options_no_tiene_etiquetas_duplicadas(): void
    {
        $labels = array_column(TournamentPhaseLabels::options(), 'label');

        $this->assertSame(array_values(array_unique($labels)), $labels);
    }

    public function test_options_trae_las_fases_de_eliminacion_pedidas(): void
    {
        $values = array_column(TournamentPhaseLabels::options(), 'value');

        $this->assertContains('round_16', $values);
        $this->assertContains('quarterfinals', $values);
        $this->assertContains('semifinals', $values);
        $this->assertContains('third_place', $values);
        $this->assertContains('final', $values);
    }

    public function test_orden_de_competencia_deja_el_tercer_puesto_antes_de_la_final(): void
    {
        $values = array_column(TournamentPhaseLabels::options(), 'value');

        $this->assertLessThan(
            array_search('final', $values, true),
            array_search('third_place', $values, true),
            'El tercer y cuarto puesto se juega antes de la final.'
        );

        $this->assertLessThan(
            array_search('semifinals', $values, true),
            array_search('quarterfinals', $values, true)
        );

        $this->assertLessThan(
            array_search('quarterfinals', $values, true),
            array_search('round_16', $values, true)
        );
    }

    public function test_canonical_resuelve_los_alias_historicos(): void
    {
        $this->assertSame('quarterfinals', TournamentPhaseLabels::canonical('quarter'));
        $this->assertSame('semifinals', TournamentPhaseLabels::canonical('semi'));
        $this->assertSame('round_16', TournamentPhaseLabels::canonical('round_8'));
        $this->assertSame('groups', TournamentPhaseLabels::canonical('group_stage'));
        $this->assertSame('league', TournamentPhaseLabels::canonical('league'));
        $this->assertNull(TournamentPhaseLabels::canonical(null));
    }

    public function test_tercer_puesto_se_llama_tercer_y_cuarto_puesto(): void
    {
        $this->assertSame('Tercer y cuarto puesto', TournamentPhaseLabels::label('third_place'));
        $this->assertSame('Tercer y cuarto puesto', TournamentPhaseLabels::label('third'));
        $this->assertSame('Octavos de final', TournamentPhaseLabels::label('round_16'));
        $this->assertSame('Eliminatorias', TournamentPhaseLabels::label('knockout'));
    }

    public function test_solo_las_fases_de_eliminacion_permiten_cruce(): void
    {
        $this->assertTrue(TournamentPhaseLabels::isKnockout('quarterfinals'));
        $this->assertTrue(TournamentPhaseLabels::isKnockout('final'));
        $this->assertTrue(TournamentPhaseLabels::isKnockout('third'));
        $this->assertFalse(TournamentPhaseLabels::isKnockout('league'));
        $this->assertFalse(TournamentPhaseLabels::isKnockout('groups'));
    }
}
