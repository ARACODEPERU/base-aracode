<?php

namespace Modules\Socialevents\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEditionMatch;

class TournamentService
{
    public function generate($editionId, $format, array $teamIds, ?callable $afterGenerate = null)
    {
        if (empty($teamIds)) {
            throw ValidationException::withMessages([
                'teams' => 'No se han seleccionado equipos para generar el torneo.'
            ]);
        }

        return DB::transaction(function () use ($editionId, $format, $teamIds, $afterGenerate) {
            // Borra partidos pendientes antes de crear nuevos
            EventEditionMatch::where('edition_id', $editionId)
                ->where('status', 'pending')
                ->delete();

            shuffle($teamIds);

            match($format) {
                'round_robin'              => $this->generateRoundRobin($teamIds, $editionId),
                'round_robin_playoff'      => $this->generateHybridFormat($teamIds, $editionId),
                'group_stage_and_playoffs' => $this->generateGroupStage($teamIds, $editionId),
                'relampago'                => $this->generateKnockout($teamIds, $editionId),
                'single_elimination'       => $this->generateSingleElimination($teamIds, $editionId),
                default                    => throw new \Exception("Formato no reconocido"),
            };

            $matches = EventEditionMatch::where('edition_id', $editionId)
                ->where('status', 'pending')
                ->get();
            if ($afterGenerate) $afterGenerate($matches);

            return $matches;
        });
    }

    /**
     * Crea solo los partidos todos contra todos que aún no existen en la edición.
     * No borra ni modifica partidos existentes (jugados, cerrados o pendientes)
     * y no asigna fecha ni hora: el organizador las define después.
     *
     * Las jornadas se reparten respetando lo que ya está registrado: el partido
     * que el organizador guardó en una fecha conserva su fecha y su cupo, y los
     * cruces que faltan se acomodan buscando que cada jornada quede con la
     * cantidad de partidos del calendario (con 4 equipos, dos por fecha) y que
     * ningún equipo juegue dos veces el mismo día. Cada cruce intenta primero la
     * fecha que le toca en el calendario y, si está completa o uno de sus equipos
     * ya juega ese día, otra que pueda con él.
     *
     * El acomodo se busca con retroceso (no por orden de llegada) porque un
     * reparto apurado deja fechas incompletas y equipos repetidos cuando el
     * organizador ya registró partidos en fechas que no son las del calendario.
     */
    public function generateMissingRoundRobin($editionId, array $teamIds, ?callable $afterGenerate = null)
    {
        if (count($teamIds) < 2) {
            throw ValidationException::withMessages([
                'teams' => 'Necesitas al menos 2 equipos.'
            ]);
        }

        return DB::transaction(function () use ($editionId, $teamIds, $afterGenerate) {
            // Orden estable para que regenerar no cambie los cruces ya existentes.
            $teamIds = array_values(array_unique(array_map('intval', $teamIds)));
            sort($teamIds);

            $calendario = $this->calendarioBerger($teamIds);
            $jornadas = $calendario === [] ? 0 : (int) max(array_keys($calendario));
            $cupoPorJornada = intdiv(count($teamIds), 2);

            // Reservas de cada jornada: los equipos que ya juegan ese día y
            // cuántos partidos tiene. Un partido registrado en una fecha ocupa
            // su cupo, de modo que el generador no la deje con un partido de más.
            $ocupacion = [];
            $paresExistentes = [];

            $existentes = EventEditionMatch::where('edition_id', $editionId)
                ->where('phase', 'league')
                ->get(['team_h_id', 'team_a_id', 'round_number']);

            foreach ($existentes as $match) {
                // Pares (local-visitante sin importar el orden) ya registrados en la liga.
                $paresExistentes[$this->claveParEquipos($match->team_h_id, $match->team_a_id)] = true;

                $jornada = (int) $match->round_number;
                // Sin fecha del calendario no reserva cupo, pero su cruce ya no se repite.
                if ($jornada < 1 || $jornada > $jornadas) continue;

                $this->reservarCupoEnJornada($ocupacion, $jornada, (int) $match->team_h_id, (int) $match->team_a_id);
            }

            // Cruces que faltan, en el orden en que los pone el calendario.
            $faltantes = [];
            foreach ($calendario as $jornadaCalendario => $cruces) {
                foreach ($cruces as [$home, $away]) {
                    if (isset($paresExistentes[$this->claveParEquipos($home, $away)])) continue;

                    $faltantes[] = [
                        'clave'   => $this->claveParEquipos($home, $away),
                        'home'    => (int) $home,
                        'away'    => (int) $away,
                        'jornada' => (int) $jornadaCalendario,
                    ];
                }
            }

            // Reparto con retroceso: se busca un acomodo donde ninguna jornada
            // pase de su cupo y ningún equipo juegue dos veces el mismo día.
            $reparto = $this->repartirCrucesPorJornada($faltantes, $ocupacion, $jornadas, $cupoPorJornada);

            $nuevos = collect();
            foreach ($faltantes as $faltante) {
                // Sin reparto posible (lo registrado ya obliga a repetir equipos)
                // el cruce igual se crea, en la jornada menos cargada.
                $jornada = $reparto[$faltante['clave']]
                    ?? $this->jornadaMenosCargada($ocupacion, $faltante['jornada'], $jornadas);

                $this->reservarCupoEnJornada($ocupacion, $jornada, $faltante['home'], $faltante['away']);

                $nuevos->push(EventEditionMatch::create([
                    'edition_id'   => $editionId,
                    'team_h_id'    => $faltante['home'],
                    'team_a_id'    => $faltante['away'],
                    'round_number' => $jornada,
                    'group_name'   => null,
                    'phase'        => 'league',
                    'match_date'   => null,
                    'status'       => 'pending',
                ]));
            }

            if ($afterGenerate) $afterGenerate($nuevos);

            return $nuevos;
        });
    }

