import assert from 'node:assert/strict';
import test from 'node:test';

import {
    MIN_GEAR_Z,
    TOGGLE_SIZES,
    centerOf,
    containsPoint,
    fitsInBounds,
    gearZIndex,
    intersects,
    pickSlot,
    sizeFor,
    slotAt,
    slotCost,
    stackingZIndexOf,
} from '../../Modules/Security/Resources/assets/js/Plugins/superEditorPlacement.js';

// Ventana de referencia: los huecos que se salgan de aquí no sirven y el
// recorte de position:fixed los dejaría encima del elemento.
const viewport = { width: 1440, height: 900 };

/**
 * El toggle se coloca con rectángulos, no con el DOM: la promesa que se
 * verifica aquí es la de paridad de clicks. El toggle es transparente al puntero
 * y su propio click se reconoce por geometría, así que su rectángulo no puede
 * caer sobre el centro del elemento que decora ni sobre el centro de otro
 * control (ahí el click pertenece a ese control y la página debe seguir
 * comportándose como si el editor no existiera).
 */

const box = (left, top, width, height) => ({
    left,
    top,
    right: left + width,
    bottom: top + height,
    width,
    height,
});

// Fila de acciones: botón de 28x28 y, 8px después, su vecino.
const editar = box(400, 300, 28, 28);
const eliminar = box(436, 300, 28, 28);

const blockerFor = (rect) => ({ rect, center: centerOf(rect) });

test('el toggle es chico para controles chicos', () => {
    assert.equal(sizeFor(editar), 'sm', 'botón de icono');
    assert.equal(sizeFor(box(0, 0, 48, 48)), 'sm', 'botón del rail de módulos');
    assert.equal(sizeFor(box(0, 0, 208, 40)), 'md', 'fila de menú');
    assert.equal(sizeFor(null), 'md', 'sin caja');
});

test('el hueco «end» vive dentro del extremo derecho y no toca el centro', () => {
    const row = box(20, 100, 208, 40);
    const slot = slotAt(row, 'end', sizeFor(row));

    assert.ok(slot, 'cabe en una fila ancha');
    assert.equal(slot.right, row.right - 6, 'pegado al borde derecho');
    assert.equal(containsPoint(slot, row.right - 2, 120), false, 'deja libre el borde');
    assert.equal(containsPoint(slot, centerOf(row).x, centerOf(row).y), false, 'no pisa el centro de la fila');

    assert.equal(slotAt(box(0, 0, 40, 40), 'end', 'sm'), null, 'en un control estrecho no cabe');
});

test('el hueco «before-end» deja libre el chevron de la fila', () => {
    const row = box(20, 100, 208, 44);
    const slot = slotAt(row, 'before-end', sizeFor(row));
    // El chevron (y su padding) ocupa los últimos ~28px de la fila.
    const chevron = box(row.right - 28, 110, 16, 16);

    assert.ok(slot);
    assert.equal(intersects(slot, chevron), false, 'no se solapa con el chevron');
    assert.ok(slot.right <= row.right - 32, `derecho en ${slot.right}, fila hasta ${row.right}`);
});

test('el hueco «corner» no pisa el centro de un botón de 48x48', () => {
    const rail = box(16, 100, 48, 48);
    const slot = slotAt(rail, 'corner', sizeFor(rail));

    assert.ok(slot);
    assert.equal(containsPoint(slot, centerOf(rail).x, centerOf(rail).y), false, 'centro del icono libre');
    assert.equal(slot.top, rail.top, 'arranca en el borde superior');
});

test('el coste del hueco distingue libre, pisa cajas y pisa centros', () => {
    const row = box(20, 100, 208, 40);
    const center = centerOf(row);

    assert.equal(slotCost(null, center, []), 3, 'no cabe');
    assert.equal(slotCost(box(0, 0, 30, 16), center, []), 0, 'libre');
    assert.equal(slotCost(box(20, 100, 30, 16), center, [blockerFor(row)]), 1, 'pisa la caja de otro control');
    assert.equal(
        slotCost(box(center.x - 4, center.y - 4, 30, 16), center, []),
        2,
        'pisa el centro del propio elemento'
    );
    assert.equal(
        slotCost(box(center.x - 4, center.y - 4, 30, 16), null, [blockerFor(row)]),
        2,
        'pisa el centro de otro control'
    );
});

