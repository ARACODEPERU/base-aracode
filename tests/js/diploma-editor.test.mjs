import assert from 'node:assert/strict';
import test from 'node:test';

import { buildSquadItems, resolveDiplomaTokens } from '../../Modules/Socialevents/Resources/assets/js/utils/diplomaLayout.js';

const tokens = {
    premio: 'Mejor jugador',
    premio_ganador: 'Luis Ramos',
    torneo: 'Campeonato de Exalumnos',
    edicion: '2026',
    goles: '12',
};

test('resuelve las variables del layout con los valores del servidor', () => {
    assert.equal(resolveDiplomaTokens('{{premio_ganador}}', tokens), 'Luis Ramos');
    assert.equal(
        resolveDiplomaTokens('{{torneo}} · {{edicion}}', tokens),
        'Campeonato de Exalumnos · 2026'
    );
});

test('tolera espacios y mayúsculas en las variables', () => {
    assert.equal(resolveDiplomaTokens('{{  premio  }}', tokens), 'Mejor jugador');
    assert.equal(resolveDiplomaTokens('{{ PREMIO_GANADOR }}', tokens), 'Luis Ramos');
});

test('deja visible una variable desconocida para poder diseñar con ella', () => {
    assert.equal(resolveDiplomaTokens('Goles: {{goles}} {{desconocida}}', tokens), 'Goles: 12 {{desconocida}}');
});

test('mantiene el texto sin cambios cuando no hay valores resueltos', () => {
    assert.equal(resolveDiplomaTokens('{{premio}}', {}), '{{premio}}');
    assert.equal(resolveDiplomaTokens(null, tokens), '');
});

test('aplica lo que el usuario cambia en el inspector del plantel', () => {
    const players = [
        { name: 'Luis Ramos', jersey: 10, position: 'Delantero' },
        { name: 'Ana Torres', jersey: 2, position: 'Defensa' },
    ];

    const byNumber = buildSquadItems({ sortBy: 'number', showJerseyNumber: true }, players);

    assert.deepEqual(byNumber.map((item) => item.line), ['2. Ana Torres', '10. Luis Ramos']);

    const byName = buildSquadItems({ sortBy: 'name', showJerseyNumber: false }, players);

    assert.deepEqual(byName.map((item) => item.line), ['Ana Torres', 'Luis Ramos']);
});

test('muestra la posición solo cuando se activa y soporta un plantel vacío', () => {
    const players = [{ name: 'Luis Ramos', jersey: 10, position: 'delantero' }];

    assert.equal(buildSquadItems({ showPosition: true }, players)[0].line, '10. Luis Ramos (Delantero)');
    assert.equal(buildSquadItems({ showPosition: false }, players)[0].line, '10. Luis Ramos');
    assert.deepEqual(buildSquadItems({}, []), []);
    assert.deepEqual(buildSquadItems({}, null), []);
});