    /** Reserva en la jornada a los equipos que juegan y suma el partido. */
    private function reservarCupoEnJornada(array &$ocupacion, int $jornada, int $home, int $away): void
    {
        $ocupacion[$jornada]['equipos'][$home] = true;
        $ocupacion[$jornada]['equipos'][$away] = true;
        $ocupacion[$jornada]['partidos'] = ($ocupacion[$jornada]['partidos'] ?? 0) + 1;
    }

    /**
     * Reparte los cruces pendientes entre las jornadas y devuelve [clavePar => jornada].
     * Devuelve null cuando no existe un acomodo que respete el cupo de cada jornada
     * sin repetir equipos el mismo día (por ejemplo, si lo ya registrado lo impide).
     *
     * Se prueba primero el cruce con menos jornadas disponibles, para descartar
     * temprano los callejones sin salida, y dentro de cada cruce primero la fecha
     * que le toca en el calendario. El tope de intentos evita que un caso imposible
     * se demore: al agotarse se usa el reparto de respaldo (jornada menos cargada).
     */
    private function repartirCrucesPorJornada(array $faltantes, array $ocupacion, int $jornadas, int $cupo): ?array
    {
        $intentos = 0;
        $topeIntentos = 20000;

        $resolver = function (array $pendientes, array $estado) use (&$resolver, $jornadas, $cupo, &$intentos, $topeIntentos): ?array {
            if ($pendientes === []) return [$estado, []];
            if (++$intentos > $topeIntentos) return null;

            $elegido = null;
            $opciones = null;
            foreach ($pendientes as $indice => $faltante) {
                $disponibles = $this->jornadasDisponibles($estado, $jornadas, $cupo, $faltante['home'], $faltante['away'], $faltante['jornada']);
                if ($disponibles === []) return null;

                if ($opciones === null || count($disponibles) < count($opciones)) {
                    $elegido = $indice;
                    $opciones = $disponibles;
                    if (count($opciones) === 1) break;
                }
            }

            $faltante = $pendientes[$elegido];
            unset($pendientes[$elegido]);
            $pendientes = array_values($pendientes);

            foreach ($opciones as $jornada) {
                $estadoSiguiente = $estado;
                $this->reservarCupoEnJornada($estadoSiguiente, $jornada, $faltante['home'], $faltante['away']);

                $resultado = $resolver($pendientes, $estadoSiguiente);
                if ($resultado === null) continue;

                [$estadoFinal, $asignaciones] = $resultado;
                $asignaciones[$faltante['clave']] = $jornada;

                return [$estadoFinal, $asignaciones];
            }

            return null;
        };

        $resultado = $resolver(array_values($faltantes), $ocupacion);

        return $resultado === null ? null : $resultado[1];
    }