test('una fila de acciones no manda el toggle encima del botón vecino', () => {
    const picked = pickSlot(editar, sizeFor(editar), [blockerFor(editar), blockerFor(eliminar)], viewport);

    // A la derecha está el vecino: se va al otro lado, donde no pisa nada.
    assert.equal(picked.place, 'outside-left');
    assert.equal(picked.rect.right, editar.left - 6);
    assert.equal(intersects(picked.rect, eliminar), false, 'no pisa la caja del vecino');
    assert.equal(intersects(picked.rect, editar), false, 'ni la del elemento que decora');
    assert.equal(
        containsPoint(picked.rect, centerOf(eliminar).x, centerOf(eliminar).y),
        false,
        'el centro del vecino queda libre: su click sigue siendo suyo'
    );
    assert.equal(
        containsPoint(picked.rect, centerOf(editar).x, centerOf(editar).y),
        false,
        'el centro del elemento decorado también'
    );
    assert.equal(picked.rect.width, TOGGLE_SIZES.sm.width, 'y con el tamaño chico');
});

test('si el hueco natural está ocupado por otro centro, se busca alternativa', () => {
    const row = box(20, 100, 208, 40);
    // Otro control justo donde caería el hueco exterior derecho.
    const tapon = box(row.right + 8, 108, 24, 24);

    const picked = pickSlot(row, sizeFor(row), [blockerFor(tapon)], viewport);

    assert.equal(
        containsPoint(picked.rect, centerOf(tapon).x, centerOf(tapon).y),
        false,
        'evita el centro del control que tapaba el hueco'
    );
    assert.equal(picked.place, 'above', 'sigue buscando fuera del elemento, por encima de él');
    assert.equal(intersects(picked.rect, row), false, 'y sin pisar su caja clickeable');
});

test('un botón ancho recibe el toggle a su derecha, no dentro', () => {
    // Botón «NUEVO» medido en la app: 90x35, con hueco de sobra a la derecha.
    const nuevo = box(800, 148, 90, 35);
    const picked = pickSlot(nuevo, sizeFor(nuevo), [blockerFor(nuevo)], viewport);

    assert.equal(picked.place, 'outside-right');
    assert.equal(picked.rect.left, nuevo.right + 6);
    assert.equal(
        intersects(picked.rect, nuevo),
        false,
        'dentro del botón el toggle le robaría el click: el hueco va fuera'
    );
});

test('el hueco tiene que caber en la ventana', () => {
    const pegado = box(1340, 400, 90, 35);
    const picked = pickSlot(pegado, sizeFor(pegado), [blockerFor(pegado)], viewport);

    assert.equal(fitsInBounds(slotAt(pegado, 'outside-right', 'sm'), viewport), false, 'a la derecha se sale');
    assert.equal(fitsInBounds(picked.rect, viewport), true, `el hueco elegido (${picked.place}) sí cabe`);
    assert.equal(intersects(picked.rect, pegado), false, 'y sigue estando fuera del elemento');

    assert.equal(fitsInBounds(null, viewport), false, 'sin hueco no cabe');
    assert.equal(fitsInBounds(box(0, 0, 10, 10), null), true, 'sin ventana conocida no se descarta nada');
});

test('sin ventana, el hueco de dentro sigue siendo el último recurso', () => {
    const soloDentro = box(0, 0, 60, 24);
    const picked = pickSlot(soloDentro, 'sm', []);

    assert.equal(picked.place, 'outside-right', 'fuera del elemento es lo primero');
    assert.equal(picked.rect.left, soloDentro.right + 6);
});

test('sin caja no hay hueco y el respaldo sigue siendo la derecha', () => {
    const picked = pickSlot(null, 'md', []);

    assert.equal(picked.place, 'outside-right');
    assert.equal(picked.rect, null);
});

/** Cadena de ancestros falsa: cada nivel con su estilo, para no depender del DOM. */
const chain = (styles) => {
    let parent = null;

    for (const style of styles) {
        parent = { nodeType: 1, style, parentElement: parent };
    }

    return parent;
};

const reader = (node) => node.style ?? null;

test('el toggle se apila por encima del menú que contiene al elemento', () => {
    const staticNode = { position: 'static', zIndex: 'auto' };

    assert.equal(
        stackingZIndexOf(chain([staticNode, { position: 'absolute', zIndex: '1050' }, staticNode]), reader),
        1050,
        'menú de ant-design'
    );
    assert.equal(
        gearZIndex(chain([staticNode, { position: 'fixed', zIndex: '9999' }, staticNode]), reader),
        10000,
        'justo por encima de un popup de vue3-popper'
    );
});

test('el z-index del toggle nunca baja del mínimo del editor', () => {
    const staticNode = { position: 'static', zIndex: 'auto' };

    assert.equal(stackingZIndexOf(null, reader), 0, 'sin elemento');
    assert.equal(stackingZIndexOf(chain([staticNode, staticNode]), reader), 0, 'página sin apilado');
    assert.equal(
        stackingZIndexOf(chain([staticNode, { position: 'absolute', zIndex: 'auto' }]), reader),
        0,
        'un contenedor posicionado sin z-index no eleva nada'
    );
    assert.equal(gearZIndex(chain([staticNode]), reader), MIN_GEAR_Z);
    assert.equal(gearZIndex(null, reader), MIN_GEAR_Z);
});
