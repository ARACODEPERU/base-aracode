<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, router, useForm, usePage } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import Swal2 from "sweetalert2";
    import { ref, computed, watch } from 'vue';
    import ModalLargeXX from '@/Components/ModalLargeXX.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconStar from '@/Components/vristo/icon/icon-star.vue';
    import IconStarFiller from '@/Components/vristo/icon/icon-star-filler.vue';
    import IconBallSoccer from '@/Components/vristo/icon/icon-ball-soccer.vue';
    import IconNotesEdit from '@/Components/vristo/icon/icon-notes-edit.vue';
    import IconFutbol from '@/Components/vristo/icon/icon-futbol.vue';
    import IconHand from '@/Components/vristo/icon/icon-hand.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';

    const props = defineProps({
        edition: { type: Object, default: () => ({}) },
        match: { type: Object, default: () => ({}) },
        // Etiquetas de fase del catálogo único del servidor.
        phaseLabels: { type: Object, default: () => ({}) },
        tieFormats: { type: Array, default: () => [] },
    });

    // Mismo tema azul noche y lima que equipos, fixture y actas de la edición.
    const page = usePage();
    const sportsMode = computed(() => page.props.sportsOrganization?.mode === true);

    // Tokens de los controles para que los campos se pinten sobre la tarjeta navy
    // (la hoja global los tiñe de verde claro dentro de html.theme-torneos).
    const sportsControlVars = computed(() => ({
        '--torneos-control-bg': '#0e2532',
        '--torneos-control-text': '#e2e8f0',
        '--torneos-control-border': '#2b4b5d',
        '--torneos-control-placeholder': '#64748b',
        '--torneos-dropdown-bg': '#122c3a',
    }));

    // ─── Formulario del acta ───────────────────────────────────────────────
    // Los mismos campos que espera el endpoint de guardado, para que esta vista
    // y la corrección de escritorio no se comporten distinto.
    const formScore = useForm({
        id: props.match.id,
        edition_id: props.edition.id,
        price_red: props.edition.direct_red_price,
        double_yellow: props.edition.double_yellow_price,
        yellow: props.edition.yellow_price,
        equipo_h_name: props.match.equipolocal?.name ?? null,
        equipo_a_name: props.match.equipovisitante?.name ?? null,
        equipo_h_id: props.match.equipolocal?.id ?? null,
        equipo_a_id: props.match.equipovisitante?.id ?? null,
        score_h: props.match.score_h ?? 0,
        score_a: props.match.score_a ?? 0,
        has_penalties: !!(props.match.penalty_rounds && props.match.penalties?.length),
        penalty_rounds: props.match.penalty_rounds ?? 5,
        penalties: props.match.penalties || [],
        players_h: [],
        players_a: [],
        substitutions_h: [],
        substitutions_a: [],
        goals_h: [],
        goals_a: [],
        saves_h: [],
        saves_a: [],
        extra_time_played: !!props.match.extra_time_played,
        score_regular_h: props.match.score_regular_h ?? null,
        score_regular_a: props.match.score_regular_a ?? null,
        half_minutes_override: props.match.half_minutes_override ?? null,
        halves_override: props.match.halves_override ?? null,
        extra_half_minutes_override: props.match.extra_half_minutes_override ?? null,
    });

    const playersh = ref([]);
    const playersa = ref([]);

    // El acta ya estaba registrada (finalizada o cerrada): se entra en corrección.
    const isCorrectingScore = computed(
        () => ['finished', 'closed'].includes(props.match.status)
    );

    // Las etiquetas ya no se duplican aquí: llegan del catálogo del servidor.
    const phaseLabel = (phase) => props.phaseLabels?.[phase] ?? String(phase ?? '').replace(/_/g, ' ');

    // Resumen del cruce (ida/vuelta) que arma TournamentTieService.
    const tieSummary = computed(() => props.match.tie ?? null);

    const tieGlobalLabel = computed(() => {
        const tie = tieSummary.value;

        if (!tie || !tie.is_tie) return null;

        const how = { aggregate: 'global', penalties: 'penales', pending: 'pendiente' }[tie.decided_by] ?? '';

        return `${tie.aggregate_h} - ${tie.aggregate_a}${how ? ' (' + how + ')' : ''}`;
    });

    const isSuddenDeath = computed(() => props.match.decider === 'sudden_death');

    // ─── Tiempos y cambios ─────────────────────────────────────────────────
    // Misma resolución que el backend: override del partido → edición → preset.
    const timing = computed(() => {
        const edition = props.edition || {};

        const halfMinutes = Number(formScore.half_minutes_override) || Number(edition.regular_half_minutes) || 20;
        const halves = Number(formScore.halves_override) || Number(edition.regular_halves) || 2;
        const extraHalf = Number(formScore.extra_half_minutes_override) || Number(edition.extra_half_minutes) || 10;
        const extraHalves = Number(edition.extra_halves) || 2;

        const regularTotal = halfMinutes * halves;
        const extraTotal = extraHalf * extraHalves;
        const played = !!formScore.extra_time_played;

        return {
            half_minutes: halfMinutes,
            halves,
            extra_half_minutes: extraHalf,
            extra_halves: extraHalves,
            regular_total: regularTotal,
            extra_total: extraTotal,
            extra_time_played: played,
            duration: regularTotal + (played ? extraTotal : 0),
        };
    });

    const periodOptions = computed(() => {
        const options = [];

        if (timing.value.halves > 1) {
            options.push({ value: 'first', label: '1er tiempo', length: timing.value.half_minutes });
            options.push({ value: 'second', label: '2do tiempo', length: timing.value.half_minutes });
        } else {
            options.push({ value: 'first', label: 'Tiempo único', length: timing.value.half_minutes });
        }

        if (timing.value.extra_time_played) {
            options.push({ value: 'extra_first', label: '1er suplementario', length: timing.value.extra_half_minutes });
            options.push({ value: 'extra_second', label: '2do suplementario', length: timing.value.extra_half_minutes });
        }

        return options;
    });

    const periodLabel = (period) => periodOptions.value.find(o => o.value === period)?.label ?? 'Sin tiempo';

    const periodLength = (period) => periodOptions.value.find(o => o.value === period)?.length ?? timing.value.half_minutes;

    // Tiempo especial de la tanda de penales: no es tiempo de juego, pero sí
    // tiene atajadas que se registran (debe coincidir con el backend).
    const PENALTY_SHOOTOUT = 'penalty_shootout';

    // Tiempos que puede elegir una atajada: los mismos del partido más la tanda.
    const savePeriodOptions = computed(() => [
        ...periodOptions.value,
        { value: PENALTY_SHOOTOUT, label: 'Ronda de penales', length: 0 },
    ]);

    const periodShort = (period) => {
        if (period === 'first') return timing.value.halves > 1 ? '1T' : 'T';
        if (period === 'second') return '2T';
        if (period === 'extra_first') return 'S1';
        if (period === 'extra_second') return 'S2';
        if (period === PENALTY_SHOOTOUT) return 'Penales';

        return 's/t';
    };

    const absoluteMinute = (period, minuteInPeriod) => {
        const m = Math.max(0, Number(minuteInPeriod) || 0);

        if (period === 'second') return timing.value.half_minutes + m;
        if (period === 'extra_first') return timing.value.regular_total + m;
        if (period === 'extra_second') return timing.value.regular_total + timing.value.extra_half_minutes + m;

        return m;
    };

    const playerIdOf = (player) => Number(player?.person?.id || player?.person_id || 0);

    const playerName = (player) => player?.person?.full_name || player?.full_name || 'Jugador';

    // Tipos de gol: cómo se hizo. Debe coincidir con MatchPerformanceService.
    const goalTypeOptions = [
        { value: 'normal', label: 'Jugada' },
        { value: 'penalty', label: 'Penal' },
        { value: 'header', label: 'Cabeza' },
        { value: 'free_kick', label: 'Tiro libre' },
        { value: 'volley', label: 'Volea' },
        { value: 'other', label: 'Otro' },
    ];

    const goalTypeLabel = (value) => goalTypeOptions.find(o => o.value === value)?.label ?? 'Jugada';

    // Tipos de atajada: cómo llegó el remate que el arquero detuvo. Debe
    // coincidir con MatchPerformanceService.
    const saveTypeOptions = [
        { value: 'shot', label: 'Tiro / remate' },
        { value: 'one_on_one', label: 'Uno contra uno' },
        { value: 'header', label: 'Cabeza' },
        { value: 'olympic', label: 'Tiro olímpico' },
        { value: 'other', label: 'Otro' },
    ];


    const reasonOptions = [
        { value: 'tactical', label: 'Táctico' },
        { value: 'injury', label: 'Lesión' },
        { value: 'red_card', label: 'Expulsión' },
        { value: 'other', label: 'Otro' },
    ];

    const reasonLabel = (value) => reasonOptions.find(o => o.value === value)?.label ?? '—';

    const substitutionCandidates = (team, direction, current = null) => {
        const players = team === 'local' ? playersh.value : playersa.value;
        const subs = team === 'local' ? formScore.substitutions_h : formScore.substitutions_a;

        const currentOut = Number(current?.player_out_id) || 0;
        const currentIn = Number(current?.player_in_id) || 0;

        const hasEntered = new Set(subs.map(s => Number(s.player_in_id)).filter(Boolean));
        const hasLeft = new Set(subs.map(s => Number(s.player_out_id)).filter(Boolean));

        return players.filter(p => {
            const id = playerIdOf(p);

            const isOwnSelection = direction === 'out'
                ? (currentOut && id === currentOut)
                : (currentIn && id === currentIn);

            if (isOwnSelection) return true;

            if (!p.match_role) return false;

            if (direction === 'out') {
                return !hasLeft.has(id) && (p.match_role === 'starter' || hasEntered.has(id));
            }

            return p.match_role === 'substitute' && !hasEntered.has(id) && !hasLeft.has(id);
        });
    };

    const teamSubstitutions = (team) => team === 'local' ? formScore.substitutions_h : formScore.substitutions_a;

    const addSubstitution = (team) => {
        const list = team === 'local' ? formScore.substitutions_h : formScore.substitutions_a;

        list.push({
            player_out_id: null,
            player_in_id: null,
            period: periodOptions.value[0]?.value ?? 'first',
            minute_in_period: 0,
            reason: 'tactical',
        });
    };

    const removeSubstitution = (team, index) => {
        const list = team === 'local' ? formScore.substitutions_h : formScore.substitutions_a;
        list.splice(index, 1);
    };

    // ─── Goles con su tiempo, tipo y asistente ──────────────────────────────
    const teamGoals = (team) => team === 'local' ? formScore.goals_h : formScore.goals_a;

    // Goles que cuentan para el jugador: el gol en propia puerta va para el
    // rival, así que no suma a su cuenta. Es el mismo criterio con el que se
    // guarda su estadística, para que el número de la lista y el ranking de
    // jugadores no se contradigan.
    const playerGoalCount = (team, player) => {
        const id = playerIdOf(player);

        if (!id) return 0;

        return teamGoals(team).filter(g => Number(g.player_id) === id && !g.is_own_goal).length;
    };

    // Goles en propia puerta del jugador: no suman a su cuenta, pero se avisan
    // en la lista para que no queden invisibles al corregir el acta.
    const playerOwnGoalCount = (team, player) => {
        const id = playerIdOf(player);

        if (!id) return 0;

        return teamGoals(team).filter(g => Number(g.player_id) === id && g.is_own_goal).length;
    };

    const lastGoalSlot = (team) => {
        const last = teamGoals(team).filter(g => g.period).slice(-1)[0];

        return {
            period: last?.period ?? (periodOptions.value[0]?.value ?? 'first'),
            minute_in_period: last?.minute_in_period ?? 0,
        };
    };

    const addPlayerGoal = (team, player) => {
        const id = playerIdOf(player);

        if (!id) return;

        teamGoals(team).push({
            player_id: id,
            ...lastGoalSlot(team),
            goal_type: 'normal',
            assist_player_id: null,
            is_own_goal: false,
        });
    };

    const removePlayerGoal = (team, player) => {
        const id = playerIdOf(player);
        const list = teamGoals(team);

        for (let i = list.length - 1; i >= 0; i--) {
            if (Number(list[i].player_id) === id) {
                list.splice(i, 1);
                return;
            }
        }
    };

    // El botón de la pelota de la lista de jugadores: registra el gol de ese
    // jugador y abre su detalle para completar tiempo, tipo y asistencia.
    const registerPlayerGoal = (team, player) => {
        if (!player.match_role || !playerIdOf(player)) return;

        addPlayerGoal(team, player);
        openGoals(team, playerIdOf(player));
    };

    const addGoal = (team) => {
        teamGoals(team).push({
            player_id: null,
            ...lastGoalSlot(team),
            goal_type: 'normal',
            assist_player_id: null,
            is_own_goal: false,
        });
    };

    const removeGoal = (team, index) => {
        teamGoals(team).splice(index, 1);
    };

    // Quitar una fila concreta: al filtrar por jugador la posición de la fila en
    // pantalla ya no coincide con su índice dentro del equipo.
    const removeGoalRef = (team, goal) => {
        const list = teamGoals(team);
        const index = list.indexOf(goal);

        if (index !== -1) list.splice(index, 1);
    };

    // Autores posibles: los jugadores que están en el acta (para no inventar
    // goles de quien no fue alineado). El autor ya elegido siempre se incluye.
    const goalScorerCandidates = (team, currentId = null) => {
        const players = team === 'local' ? playersh.value : playersa.value;
        const current = Number(currentId) || 0;

        return players.filter(p => p.match_role || (current && playerIdOf(p) === current));
    };

    // Asistente: compañero del acta distinto del autor. La selección actual se
    // mantiene dentro de la lista: si se excluyera, su <option> desaparecería y
    // el combo se vería vacío aunque el asistente siguiera guardado.
    const goalAssistCandidates = (team, goal) => {
        const scorer = Number(goal?.player_id) || 0;

        return goalScorerCandidates(team, null).filter(p => {
            const id = playerIdOf(p);
            return id && id !== scorer;
        });
    };

    const playerGoalTimes = (team, player) => {
        const id = playerIdOf(player);
        const goals = teamGoals(team).filter(g => Number(g.player_id) === id);

        if (!goals.length) return '';

        return goals.map(g => {
            if (!g.period || g.minute_in_period === null || g.minute_in_period === undefined) {
                return 'sin minuto';
            }

            return `${periodShort(g.period)} ${Number(g.minute_in_period)}'`;
        }).join(' · ');
    };

    // Los dos equipos del partido, tal como se muestran en los modales.
    const teamSides = computed(() => [
        { key: 'local', label: formScore.equipo_h_name, color: 'text-blue-600' },
        { key: 'visitor', label: formScore.equipo_a_name, color: 'text-red-600' },
    ]);

    const teamLabel = (team) => team === 'visitor'
        ? (formScore.equipo_a_name || 'Visitante')
        : (formScore.equipo_h_name || 'Local');

    const playerById = (team, id) => (team === 'local' ? playersh.value : playersa.value)
        .find(p => playerIdOf(p) === Number(id)) || null;

    // ─── Atajadas del arquero con su detalle ────────────────────────────────
    // El contador vive en el detalle, igual que los goles: quitar una fila baja
    // el total del arquero en el mismo momento.
    const teamSaves = (team) => team === 'local' ? formScore.saves_h : formScore.saves_a;

    const playerSaveCount = (team, player) => {
        const id = playerIdOf(player);

        if (!id) return 0;

        return teamSaves(team).filter(s => Number(s.player_id) === id).length;
    };

    const lastSaveSlot = (team, player) => {
        const id = playerIdOf(player);
        const mine = teamSaves(team).filter(s => Number(s.player_id) === id && s.period);
        const last = mine.slice(-1)[0];

        return {
            period: last?.period ?? (periodOptions.value[0]?.value ?? 'first'),
            minute_in_period: last?.minute_in_period ?? 0,
        };
    };

    const addPlayerSave = (team, player) => {
        const id = playerIdOf(player);

        if (!id) return;

        teamSaves(team).push({
            player_id: id,
            ...lastSaveSlot(team, player),
            save_type: 'shot',
            is_penalty: false,
            shooter_player_id: null,
        });
    };

    // El botón del guante de la lista de jugadores: registra la atajada del
    // arquero y abre su detalle para completar cómo fue, quién la pateó y cuándo.
    const registerPlayerSave = (team, player) => {
        if (!player.match_role || !playerIdOf(player)) return;

        addPlayerSave(team, player);
        openSaves(team, playerIdOf(player));
    };

    const addSave = (team) => {
        teamSaves(team).push({
            player_id: null,
            period: periodOptions.value[0]?.value ?? 'first',
            minute_in_period: 0,
            save_type: 'shot',
            is_penalty: false,
            shooter_player_id: null,
        });
    };

    const removeSaveRef = (team, save) => {
        const list = teamSaves(team);
        const index = list.indexOf(save);

        if (index !== -1) list.splice(index, 1);
    };

    // Arqueros posibles: los del acta (mismo criterio que los goles). El ya
    // elegido se mantiene en la lista para que su <option> no desaparezca.
    const saveKeeperCandidates = (team, currentId = null) => {
        const players = team === 'local' ? playersh.value : playersa.value;
        const current = Number(currentId) || 0;

        return players.filter(p => p.match_role || (current && playerIdOf(p) === current));
    };

    // Quien remató: jugador del equipo rival (el que patea contra este arquero).
    const saveShooterCandidates = (team) => {
        const rival = team === 'local' ? playersa.value : playersh.value;

        return rival.filter(p => p.match_role && playerIdOf(p));
    };

    const playerSaveSummary = (team, player) => {
        const id = playerIdOf(player);
        const saves = teamSaves(team).filter(s => Number(s.player_id) === id);

        if (!saves.length) return '';

        return saves.map(s => {
            if (s.period === PENALTY_SHOOTOUT) return 'Penales';
            if (!s.period || s.minute_in_period === null || s.minute_in_period === undefined) return 'sin minuto';

            return `${periodShort(s.period)} ${Number(s.minute_in_period)}'`;
        }).join(' · ');
    };

    // Resumen que responde la pregunta del reporte: cuántos goles fueron en el
    // tiempo reglamentario, cuántos en la prórroga y cuántos en la tanda.
    const goalSummary = computed(() => {
        const breakdown = (team) => {
            const base = { regulation: 0, extra: 0, unlocated: 0 };

            teamGoals(team).forEach(g => {
                if (g.period === 'extra_first' || g.period === 'extra_second') base.extra++;
                else if (g.period) base.regulation++;
                else base.unlocated++;
            });

            return base;
        };

        const local = breakdown('local');
        const visitor = breakdown('visitor');

        return {
            local,
            visitor,
            regulation: local.regulation + visitor.regulation,
            extra: local.extra + visitor.extra,
            unlocated: local.unlocated + visitor.unlocated,
            penalties: {
                local: getPenaltyScore.value.penalty_h,
                visitor: getPenaltyScore.value.penalty_a,
            },
        };
    });

    const recalcMinutes = () => {
        ['local', 'visitor'].forEach(team => {
            const players = team === 'local' ? playersh.value : playersa.value;
            const subs = team === 'local' ? formScore.substitutions_h : formScore.substitutions_a;
            const duration = timing.value.duration;

            const enteredAt = {};
            const leftAt = {};

            subs.forEach(sub => {
                const outId = Number(sub.player_out_id);
                const inId = Number(sub.player_in_id);

                if (outId) leftAt[outId] = absoluteMinute(sub.period, sub.minute_in_period);
                if (inId) enteredAt[inId] = absoluteMinute(sub.period, sub.minute_in_period);
            });

            players.forEach(p => {
                if (!p.match_role || p.minutes_manual) return;

                const id = playerIdOf(p);
                let start;

                if (enteredAt[id] !== undefined) {
                    start = Math.min(enteredAt[id], duration);
                } else if (p.match_role === 'starter') {
                    start = 0;
                } else {
                    p.minutes_played = 0;
                    return;
                }

                const end = Math.min(leftAt[id] !== undefined ? leftAt[id] : duration, duration);
                p.minutes_played = Math.max(0, end - start);
            });
        });
    };

    watch(
        () => [
            JSON.stringify(formScore.substitutions_h),
            JSON.stringify(formScore.substitutions_a),
            formScore.extra_time_played,
            formScore.half_minutes_override,
            formScore.halves_override,
            formScore.extra_half_minutes_override,
        ],
        () => recalcMinutes()
    );

    // ─── Carga inicial ─────────────────────────────────────────────────────
    const mapPlayers = (players) => {
        if (!players) return [];

        return players.map(p => {
            const s = (p.stats && p.stats.length > 0) ? p.stats[0] : {};
            const sanctions = p.sanctions || [];

            const hasDoubleYellow = !!p.is_double_yellow || sanctions.some(x => x.type === 'double_yellow');
            const hasDirectRed = sanctions.some(x => x.type === 'red');

            return {
                ...p,
                match_role: p.is_suspended ? null : (p.match_role || null),

                is_suspended: !!p.is_suspended,
                suspension: p.suspension || null,

                goals: s.goals || 0,
                assists: s.assists || 0,
                saves: s.saves || 0,
                is_mvp: !!s.is_mvp,
                minutes_played: s.minutes_played || 0,
                minutes_manual: false,

                is_double_yellow: hasDoubleYellow,
                yellow_cards: hasDoubleYellow ? 2 : sanctions.filter(x => x.type === 'yellow').length,
                red_cards: (hasDirectRed || hasDoubleYellow) ? 1 : 0,
            };
        });
    };

    const initFromMatch = () => {
        const local = props.match.equipolocal || {};
        const visitor = props.match.equipovisitante || {};

        playersh.value = mapPlayers(local.players);
        playersa.value = mapPlayers(visitor.players);

        const toSubForm = (sub) => ({
            // Los ids se normalizan a número: los <option> del combo usan números
            // y un id en texto dejaría el select en blanco.
            player_out_id: sub.player_out_id ? Number(sub.player_out_id) : null,
            player_in_id: sub.player_in_id ? Number(sub.player_in_id) : null,
            period: sub.period || 'first',
            minute_in_period: sub.minute_in_period ?? 0,
            reason: sub.reason || 'tactical',
        });

        const substitutions = props.match.substitutions || [];

        formScore.substitutions_h = substitutions
            .filter(sub => Number(sub.team_id) === Number(local.id))
            .map(toSubForm);
        formScore.substitutions_a = substitutions
            .filter(sub => Number(sub.team_id) === Number(visitor.id))
            .map(toSubForm);

        // Atajadas ya registradas, con su tipo, quién la pateó y su tiempo.
        const saves = props.match.saves || [];
        const toSaveForm = (save) => ({
            player_id: save.player_id ? Number(save.player_id) : null,
            period: save.period || null,
            minute_in_period: save.minute_in_period ?? null,
            save_type: save.save_type || 'shot',
            is_penalty: !!save.is_penalty,
            shooter_player_id: save.shooter_player_id ? Number(save.shooter_player_id) : null,
        });

        formScore.saves_h = saves
            .filter(save => Number(save.team_id) === Number(local.id))
            .map(toSaveForm);
        formScore.saves_a = saves
            .filter(save => Number(save.team_id) === Number(visitor.id))
            .map(toSaveForm);

        // Goles ya registrados, con su tiempo, tipo y asistente.
        const goals = props.match.goals || [];
        const toGoalForm = (goal) => ({
            player_id: goal.player_id ? Number(goal.player_id) : null,
            period: goal.period || null,
            minute_in_period: goal.minute_in_period ?? null,
            goal_type: goal.goal_type || 'normal',
            assist_player_id: goal.assist_player_id ? Number(goal.assist_player_id) : null,
            is_own_goal: !!goal.is_own_goal,
        });

        formScore.goals_h = goals
            .filter(goal => Number(goal.team_id) === Number(local.id))
            .map(toGoalForm);
        formScore.goals_a = goals
            .filter(goal => Number(goal.team_id) === Number(visitor.id))
            .map(toGoalForm);

        recalcMinutes();
    };

    initFromMatch();

    // ─── Modales de detalle ────────────────────────────────────────────────
    const displayModalGoals = ref(false);
    const displayModalSubs = ref(false);
    const displayModalSaves = ref(false);
    const goalsFocusTeam = ref('local');
    // Jugador desde el que se abrió el modal: si hay foco, el detalle muestra
    // solo a ese jugador y su equipo.
    const goalsFocusPlayerId = ref(null);
    const savesFocusTeam = ref('local');
    const savesFocusPlayerId = ref(null);

    const openGoals = (team = 'local', playerId = null) => {
        goalsFocusTeam.value = team;
        goalsFocusPlayerId.value = playerId ? Number(playerId) : null;
        displayModalGoals.value = true;
    };

    const openSaves = (team = 'local', playerId = null) => {
        savesFocusTeam.value = team;
        savesFocusPlayerId.value = playerId ? Number(playerId) : null;
        displayModalSaves.value = true;
    };

    const focusedGoalPlayer = computed(() => goalsFocusPlayerId.value
        ? playerById(goalsFocusTeam.value, goalsFocusPlayerId.value)
        : null);

    const focusedSavePlayer = computed(() => savesFocusPlayerId.value
        ? playerById(savesFocusTeam.value, savesFocusPlayerId.value)
        : null);

    // Secciones del modal de goles: con foco, una sola (ese jugador con sus
    // goles); sin foco, los dos equipos con todo lo registrado.
    const goalSections = computed(() => {
        const focusedId = Number(goalsFocusPlayerId.value) || 0;

        if (!focusedId) {
            return teamSides.value.map(side => ({ ...side, focused: false, rows: teamGoals(side.key) }));
        }

        const side = teamSides.value.find(s => s.key === goalsFocusTeam.value) ?? teamSides.value[0];

        return [{
            ...side,
            focused: true,
            rows: teamGoals(side.key).filter(g => Number(g.player_id) === focusedId),
        }];
    });

    const saveSections = computed(() => {
        const focusedId = Number(savesFocusPlayerId.value) || 0;

        if (!focusedId) {
            return teamSides.value.map(side => ({ ...side, focused: false, rows: teamSaves(side.key) }));
        }

        const side = teamSides.value.find(s => s.key === savesFocusTeam.value) ?? teamSides.value[0];

        return [{
            ...side,
            focused: true,
            rows: teamSaves(side.key).filter(s => Number(s.player_id) === focusedId),
        }];
    });

    // "+ Agregar" de los modales: con foco la fila nace a nombre de ese jugador;
    // sin foco nace sin autor para elegirlo dentro del propio detalle.
    const addSectionGoal = (section) => {
        const player = section.focused ? focusedGoalPlayer.value : null;

        if (player) addPlayerGoal(section.key, player);
        else addGoal(section.key);
    };

    const addSectionSave = (section) => {
        const player = section.focused ? focusedSavePlayer.value : null;

        if (player) addPlayerSave(section.key, player);
        else addSave(section.key);
    };

    const openSubstitutions = () => {
        displayModalSubs.value = true;
    };

    // ─── Tarjetas ──────────────────────────────────────────────────────────
    const toggleYellow = (player) => {
        player.yellow_cards = (player.yellow_cards + 1) % 3;

        if (player.yellow_cards === 2) {
            player.red_cards = 1;
            player.is_double_yellow = true;
        } else {
            player.red_cards = 0;
            player.is_double_yellow = false;
        }
    };

    const toggleRed = (player) => {
        player.red_cards = player.red_cards === 0 ? 1 : 0;

        if (player.red_cards === 1) {
            player.yellow_cards = 0;
            player.is_double_yellow = false;
        }
    };

    // ─── Penales (tanda) ───────────────────────────────────────────────────
    const addPenalty = () => {
        const maxPenalties = formScore.penalty_rounds * 2;

        if (formScore.penalties.length >= maxPenalties) {
            Swal2.fire({
                title: 'Límite alcanzado',
                text: `Has alcanzado el máximo de ${maxPenalties} patadas permitidas para ${formScore.penalty_rounds} rondas.`,
                icon: 'warning',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        const currentIndex = formScore.penalties.length;

        formScore.penalties.push({
            team: currentIndex % 2 === 0 ? 'local' : 'visitor',
            round: Math.floor(currentIndex / 2) + 1,
            kicker: null,
            result: 'goal',
            goalkeeper: null,
            kickOrder: currentIndex + 1,
        });
    };

    const removePenalty = (index) => {
        formScore.penalties.splice(index, 1);

        formScore.penalties.forEach((penalty, idx) => {
            penalty.round = Math.floor(idx / 2) + 1;
            penalty.team = idx % 2 === 0 ? 'local' : 'visitor';
            penalty.kickOrder = idx + 1;
        });
    };

    const canAddMorePenalties = computed(() => formScore.penalties.length < (formScore.penalty_rounds * 2));

    const getPenaltyScore = computed(() => {
        const penalties = formScore.penalties;

        return {
            penalty_h: penalties.filter(p => p.team === 'local' && p.result === 'goal').length,
            penalty_a: penalties.filter(p => p.team === 'visitor' && p.result === 'goal').length,
        };
    });

    const getPlayersForTeam = (team) => {
        const players = team === 'local' ? playersh.value : playersa.value;

        return players.map(p => ({
            value: playerIdOf(p),
            label: `${playerName(p)} (#${p.jersey_number})`,
        }));
    };

    const suspensionSummary = (suspension) => {
        if (!suspension) return '';
        if (suspension.type === 'definitive') return 'Suspensión definitiva';
        if (suspension.type === 'matches') {
            const remaining = suspension.matches_remaining;
            return remaining != null
                ? `Suspendido · ${remaining} partido(s) restante(s)`
                : 'Suspendido por partidos';
        }
        if (suspension.type === 'date_range') {
            return `Suspendido del ${suspension.start_date} al ${suspension.end_date}`;
        }
        return 'Suspendido';
    };

    // ─── Guardar ───────────────────────────────────────────────────────────
    const updateMatchScore = () => {
        // Los cambios a medio llenar no se envían: solo los completos.
        formScore.substitutions_h = formScore.substitutions_h.filter(s => s.player_out_id && s.player_in_id);
        formScore.substitutions_a = formScore.substitutions_a.filter(s => s.player_out_id && s.player_in_id);
        // Una fila de gol sin jugador todavía no es un gol; tampoco una atajada
        // sin arquero.
        formScore.goals_h = formScore.goals_h.filter(g => g.player_id);
        formScore.goals_a = formScore.goals_a.filter(g => g.player_id);
        formScore.saves_h = formScore.saves_h.filter(s => s.player_id);
        formScore.saves_a = formScore.saves_a.filter(s => s.player_id);
        // El total de cada jugador se lee del detalle de goles y de atajadas.
        playersh.value.forEach(p => {
            p.goals = playerGoalCount('local', p);
            p.saves = playerSaveCount('local', p);
        });
        playersa.value.forEach(p => {
            p.goals = playerGoalCount('visitor', p);
            p.saves = playerSaveCount('visitor', p);
        });
        formScore.players_h = playersh.value;
        formScore.players_a = playersa.value;

        formScore.post(route('even_edition_match_score_update'), {
            preserveScroll: true,
            onError: (errors) => {
                const message = errors?.score || Object.values(errors || {})[0] || 'Ocurrió un error al guardar el acta';
                Swal2.fire({
                    title: 'No se pudo guardar',
                    text: message,
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: isCorrectingScore.value
                        ? 'Acta corregida: se recalcularon la tabla de posiciones y los rankings de jugadores'
                        : 'Se actualizo correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then(() => router.visit(route('even_ediciones_fixtures', props.edition.id)));
            },
        });
    };

    const goBack = () => router.visit(route('even_ediciones_fixtures', props.edition.id));
</script>
<template>
    <AppLayout title="Acta del partido">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[
                {
                    route: route('even_ediciones_listado'), title: 'Ediciones',
                    children: [
                        { route: route('even_ediciones_equipos', edition.id), title: 'Equipos', permissions: 'even_ediciones_equipos'},
                        { route: route('even_ediciones_fixtures', edition.id), title: 'Partidos', permissions: 'even_ediciones_fixtures'},
                        { route: route('even_ediciones_pago_sanciones', edition.id), title: 'Sanciones', permissions: 'even_ediciones_sanciones'},
                        { route: route('even_ediciones_exclusiones', edition.id), title: 'Exclusiones', permissions: 'even_ediciones_exclusiones'},
                        { route: route('even_ediciones_suspensiones', edition.id), title: 'Suspensiones', permissions: 'even_ediciones_suspensiones'},
                        { route: route('even_ediciones_actas_listado', edition.id), title: 'Actas', permissions: 'even_ediciones_actas'},
                    ]
                },
                {route: route('even_ediciones_editar', edition.id), title: edition.name},
                {route: route('even_ediciones_fixtures', edition.id), title: 'Control de Partidos'},
                {title: 'Acta del partido'}
            ]"
        />

        <div class="pt-5 space-y-6" :class="sportsMode ? 'text-slate-100' : ''">
            <!-- Cabecera del acta: mismo hero que Partidos, Equipos y Actas -->
            <section
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode
                    ? 'border-white/10 bg-[#0b1f2b] text-white shadow-[0_26px_70px_rgba(0,0,0,0.38)]'
                    : 'border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900'"
            >
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div
                            class="mb-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em]"
                            :class="sportsMode ? 'border-lime-300/25 bg-lime-300/10 text-lime-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-lime-400"></span> Acta del partido
                        </div>
                        <h1 class="text-2xl font-black tracking-tight sm:text-3xl" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                            {{ formScore.equipo_h_name || 'TBD' }}
                            <span :class="sportsMode ? 'text-lime-300' : 'text-gray-400'">vs</span>
                            {{ formScore.equipo_a_name || 'TBD' }}
                        </h1>
                        <p class="mt-1 text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-600 dark:text-gray-300'">
                            {{ match.match_date ? match.match_date : 'Horario por definir' }}
                            <span v-if="match.location"> · {{ match.location }}</span>
                            · {{ phaseLabel(match.phase) }}
                            <span v-if="match.round_number"> · Fecha {{ match.round_number }}</span>
                            <span v-if="tieSummary?.leg" class="ml-1 font-bold uppercase" :class="sportsMode ? 'text-lime-300' : 'text-indigo-600 dark:text-indigo-300'">
                                · {{ tieSummary.leg === 1 ? 'Ida' : 'Vuelta' }}
                            </span>
                            <span v-if="tieGlobalLabel" class="ml-1 font-bold" :class="sportsMode ? 'text-slate-100' : 'text-gray-700 dark:text-gray-200'">
                                · Global {{ tieGlobalLabel }}
                            </span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <span
                            v-if="isCorrectingScore"
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold"
                            :class="sportsMode
                                ? 'border-amber-300/30 bg-amber-300/10 text-amber-200'
                                : 'border-amber-300 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'"
                        >
                            Acta ya registrada · se guardará como corrección
                        </span>
                        <button
                            type="button"
                            @click="goBack"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                            :class="sportsMode
                                ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200'
                                : 'btn btn-warning text-xs'"
                        >
                            Volver al fixture
                        </button>
                    </div>
                </div>
            </section>

            <!-- Marcador y estado del encuentro -->
            <div
                class="rounded-xl border p-4"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_18px_50px_rgba(0,0,0,0.28)]' : 'border-default dark:border-blue-900'"
                :style="sportsMode ? sportsControlVars : null"
            >
                <div
                    v-if="isSuddenDeath"
                    class="mb-3 rounded-lg border px-4 py-2 text-sm"
                    :class="sportsMode ? 'border-amber-300/30 bg-amber-300/10 text-amber-200' : 'border-amber-300 bg-amber-50 text-amber-800'"
                >
                    Muerte súbita: el primero que anota gana. No se admite empate ni se registran penales.
                </div>
                <div class="grid sm:grid-cols-5 gap-2 items-center">
                    <div class="sm:col-span-2 flex items-center justify-between">
                        <InputLabel :value="formScore.equipo_h_name || 'Local'" />
                        <TextInput
                            v-model="formScore.score_h"
                            class="w-16 border-4 text-center"
                            :class="sportsMode ? 'font-black text-lime-300' : ''"
                        />
                    </div>
                    <div class="sm:col-span-1 flex items-center justify-center">
                        <h3 class="text-lg font-bold" :class="sportsMode ? 'text-slate-400' : ''">VS</h3>
                    </div>
                    <div class="sm:col-span-2 flex items-center justify-between">
                        <TextInput
                            v-model="formScore.score_a"
                            class="w-16 border-4 text-center"
                            :class="sportsMode ? 'font-black text-lime-300' : ''"
                        />
                        <InputLabel :value="formScore.equipo_a_name || 'Visitante'" />
                    </div>
                </div>

                <!-- Cómo se repartieron los goles: lo que se usará en los reportes -->
                <div class="mt-4 grid sm:grid-cols-3 gap-3 text-sm">
                    <div
                        class="rounded-lg border p-3"
                        :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-default dark:border-blue-900'"
                    >
                        <p class="text-xs font-bold uppercase" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Tiempo reglamentario</p>
                        <p class="text-xl font-black" :class="sportsMode ? 'text-white' : ''">{{ goalSummary.local.regulation }} - {{ goalSummary.visitor.regulation }}</p>
                    </div>
                    <div
                        class="rounded-lg border p-3"
                        :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-default dark:border-blue-900'"
                    >
                        <p class="text-xs font-bold uppercase" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Alargue (prórroga)</p>
                        <p class="text-xl font-black" :class="sportsMode ? 'text-white' : ''">{{ goalSummary.local.extra }} - {{ goalSummary.visitor.extra }}</p>
                    </div>
                    <div
                        class="rounded-lg border p-3"
                        :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-default dark:border-blue-900'"
                    >
                        <p class="text-xs font-bold uppercase" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Penales (tanda)</p>
                        <p class="text-xl font-black" :class="sportsMode ? 'text-white' : ''">
                            {{ formScore.has_penalties ? `${goalSummary.penalties.local} - ${goalSummary.penalties.visitor}` : '—' }}
                        </p>
                    </div>
                </div>
                <p
                    v-if="goalSummary.unlocated > 0"
                    class="mt-2 text-xs"
                    :class="sportsMode ? 'text-amber-300' : 'text-amber-700 dark:text-amber-300'"
                >
                    {{ goalSummary.unlocated }} gol(es) sin tiempo registrado: no se reparten entre los tiempos hasta que se les asigne uno.
                </p>
            </div>

            <!-- Tiempos del partido -->
            <div
                class="mt-2 rounded-base border p-4"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100' : 'border-default dark:border-blue-900'"
                :style="sportsMode ? sportsControlVars : null"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 class="text-lg font-bold" :class="sportsMode ? 'text-white' : ''">Tiempos del partido</h4>
                        <p class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500 dark:text-gray-400'">
                            Duración: {{ timing.half_minutes }} min por tiempo × {{ timing.halves }}
                            = {{ timing.regular_total }} min<span v-if="timing.extra_time_played"> + prórroga {{ timing.extra_total }} min</span>
                            (total {{ timing.duration }} min). Los minutos se calculan solos desde los cambios.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" class="form-checkbox" v-model="formScore.extra_time_played" />
                            Se jugó prórroga
                        </label>
                        <div class="flex items-center gap-2 text-sm">
                            <span>Min. por tiempo</span>
                            <input
                                type="number"
                                min="1"
                                class="form-input w-24 text-center"
                                v-model.number="formScore.half_minutes_override"
                                :placeholder="String(edition.regular_half_minutes || 20)"
                                title="Solo para este partido; si se deja vacío se usan los minutos de la edición"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles: goles, cambios y penales -->
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                    :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'btn btn-primary text-xs'"
                    @click="openGoals('local')"
                >
                    ⚽ Detalle de goles ({{ formScore.goals_h.length + formScore.goals_a.length }})
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                    :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-success text-xs'"
                    @click="openSubstitutions()"
                >
                    🔁 Cambios ({{ formScore.substitutions_h.length + formScore.substitutions_a.length }})
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                    :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-primary text-xs'"
                    @click="openSaves('local')"
                >
                    🧤 Detalle de atajadas ({{ formScore.saves_h.length + formScore.saves_a.length }})
                </button>
                <label class="inline-flex cursor-pointer items-center gap-2 px-3 text-sm">
                    <input type="checkbox" v-model="formScore.has_penalties" class="w-5 h-5 text-yellow-600 rounded focus:ring-yellow-500" />
                    <span class="font-medium" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'">Registrar tanda de penales</span>
                </label>
            </div>

            <!-- Sección de Penales -->
            <div
                v-if="formScore.has_penalties"
                class="rounded-lg border p-4"
                :class="sportsMode ? 'border-amber-300/25 bg-amber-300/5 text-slate-100' : 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20'"
                :style="sportsMode ? sportsControlVars : null"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold" :class="sportsMode ? 'text-amber-200' : 'text-yellow-800 dark:text-yellow-200'">Tanda de penales</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-600 dark:text-gray-400'">Rondas:</span>
                        <TextInput v-model="formScore.penalty_rounds" type="number" min="1" max="10" class="w-16" />
                        <span class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">({{ formScore.penalty_rounds * 2 }} patadas máximo)</span>
                    </div>
                </div>

                <div class="mb-4 flex items-center justify-center gap-4">
                    <div class="text-center">
                        <span class="text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-600 dark:text-gray-400'">Local</span>
                        <div class="text-3xl font-bold" :class="sportsMode ? 'text-lime-300' : 'text-green-600'">{{ getPenaltyScore.penalty_h }}</div>
                    </div>
                    <span class="text-lg font-bold" :class="sportsMode ? 'text-slate-500' : 'text-gray-400'">-</span>
                    <div class="text-center">
                        <span class="text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-600 dark:text-gray-400'">Visitante</span>
                        <div class="text-3xl font-bold" :class="sportsMode ? 'text-lime-300' : 'text-green-600'">{{ getPenaltyScore.penalty_a }}</div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="addPenalty"
                    :disabled="!canAddMorePenalties"
                    :class="[
                        sportsMode
                            ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]'
                            : 'bg-yellow-500 hover:bg-yellow-600 text-white',
                        { 'opacity-50 cursor-not-allowed': !canAddMorePenalties }
                    ]"
                    class="mb-4 inline-flex items-center rounded-md px-3 py-2 text-sm font-medium"
                >
                    <IconPlus class="w-4 h-4 mr-1" />
                    Agregar Penal
                </button>

                <div v-if="formScore.penalties.length > 0" class="space-y-2">
                    <div class="flex items-center gap-2 px-2 text-xs font-bold" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                        <div class="w-20">#</div>
                        <div class="flex-1">Equipo que Patea</div>
                        <div class="flex-1">Pateador</div>
                        <div class="flex-1">Resultado</div>
                        <div class="flex-1">Arquero</div>
                        <div class="w-8"></div>
                    </div>

                    <div
                        v-for="(penalty, index) in formScore.penalties"
                        :key="index"
                        class="flex items-center gap-2 rounded-lg border p-3"
                        :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'bg-white dark:bg-gray-800'"
                    >
                        <div class="w-20">
                            <span class="text-xs font-bold" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Patada {{ index + 1 }}</span>
                            <span class="block text-[10px]" :class="sportsMode ? 'text-slate-500' : 'text-gray-400'">(Ronda {{ penalty.round }})</span>
                        </div>
                        <div class="flex-1">
                            <select
                                v-model="penalty.team"
                                class="form-select text-sm"
                                :class="penalty.team === 'local' ? 'bg-blue-50 border-blue-300' : 'bg-red-50 border-red-300'"
                            >
                                <option value="local">LOCAL</option>
                                <option value="visitor">VISITANTE</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <select v-model="penalty.kicker" class="form-select text-sm">
                                <option :value="null">Seleccionar...</option>
                                <option v-for="player in getPlayersForTeam(penalty.team)" :key="player.value" :value="player.value">
                                    {{ player.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <select
                                v-model="penalty.result"
                                class="form-select text-sm"
                                :class="{
                                    'border-green-500 text-green-600 font-bold': penalty.result === 'goal',
                                    'border-red-500 text-red-600 font-bold': penalty.result === 'miss',
                                    'border-orange-500 text-orange-600 font-bold': penalty.result === 'saved'
                                }"
                            >
                                <option value="goal">ANOTADO</option>
                                <option value="miss">FALLÓ</option>
                                <option value="saved">TAPÓ</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <select v-model="penalty.goalkeeper" class="form-select text-sm">
                                <option :value="null">Sin arquero</option>
                                <option v-for="player in getPlayersForTeam(penalty.team === 'local' ? 'visitor' : 'local')" :key="player.value" :value="player.value">
                                    {{ player.label }}
                                </option>
                            </select>
                        </div>
                        <button type="button" @click="removePenalty(index)" class="p-2 text-red-500 hover:text-red-700">
                            <IconTrashLines class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                <p v-else class="py-2 text-center text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                    No hay penales agregados. Haz clic en "Agregar penal" para registrar.
                </p>
            </div>

            <!-- Alineaciones -->
            <div class="grid xl:grid-cols-2 gap-4">
                <div
                    v-for="side in [
                        { key: 'local', label: formScore.equipo_h_name, players: playersh },
                        { key: 'visitor', label: formScore.equipo_a_name, players: playersa },
                    ]"
                    :key="side.key"
                >
                    <div
                        class="w-full overflow-hidden rounded-base border p-0"
                        :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_18px_50px_rgba(0,0,0,0.28)]' : 'border-default shadow-xs dark:border-blue-900'"
                        :style="sportsMode ? sportsControlVars : null"
                    >
                        <div
                            class="flex items-center justify-between border-b px-4 py-2"
                            :class="sportsMode ? 'border-[#234052] bg-[#0e2532]' : 'border-default dark:border-blue-900'"
                        >
                            <p class="font-bold" :class="sportsMode ? 'text-white' : ''">{{ side.label }}</p>
                            <button
                                type="button"
                                class="text-xs underline"
                                :class="sportsMode ? 'text-lime-300 hover:text-lime-200' : 'text-blue-600'"
                                @click="openGoals(side.key)"
                            >
                                Ver goles
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="w-full text-sm text-left rtl:text-right text-body se-score-table">
                                <thead
                                    class="border-b text-sm"
                                    :class="sportsMode ? 'border-[#234052] text-lime-200' : 'border-default text-body dark:border-blue-900'"
                                >
                                    <th class="se-score-sticky se-score-sticky--role px-3 py-2 text-xs font-bold" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 dark:bg-blue-900 dark:text-white'"></th>
                                    <th class="se-score-sticky se-score-sticky--name px-3 py-2 text-xs font-bold" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 dark:bg-blue-900 dark:text-white'">Nombre</th>
                                    <th class="px-3 py-2 text-xs font-bold" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 dark:bg-blue-900 dark:text-white'">Goles</th>
                                    <th class="px-3 py-2 text-xs font-bold" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 dark:bg-blue-900 dark:text-white'">Atajadas</th>
                                    <th class="px-3 py-2 text-xs font-bold" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 dark:bg-blue-900 dark:text-white'">MVP</th>
                                    <th class="px-3 py-2 text-xs font-bold" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 dark:bg-blue-900 dark:text-white'">Sanciones</th>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(player, index) in side.players"
                                        :key="side.key + index"
                                        class="border-b"
                                        :class="sportsMode ? 'border-[#1b3544] hover:bg-[#122c3a]' : 'border-default'"
                                    >
                                        <td
                                            class="se-score-sticky se-score-sticky--role px-2"
                                            :class="sportsMode ? 'bg-[#0b1f2b]' : 'bg-white dark:bg-gray-800'"
                                        >
                                            <div class="flex-col items-start gap-1">
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" :name="'role_' + side.key + index" value="starter"
                                                        v-model="player.match_role"
                                                        :disabled="player.is_suspended"
                                                        class="text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-1 text-xs">Titular</span>
                                                </label>
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" :name="'role_' + side.key + index" value="substitute"
                                                        v-model="player.match_role"
                                                        :disabled="player.is_suspended"
                                                        class="text-green-600 focus:ring-green-500">
                                                    <span class="ml-1 text-xs">Suplente</span>
                                                </label>
                                                <button @click="player.match_role = null" class="text-[11px] text-red-400 underline">
                                                    No participo
                                                </button>
                                            </div>
                                        </td>
                                        <td
                                            class="se-score-sticky se-score-sticky--name whitespace-nowrap px-3 py-3 font-medium"
                                            :class="sportsMode ? 'bg-[#0d2230] text-slate-100' : 'bg-neutral-secondary-soft text-heading'"
                                        >
                                            <p class="truncate font-medium" :class="sportsMode ? 'text-white' : 'text-heading'">{{ playerName(player) }}</p>
                                            <div v-if="player.is_suspended" class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/40 dark:text-red-300">
                                                SUSPENDIDO
                                            </div>
                                            <p v-if="player.is_suspended && player.suspension" class="text-[10px] text-red-600 dark:text-red-300 leading-tight">
                                                {{ suspensionSummary(player.suspension) }}
                                            </p>
                                            <p class="truncate text-xs" :class="sportsMode ? 'text-slate-400' : 'text-body'">
                                                Camiseta: {{ player.jersey_number }} · {{ player.position }}
                                            </p>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex flex-col items-center gap-1">
                                                <button
                                                    type="button"
                                                    :disabled="!player.match_role"
                                                    @click="registerPlayerGoal(side.key, player)"
                                                    :title="player.match_role
                                                        ? 'Registrar un gol de ' + playerName(player) + ' con su tiempo, tipo y asistencia'
                                                        : 'Primero marca al jugador como Titular o Suplente'",
                                                    :class="[
                                                        playerGoalCount(side.key, player) > 0
                                                            ? (sportsMode ? 'border-lime-300/60 bg-lime-400/15 text-lime-200' : 'border-green-400 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300')
                                                            : (sportsMode ? 'border-[#2b4b5d] bg-[#122c3a] text-slate-400' : 'border-gray-200 bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-white'),
                                                        !player.match_role ? 'opacity-50 cursor-not-allowed' : (sportsMode ? 'hover:border-lime-300/60 hover:text-lime-200' : 'hover:border-blue-400 hover:text-blue-600'),
                                                        'inline-flex items-center gap-1 border rounded-lg px-3 py-1.5 transition-colors'
                                                    ]"
                                                >
                                                    <IconFutbol class="w-4 h-4" />
                                                    <span class="font-bold">{{ playerGoalCount(side.key, player) }}</span>
                                                </button>
                                                <button
                                                    v-if="playerGoalTimes(side.key, player)"
                                                    type="button"
                                                    @click="openGoals(side.key, playerIdOf(player))"
                                                    class="whitespace-nowrap text-[10px] underline"
                                                    :class="sportsMode ? 'text-lime-300' : 'text-blue-600'"
                                                >
                                                    {{ playerGoalTimes(side.key, player) }}
                                                </button>
                                                <span
                                                    v-if="playerOwnGoalCount(side.key, player) > 0"
                                                    class="whitespace-nowrap text-[10px]"
                                                    :class="sportsMode ? 'text-amber-300' : 'text-amber-600'"
                                                >
                                                    {{ playerOwnGoalCount(side.key, player) }} en propia puerta
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex flex-col items-center gap-1">
                                                <button
                                                    type="button"
                                                    :disabled="!player.match_role"
                                                    @click="registerPlayerSave(side.key, player)"
                                                    :title="player.match_role
                                                        ? 'Registrar una atajada de ' + playerName(player) + ' con quién la pateó, cómo fue y en qué tiempo'
                                                        : 'Primero marca al jugador como Titular o Suplente'"
                                                    :class="[
                                                        playerSaveCount(side.key, player) > 0
                                                            ? (sportsMode ? 'border-sky-300/60 bg-sky-400/15 text-sky-200' : 'border-blue-400 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300')
                                                            : (sportsMode ? 'border-[#2b4b5d] bg-[#122c3a] text-slate-400' : 'border-gray-200 bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-white'),
                                                        !player.match_role ? 'opacity-50 cursor-not-allowed' : (sportsMode ? 'hover:border-sky-300/60 hover:text-sky-200' : 'hover:border-blue-400 hover:text-blue-600'),
                                                        'inline-flex items-center gap-1 border rounded-lg px-3 py-1.5 transition-colors'
                                                    ]"
                                                >
                                                    <IconHand class="w-4 h-4" />
                                                    <span class="font-bold">{{ playerSaveCount(side.key, player) }}</span>
                                                </button>
                                                <button
                                                    v-if="playerSaveSummary(side.key, player)"
                                                    type="button"
                                                    @click="openSaves(side.key, playerIdOf(player))"
                                                    class="whitespace-nowrap text-[10px] underline"
                                                    :class="sportsMode ? 'text-sky-300' : 'text-blue-600'"
                                                >
                                                    {{ playerSaveSummary(side.key, player) }}
                                                </button>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <button
                                                @click="player.is_mvp = !player.is_mvp"
                                                :class="player.is_mvp ? 'text-yellow-500' : 'text-gray-300'"
                                                class="text-xl"
                                                title="Jugador del Partido"
                                            >
                                                <IconStarFiller v-if="player.is_mvp" class="w-5 h-5" />
                                                <IconStar v-else class="w-5 h-5" />
                                            </button>
                                        </td>
                                        <td>
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button"
                                                    @click="toggleYellow(player)"
                                                    :class="[
                                                        player.yellow_cards > 0
                                                            ? (sportsMode ? 'border-amber-300/70 bg-amber-300/15' : 'bg-yellow-50 border-yellow-400')
                                                            : (sportsMode ? 'border-[#2b4b5d] bg-transparent' : 'bg-transparent border-gray-200'),
                                                        'relative flex items-center justify-center border rounded-lg h-10 w-10 transition-all'
                                                    ]">
                                                    <img :src="'/img/tarjeta-amarilla.png'" class="w-6" :class="player.yellow_cards === 0 ? 'opacity-60' : ''" />
                                                    <span v-if="player.yellow_cards > 0"
                                                        class="absolute -right-2 -top-2 bg-yellow-500 text-white text-[10px] font-bold px-1.5 rounded-full shadow-sm">
                                                        {{ player.yellow_cards }}
                                                    </span>
                                                </button>

                                                <button type="button"
                                                    @click="toggleRed(player)"
                                                    :class="[
                                                        player.red_cards > 0
                                                            ? (sportsMode ? 'border-rose-400/70 bg-rose-400/15 ring-2 ring-rose-400/30' : 'bg-red-100 border-red-500 ring-2 ring-red-200')
                                                            : (sportsMode ? 'border-[#2b4b5d] bg-transparent' : 'bg-transparent border-gray-200'),
                                                        'flex items-center justify-center border rounded-lg h-10 w-10 transition-all'
                                                    ]">
                                                    <img :src="'/img/tarjeta-roja.png'" class="w-6" :class="player.red_cards === 0 ? 'opacity-60' : ''" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guardar -->
            <div
                class="flex flex-wrap items-center justify-end gap-2 rounded-2xl border p-4"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] shadow-[0_18px_50px_rgba(0,0,0,0.28)]' : 'border-transparent p-0 pb-10'"
            >
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                    :class="sportsMode ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'btn btn-warning text-xs'"
                    @click="goBack"
                >
                    Cancelar y volver
                </button>
                <PrimaryButton
                    type="button"
                    @click="updateMatchScore"
                    :disabled="formScore.processing"
                >
                    <div v-if="formScore.processing" class="mr-2">
                        <IconBallSoccer class="w-4 h-4 animate-bounce-ball" />
                    </div>
                    <span>
                        {{ formScore.processing ? 'Procesando resultados...' : (isCorrectingScore ? 'Guardar correcciones' : 'Guardar resultados') }}
                    </span>
                </PrimaryButton>
            </div>
        </div>

        <!-- Los modales solo reciben la piel navy cuando la organización es deportiva. -->
        <div :class="sportsMode ? 'acta-modals' : ''">
        <!-- Modal: detalle de goles (quién, cuándo, cómo y con qué asistencia) -->
        <ModalLargeXX :show="displayModalGoals" :onClose="() => displayModalGoals = false" :icon="'/img/pelota-de-futbol.png'">
            <template #title>
                {{ goalsFocusPlayerId
                    ? 'Goles de ' + (focusedGoalPlayer ? playerName(focusedGoalPlayer) : 'jugador')
                    : 'Detalle de goles' }}
            </template>
            <template #message>
                {{ goalsFocusPlayerId
                    ? teamLabel(goalsFocusTeam) + ' · quién marcó, en qué tiempo, cómo fue y quién asistió'
                    : 'Quién marcó, en qué tiempo, cómo fue y quién asistió' }}
            </template>
            <template #content>
                <div :class="goalSections.length > 1 ? 'grid lg:grid-cols-2 gap-6' : ''">
                    <div v-for="section in goalSections" :key="'goals-' + section.key">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-bold" :class="section.color">
                                {{ section.label }}
                                <span v-if="section.focused" class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-300">
                                    · {{ focusedGoalPlayer ? playerName(focusedGoalPlayer) : 'Jugador' }}
                                </span>
                            </p>
                            <button type="button" class="text-xs text-blue-600 underline" @click="addSectionGoal(section)">
                                + Agregar gol
                            </button>
                        </div>

                        <p v-if="!section.rows.length" class="text-xs text-gray-400 py-2">
                            {{ section.focused
                                ? 'Este jugador todavía no tiene goles en el detalle. Usa "+ Agregar gol" para registrar el suyo.'
                                : 'Sin goles registrados. Usa el botón de la pelota en la lista de jugadores para agregar el gol de ese jugador, o "+ Agregar gol" para registrarlo aquí mismo.' }}
                        </p>

                        <div
                            v-for="(goal, index) in section.rows"
                            :key="section.key + '-goal-' + index"
                            class="mb-3 rounded-base border border-default p-3 dark:border-blue-900"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="section.focused" class="flex-1 min-w-[12rem] text-xs font-bold">
                                    {{ focusedGoalPlayer ? playerName(focusedGoalPlayer) : 'Jugador' }}
                                </span>
                                <select v-else v-model="goal.player_id" class="form-select flex-1 min-w-[12rem] text-xs">
                                    <option :value="null">Autor del gol...</option>
                                    <option
                                        v-for="p in goalScorerCandidates(section.key, goal.player_id)"
                                        :key="playerIdOf(p)"
                                        :value="playerIdOf(p)"
                                    >
                                        {{ playerName(p) }} (#{{ p.jersey_number }})
                                    </option>
                                </select>
                                <select v-model="goal.goal_type" class="form-select w-36 text-xs">
                                    <option v-for="option in goalTypeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <button type="button" @click="removeGoalRef(section.key, goal)" class="p-2 text-red-500 hover:text-red-700">
                                    <IconTrashLines class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <select v-model="goal.period" class="form-select w-44 text-xs">
                                    <option :value="null">Sin tiempo registrado</option>
                                    <option v-for="option in periodOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <div class="flex items-center gap-1">
                                    <input
                                        v-if="goal.period"
                                        type="number"
                                        min="0"
                                        :max="periodLength(goal.period)"
                                        v-model.number="goal.minute_in_period"
                                        class="form-input w-20 text-center text-xs"
                                        placeholder="Min."
                                    />
                                    <span v-if="goal.period && goal.minute_in_period !== null" class="text-[10px] text-gray-500 whitespace-nowrap">
                                        = minuto {{ absoluteMinute(goal.period, goal.minute_in_period) }} del partido
                                    </span>
                                </div>
                                <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="checkbox" class="form-checkbox" v-model="goal.is_own_goal" />
                                    En propia puerta
                                </label>
                            </div>

                            <div class="mt-2">
                                <select v-model="goal.assist_player_id" class="form-select w-full text-xs">
                                    <option :value="null">Sin asistencia</option>
                                    <option
                                        v-for="p in goalAssistCandidates(section.key, goal)"
                                        :key="playerIdOf(p)"
                                        :value="playerIdOf(p)"
                                    >
                                        {{ playerName(p) }} (#{{ p.jersey_number }})
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-default p-3 text-xs text-gray-600 dark:border-blue-900 dark:text-gray-300">
                    <p class="font-bold uppercase text-[10px] mb-1">Cómo leer este detalle</p>
                    <p>
                        Reglamentario: 1er y 2do tiempo · Alargue: 1er y 2do suplementario · Penales: la tanda, que se registra aparte
                        porque no son goles del juego. Los goles sin tiempo quedan como "sin tiempo registrado" y no se reparten solos.
                    </p>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="displayModalGoals = false">Listo</PrimaryButton>
            </template>
        </ModalLargeXX>

        <!-- Modal: detalle de atajadas (cómo fue, quién la pateó y en qué tiempo) -->
        <ModalLargeXX :show="displayModalSaves" :onClose="() => displayModalSaves = false" :icon="'/img/juego.png'">
            <template #title>
                {{ savesFocusPlayerId
                    ? 'Atajadas de ' + (focusedSavePlayer ? playerName(focusedSavePlayer) : 'arquero')
                    : 'Detalle de atajadas' }}
            </template>
            <template #message>
                {{ savesFocusPlayerId
                    ? teamLabel(savesFocusTeam) + ' · cómo fue la atajada, quién la pateó y en qué tiempo'
                    : 'Cómo fue cada atajada, quién la pateó y en qué tiempo' }}
            </template>
            <template #content>
                <div :class="saveSections.length > 1 ? 'grid lg:grid-cols-2 gap-6' : ''">
                    <div v-for="section in saveSections" :key="'saves-' + section.key">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-bold" :class="section.color">
                                {{ section.label }}
                                <span v-if="section.focused" class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-300">
                                    · {{ focusedSavePlayer ? playerName(focusedSavePlayer) : 'Arquero' }}
                                </span>
                            </p>
                            <button type="button" class="text-xs text-blue-600 underline" @click="addSectionSave(section)">
                                + Agregar atajada
                            </button>
                        </div>

                        <p v-if="!section.rows.length" class="text-xs text-gray-400 py-2">
                            {{ section.focused
                                ? 'Este arquero todavía no tiene atajadas en el detalle. Usa "+ Agregar atajada" para registrar la primera.'
                                : 'Sin atajadas registradas. Usa el botón del guante en la lista de jugadores para registrar la atajada de ese arquero, o "+ Agregar atajada" para registrarla aquí mismo.' }}
                        </p>

                        <div
                            v-for="(save, index) in section.rows"
                            :key="section.key + '-save-' + index"
                            class="mb-3 rounded-base border border-default p-3 dark:border-blue-900"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="section.focused" class="flex-1 min-w-[12rem] text-xs font-bold">
                                    {{ focusedSavePlayer ? playerName(focusedSavePlayer) : 'Arquero' }}
                                </span>
                                <select v-else v-model="save.player_id" class="form-select flex-1 min-w-[12rem] text-xs">
                                    <option :value="null">Arquero que atajó...</option>
                                    <option
                                        v-for="p in saveKeeperCandidates(section.key, save.player_id)"
                                        :key="playerIdOf(p)"
                                        :value="playerIdOf(p)"
                                    >
                                        {{ playerName(p) }} (#{{ p.jersey_number }})
                                    </option>
                                </select>
                                <select v-model="save.save_type" class="form-select w-40 text-xs">
                                    <option v-for="option in saveTypeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="checkbox" class="form-checkbox" v-model="save.is_penalty" />
                                    Fue penal
                                </label>
                                <button type="button" @click="removeSaveRef(section.key, save)" class="p-2 text-red-500 hover:text-red-700">
                                    <IconTrashLines class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <select v-model="save.period" class="form-select w-44 text-xs">
                                    <option :value="null">Sin tiempo registrado</option>
                                    <option v-for="option in savePeriodOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <div v-if="save.period && save.period !== PENALTY_SHOOTOUT" class="flex items-center gap-1">
                                    <input
                                        type="number"
                                        min="0"
                                        :max="periodLength(save.period)"
                                        v-model.number="save.minute_in_period"
                                        class="form-input w-20 text-center text-xs"
                                        placeholder="Min."
                                    />
                                    <span v-if="save.minute_in_period !== null" class="text-[10px] text-gray-500 whitespace-nowrap">
                                        = minuto {{ absoluteMinute(save.period, save.minute_in_period) }} del partido
                                    </span>
                                </div>
                                <span v-else-if="save.period === PENALTY_SHOOTOUT" class="text-[10px] text-gray-500">
                                    La tanda no se mide en minutos de juego
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="text-xs text-gray-500 whitespace-nowrap">Quién la pateó:</span>
                                <select v-model="save.shooter_player_id" class="form-select flex-1 min-w-[11rem] text-xs">
                                    <option :value="null">Sin registrar</option>
                                    <option
                                        v-for="p in saveShooterCandidates(section.key)"
                                        :key="playerIdOf(p)"
                                        :value="playerIdOf(p)"
                                    >
                                        {{ playerName(p) }} (#{{ p.jersey_number }})
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-default p-3 text-xs text-gray-600 dark:border-blue-900 dark:text-gray-300">
                    <p class="font-bold uppercase text-[10px] mb-1">Cómo leer este detalle</p>
                    <p>
                        Cada atajada guarda cómo llegó el remate (tiro, uno contra uno, cabeza, tiro olímpico), si fue penal y quién la pateó.
                        El minuto es el del tiempo elegido; la ronda de penales se marca aparte porque no se mide en minutos de juego, y la prórroga
                        solo se puede elegir cuando el partido la jugó. El total del arquero en la lista es el número de atajadas registradas aquí.
                    </p>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="displayModalSaves = false">Listo</PrimaryButton>
            </template>
        </ModalLargeXX>

        <!-- Modal: cambios de jugadores -->
        <ModalLargeXX :show="displayModalSubs" :onClose="() => displayModalSubs = false" :icon="'/img/juego.png'">
            <template #title>Cambios del partido</template>
            <template #message>Salió, entró, en qué tiempo y por qué</template>
            <template #content>
                <div class="grid lg:grid-cols-2 gap-6">
                    <div
                        v-for="side in [
                            { key: 'local', label: formScore.equipo_h_name, color: 'text-blue-600' },
                            { key: 'visitor', label: formScore.equipo_a_name, color: 'text-red-600' },
                        ]"
                        :key="side.key"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-bold" :class="side.color">{{ side.label }}</p>
                            <button type="button" class="text-xs text-blue-600 underline" @click="addSubstitution(side.key)">
                                + Agregar cambio
                            </button>
                        </div>

                        <p v-if="!teamSubstitutions(side.key).length" class="text-xs text-gray-400 py-2">
                            Sin cambios registrados. Con solo marcar Titular o Suplente, los minutos se reparten solos.
                        </p>

                        <div
                            v-for="(sub, index) in teamSubstitutions(side.key)"
                            :key="side.key + '-sub-' + index"
                            class="mb-3 rounded-base border border-default p-2 dark:border-blue-900"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <select v-model="sub.player_out_id" class="form-select flex-1 min-w-[11rem] text-xs">
                                    <option :value="null">Sale...</option>
                                    <option
                                        v-for="p in substitutionCandidates(side.key, 'out', sub)"
                                        :key="playerIdOf(p)"
                                        :value="playerIdOf(p)"
                                    >
                                        {{ playerName(p) }} (#{{ p.jersey_number }})
                                    </option>
                                </select>
                                <select v-model="sub.player_in_id" class="form-select flex-1 min-w-[11rem] text-xs">
                                    <option :value="null">Entra...</option>
                                    <option
                                        v-for="p in substitutionCandidates(side.key, 'in', sub)"
                                        :key="playerIdOf(p)"
                                        :value="playerIdOf(p)"
                                    >
                                        {{ playerName(p) }} (#{{ p.jersey_number }})
                                    </option>
                                </select>
                                <select v-model="sub.period" class="form-select w-36 text-xs">
                                    <option v-for="option in periodOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <input
                                    type="number"
                                    min="0"
                                    :max="periodLength(sub.period)"
                                    v-model.number="sub.minute_in_period"
                                    class="form-input w-20 text-center text-xs"
                                    placeholder="Min."
                                />
                                <select v-model="sub.reason" class="form-select w-32 text-xs">
                                    <option v-for="option in reasonOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <button type="button" @click="removeSubstitution(side.key, index)" class="p-2 text-red-500 hover:text-red-700">
                                    <IconTrashLines class="w-4 h-4" />
                                </button>
                            </div>
                            <p class="mt-1 text-[10px] text-gray-500">
                                {{ periodLabel(sub.period) }} · minuto {{ sub.minute_in_period ?? 0 }}
                                → minuto {{ absoluteMinute(sub.period, sub.minute_in_period) }} del partido
                                <span v-if="sub.reason"> · {{ reasonLabel(sub.reason) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="displayModalSubs = false">Listo</PrimaryButton>
            </template>
        </ModalLargeXX>
        </div>
    </AppLayout>
</template>

<style scoped>
    /* Columnas fijas de las tablas del acta: el nombre del jugador debe
       permanecer visible al hacer scroll horizontal. */
    .se-score-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .se-score-sticky {
        position: sticky;
        z-index: 5;
        min-width: 8rem;
    }

    .se-score-sticky--role {
        left: 0;
        z-index: 6;
        min-width: 7rem;
        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.06);
    }

    .se-score-sticky--name {
        left: 7rem;
        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.06);
    }

    /* Los tres modales del acta heredan la tarjeta azul noche y los acentos lima
       del resto de la pantalla, sin alterar el tema global de los modales. */
    .acta-modals :deep(.modal-torneos) {
        background-color: #0b1f2b;
        border-color: #234052;
        color: #e2e8f0;
    }

    .acta-modals :deep(.modal-torneos__header) {
        background: linear-gradient(110deg, #0b1f2b, #122c3a);
        border-color: #234052;
    }

    .acta-modals :deep(.modal-torneos__icon) {
        background-color: #122c3a;
    }

    .acta-modals :deep(.modal-torneos__title) {
        color: #f8fafc;
    }

    .acta-modals :deep(.modal-torneos__message) {
        color: #94a3b8;
    }

    .acta-modals :deep(.modal-torneos__body) {
        background-color: #081a25;
        color: #e2e8f0;
        --torneos-control-bg: #0e2532;
        --torneos-control-text: #e2e8f0;
        --torneos-control-border: #2b4b5d;
        --torneos-control-placeholder: #64748b;
        --torneos-dropdown-bg: #122c3a;
    }

    .acta-modals :deep(.modal-torneos__actions) {
        border-color: #234052;
    }

    .acta-modals :deep(.modal-torneos__close) {
        background-color: #122c3a;
        color: #94a3b8;
    }

    .acta-modals :deep(.modal-torneos__close:hover) {
        background-color: #17394a;
        color: #e2e8f0;
    }

    .acta-modals :deep(.modal-torneos__body .text-gray-500) {
        color: #94a3b8;
    }

    .acta-modals :deep(.modal-torneos__body .text-gray-400) {
        color: #64748b;
    }

    .acta-modals :deep(.modal-torneos__body .text-gray-600) {
        color: #cbd5e1;
    }

    .acta-modals :deep(.modal-torneos__body .text-blue-600) {
        color: #bef264;
    }

    .acta-modals :deep(.modal-torneos__body .border-default) {
        border-color: #234052;
    }

    .acta-modals :deep(.modal-torneos__body .mb-3.rounded-base.border) {
        background-color: #0b1f2b;
    }

    .acta-modals :deep(.modal-torneos__body .mt-4.rounded-lg.border) {
        background-color: #0e2532;
    }
</style>