    /**
     * Jornadas donde el cruce puede jugar: primero la que le toca en el calendario
     * y después las demás, siempre que tengan cupo y ningún equipo juegue ese día.
     */
    private function jornadasDisponibles(array $ocupacion, int $jornadas, int $cupo, int $home, int $away, int $preferida): array
    {
        $disponibles = [];

        if ($this->cabeElCruceEnJornada($ocupacion, $preferida, $cupo, $home, $away)) {
            $disponibles[] = $preferida;
        }

        for ($jornada = 1; $jornada <= $jornadas; $jornada++) {
            if ($jornada !== $preferida && $this->cabeElCruceEnJornada($ocupacion, $jornada, $cupo, $home, $away)) {
                $disponibles[] = $jornada;
            }
        }

        return $disponibles;
    }

    /** Jornada con menos partidos: respaldo cuando no existe un reparto posible. */
    private function jornadaMenosCargada(array $ocupacion, int $preferida, int $jornadas): int
    {
        $menosCargada = $preferida;

        for ($jornada = 1; $jornada <= $jornadas; $jornada++) {
            if (($ocupacion[$jornada]['partidos'] ?? 0) < ($ocupacion[$menosCargada]['partidos'] ?? 0)) {
                $menosCargada = $jornada;
            }
        }

        return $menosCargada;
    }

    /** true si la jornada todavía tiene cupo y ninguno de los dos equipos juega ese día. */
    private function cabeElCruceEnJornada(array $ocupacion, int $jornada, int $cupo, int $home, int $away): bool
    {
        if (($ocupacion[$jornada]['partidos'] ?? 0) >= $cupo) return false;

        return ! isset($ocupacion[$jornada]['equipos'][$home])
            && ! isset($ocupacion[$jornada]['equipos'][$away]);
    }

    private function claveParEquipos($a, $b): string
    {
        $a = (int) $a;
        $b = (int) $b;
        return min($a, $b) . '-' . max($a, $b);
    }

    /**
     * Calendario todos contra todos (método de Berger) como [jornada => [[local, visitante], ...]].
     */
    private function calendarioBerger(array $teams): array
    {
        $teams = array_values($teams);
        if (count($teams) % 2 != 0) $teams[] = null;

        $totalTeams = count($teams);
        $rondas = [];
        for ($i = 0; $i < $totalTeams - 1; $i++) {
            for ($j = 0; $j < $totalTeams / 2; $j++) {
                $home = $teams[$j];
                $away = $teams[$totalTeams - 1 - $j];
                if ($home && $away) $rondas[$i + 1][] = [$home, $away];
            }
            $pivot = array_shift($teams);
            array_unshift($teams, array_pop($teams));
            array_unshift($teams, $pivot);
        }

        return $rondas;
    }

    // --- FORMATO 1: TODOS CONTRA TODOS ---
    private function generateRoundRobin($teams, $editionId, $groupName = null)
    {
        // Si la cantidad de equipos es impar, añadimos un equipo "fantasma" (descanso)
        if (count($teams) % 2 != 0) $teams[] = null;

        $totalTeams = count($teams);
        $rounds = $totalTeams - 1; // Número total de jornadas
        $matchesPerRound = $totalTeams / 2;

        for ($i = 0; $i < $rounds; $i++) {
            // $i + 1 representa la Jornada actual (Jornada 1, Jornada 2...)
            $roundNumber = $i + 1;

            for ($j = 0; $j < $matchesPerRound; $j++) {
                $home = $teams[$j];
                $away = $teams[$totalTeams - 1 - $j];

                // Solo creamos el partido si ninguno de los dos es el equipo de descanso (null)
                if ($home && $away) {
                    EventEditionMatch::create([
                        'edition_id'   => $editionId,
                        'team_h_id'    => $home,
                        'team_a_id'    => $away,
                        'round_number' => $roundNumber, // <-- AQUÍ SE ASIGNA LA JORNADA CORRECTA
                        'group_name'   => $groupName,
                        'phase'        => $groupName ? 'group' : 'league',
                        'status'       => 'pending'
                    ]);
                }
            }

            // Algoritmo de Rotación de Berger (mantiene el primer equipo fijo y rota los demás)
            $pivot = array_shift($teams);
            array_unshift($teams, array_pop($teams));
            array_unshift($teams, $pivot);
        }
    }

    // --- FORMATO 2: POR GRUPOS ---
    private function generateGroupStage($teams, $editionId)
    {
        $chunks = array_chunk($teams, 4);
        $alphabet = range('A', 'Z');

        foreach ($chunks as $index => $groupTeams) {
            $this->generateRoundRobin($groupTeams, $editionId, $alphabet[$index]);
        }
    }

    private function generateKnockout($teams, $editionId)
    {
        $count = count($teams);
        if ($count < 2) throw new \Exception("Mínimo 2 equipos para generar eliminación directa");

        // 1. Barajar equipos para el "sorteo" inicial
        shuffle($teams);

        // 2. Determinar la potencia de 2 superior (2, 4, 8, 16, 32...) para el cuadro
        $power = 1;
        while ($power < $count) {
            $power *= 2;
        }

        $roundNumber = 1;
        $currentTeams = $teams;

        // 3. Crear la Primera Ronda (con manejo de BYES si no son potencia de 2)
        // Los equipos que no tienen pareja pasan automáticamente (BYE)
        $matchesInRound = $power / 2;
        $nextRoundPlaceholders = [];

        for ($i = 0; $i < $matchesInRound; $i++) {
            $teamH = array_shift($currentTeams);
            $teamA = array_shift($currentTeams);

            // Si hay ambos equipos, se crea partido normal
            if ($teamH && $teamA) {
                EventEditionMatch::create([
                    'edition_id'   => $editionId,
                    'team_h_id'    => $teamH,
                    'team_a_id'    => $teamA,
                    'phase'        => 'knockout',
                    'round_number' => $roundNumber,
                    'match_order'  => $i + 1, // Para saber qué ganador va contra quién
                    'status'       => 'pending'
                ]);
            }
            // Si solo hay uno (es un BYE/Pasa directo por sorteo)
            elseif ($teamH) {
                // Este equipo pasa directo a la siguiente ronda sin jugar
                $nextRoundPlaceholders[] = $teamH;
            }
        }

        // 4. Generar los "huecos" (Placeholders) para las rondas siguientes hasta la Final
        // Esto permite que el frontend visualice el camino al título aunque no haya equipos definidos
        $roundNumber++;
        $remainingMatches = $matchesInRound / 2;

        while ($remainingMatches >= 1) {
            for ($j = 0; $j < $remainingMatches; $j++) {
                EventEditionMatch::create([
                    'edition_id'   => $editionId,
                    'team_h_id'    => array_shift($nextRoundPlaceholders) ?? null, // NULL significa "Esperando Ganador"
                    'team_a_id'    => array_shift($nextRoundPlaceholders) ?? null,
                    'phase'        => 'knockout',
                    'round_number' => $roundNumber,
                    'match_order'  => $j + 1,
                    'status'       => 'pending'
                ]);
            }
            $remainingMatches /= 2;
            $roundNumber++;
        }
    }

    private function generateSingleElimination($teams, $editionId)
    {
        $this->generateKnockout($teams, $editionId);
    }

    // --- FORMATO HÍBRIDO (LIGA + PLAYOFFS) ---
    protected function generateHybridFormat($teamIds, $editionId)
    {
        // 1. Genera la Liga con jornadas automáticas (ej. del 1 al 9 si son 10 equipos)
        $this->generateRoundRobin($teamIds, $editionId);

        // 2. FASE DE PLAYOFFS
        $playoffPhases = [
            ['phase' => 'quarterfinals', 'count' => 4],
            ['phase' => 'semifinals',    'count' => 2],
            ['phase' => 'third_place',   'count' => 1],
            ['phase' => 'final',         'count' => 1],
        ];

        foreach ($playoffPhases as $config) {
            for ($i = 1; $i <= $config['count']; $i++) {
                EventEditionMatch::create([
                    'edition_id'    => $editionId,
                    'team_h_id'     => null,
                    'team_a_id'     => null,
                    'phase'         => $config['phase'],
                    'round_number'  => 1, // En eliminatorias suele ser 1 por fase
                    'status'        => 'pending',
                    'placeholder_h' => $this->getPlaceholderName($config['phase'], $i, 'h'),
                    'placeholder_a' => $this->getPlaceholderName($config['phase'], $i, 'a'),
                ]);
            }
        }

        return true;
    }

    // Auxiliar para que la base de datos no se vea vacía, sino con texto de ayuda
    private function getPlaceholderName($phase, $index, $side)
    {
        if ($phase === 'quarterfinals') return "Por Clasificar";
        if ($phase === 'semifinals') return "Ganador QF";
        if ($phase === 'final') return "Ganador SF";
        return "TBD";
    }
}
