import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

const layout = readFileSync(new URL('../../resources/js/Layouts/Vristo/AppLayout.vue', import.meta.url), 'utf8');
const authLayout = readFileSync(new URL('../../resources/js/Layouts/Vristo/AuthLayout.vue', import.meta.url), 'utf8');
const sidebar = readFileSync(new URL('../../resources/js/Components/vristo/layout/Sidebar-Admin.vue', import.meta.url), 'utf8');
const estilos = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8');
const formSection = readFileSync(new URL('../../resources/js/Components/FormSection.vue', import.meta.url), 'utf8');
const keypad = readFileSync(new URL('../../resources/js/Components/Keypad.vue', import.meta.url), 'utf8');
const navegador = readFileSync(new URL('../../resources/js/Components/vristo/layout/Navigation.vue', import.meta.url), 'utf8');
const listaEquipos = readFileSync(new URL('../../Modules/Socialevents/Resources/assets/js/Pages/Teams/List.vue', import.meta.url), 'utf8');
const cuentasOrganizaciones = readFileSync(new URL('../../Modules/Socialevents/Resources/assets/js/Pages/Organizations/Accounts.vue', import.meta.url), 'utf8');
const listaLocales = readFileSync(new URL('../../Modules/Socialevents/Resources/assets/js/Pages/Local/List.vue', import.meta.url), 'utf8');
const actaPartido = readFileSync(new URL('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/MatchScore.vue', import.meta.url), 'utf8');
const formularioCrearLocales = readFileSync(new URL('../../Modules/Socialevents/Resources/assets/js/Pages/Local/Partials/CreateForm.vue', import.meta.url), 'utf8');
const footer = readFileSync(new URL('../../resources/js/Components/vristo/layout/Footer.vue', import.meta.url), 'utf8');
const paginacion = readFileSync(new URL('../../resources/js/Components/Pagination.vue', import.meta.url), 'utf8');
const modales = ['ModalSmall.vue', 'ModalLarge.vue', 'ModalLargeX.vue', 'ModalLargeXX.vue'].map((nombre) => ({
    nombre,
    contenido: readFileSync(new URL(`../../resources/js/Components/${nombre}`, import.meta.url), 'utf8'),
}));

test('el tema del sistema depende del modo deportivo compartido por el servidor', () => {
    assert.match(layout, /sportsTheme\s*=\s*computed\(\(\)\s*=>\s*page\.props\.sportsOrganization\?\.mode\s*===\s*true\)/);
    assert.match(layout, /sportsTheme\.value\s*\?\s*\{\s*primary:\s*'#a3e635',\s*secondary:\s*'#10b981'/);
    assert.match(layout, /:\s*\{\s*primary:\s*companyData\?\.brand_primary,\s*secondary:\s*companyData\?\.brand_secondary/);
    assert.match(layout, /sportsTheme\s*\?\s*'theme-torneos'\s*:\s*''/);
    assert.match(authLayout, /sportsTheme\s*=\s*computed\(\(\)\s*=>\s*page\.props\.sportsOrganization\?\.mode\s*===\s*true\)/);
    assert.match(authLayout, /sportsTheme\s*\?\s*'theme-torneos'\s*:\s*''/);
});

test('los estilos futboleros usan el mismo fondo de estadio del login en el cuerpo', () => {
    const login = readFileSync(new URL('../../resources/js/Pages/Auth/LoginOrganizer.vue', import.meta.url), 'utf8');

    assert.match(login, /estadio:\s*'\/img\/torneos\/chaos-soccer-gear-Cjfl8r_eYxY-unsplash\.jpg'/);
    assert.match(estilos, /--torneos-pitch-image:\s*url\('\/img\/torneos\/chaos-soccer-gear-Cjfl8r_eYxY-unsplash\.jpg'\)/);
    assert.match(estilos, /html\.theme-torneos \.main-content > \.p-6\.animation\s*\{\s*background-color:\s*transparent;\s*background-image:\s*none;/);
    assert.match(estilos, /linear-gradient\(120deg, rgb\(2 44 34 \/ 88%\)/);
});

test('los estilos futboleros incluyen header, sidebar, botones y diálogos', () => {
    assert.match(estilos, /html\.theme-torneos \.main-content > header/);
    assert.match(estilos, /html\.theme-torneos nav\.sidebar/);
    assert.match(estilos, /html\.theme-torneos nav\.sidebar > #divSiempreVisible > div:nth-child\(2\)\s*\{\s*background-color:\s*#f0fdf4/);
    assert.match(estilos, /html\.theme-torneos nav\.sidebar \.sidebar-option-item > :is\(button, a\)/);
    assert.match(estilos, /html\.theme-torneos nav\.sidebar \.sidebar-option-item > :is\(button, a\)\.border-blue-200/);
    assert.match(estilos, /html\.theme-torneos nav\.sidebar \.sidebar-option-item > :is\(button, a\)\.border-orange-200/);
    assert.match(estilos, /html\.theme-torneos \.dark nav\.sidebar \.sidebar-option-item > :is\(button, a\)/);
    assert.match(estilos, /html\.theme-torneos nav\.sidebar \.sidebar-option-item a\.rounded-lg\.bg-blue-50/);
    assert.match(sidebar, /class="sidebar fixed/);
    assert.match(sidebar, /class="sidebar-option-item group relative"/);
    assert.match(sidebar, /const optionLinkClasses =/);
    assert.doesNotMatch(estilos, /^nav\.sidebar \.sidebar-option-item/m);
    assert.match(estilos, /html\.theme-torneos \.btn-primary/);
    assert.match(estilos, /html\.theme-torneos \.swal2-popup/);
    assert.match(estilos, /html\.theme-torneos \.ant-modal-content/);
});

test('las tarjetas panel y el navegador contrastan con el fondo solo en tema Torneos', () => {
    assert.match(estilos, /html\.theme-torneos \.panel\s*\{\s*background-color:\s*var\(--torneos-panel-surface\)/);
    assert.match(estilos, /--torneos-panel-surface:\s*rgb\(224 236 229 \/ 94%\)/);
    assert.match(estilos, /html\.theme-torneos \.panel > div:first-of-type\s*\{\s*background-color:\s*var\(--torneos-panel-header-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.dark \.panel\s*\{\s*background-color:\s*var\(--torneos-panel-surface\)/);
    assert.match(estilos, /html\.theme-torneos nav\[aria-label="Breadcrumb"\] > ol\s*\{\s*background-color:\s*rgb\(2 44 34 \/ 72%\)/);
    assert.match(estilos, /html\.theme-torneos nav\[aria-label="Breadcrumb"\] > ol a\s*\{\s*color:\s*#ecfdf5/);
    assert.match(estilos, /html\.theme-torneos nav\[aria-label="Breadcrumb"\] > ol > li > div > span:last-child\s*\{\s*background-color:\s*#bef264/);
    assert.match(listaEquipos, /class="team-card relative bg-white/);
    assert.match(navegador, /aria-label="Breadcrumb"/);
    assert.doesNotMatch(estilos, /^\.panel\s*\{/m);
    assert.doesNotMatch(estilos, /^nav\[aria-label="Breadcrumb"\]/m);
    assert.match(estilos, /html\.theme-torneos \.dark \.panel\s*\{/);
    assert.match(estilos, /html\.theme-torneos \.dark \.team-card\s*\{/);
    assert.match(estilos, /html\.theme-torneos \.dark nav\[aria-label="Breadcrumb"\] > ol\s*\{/);
});

test('el acta del partido usa tarjetas del tema y textos legibles sobre el estadio', () => {
    // Los bloques del acta dejan de ser transparentes: cada sección lleva su clase.
    assert.match(actaPartido, /class="sports-match-score-page/);
    assert.match(actaPartido, /class="se-acta-header /);
    assert.match(actaPartido, /class="se-acta-card rounded-xl/);
    assert.match(actaPartido, /class="se-acta-subcard /);
    assert.match(actaPartido, /class="se-acta-card se-acta-card--penales/);
    assert.match(actaPartido, /class="se-acta-card se-acta-card--flush w-full/);
    assert.match(actaPartido, /se-acta-table/);
    assert.match(actaPartido, /class="se-acta-toggle /);

    // Superficie, borde, sombra y color salen de los tokens del tema.
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-card\s*\{[^}]*background-color:\s*var\(--torneos-panel-surface\);[^}]*border:\s*1px solid var\(--torneos-panel-border\);[^}]*box-shadow:\s*var\(--torneos-panel-shadow\);[^}]*color:\s*var\(--torneos-panel-text\);/);
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-subcard\s*\{[^}]*background-color:\s*var\(--torneos-panel-header-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-card :is\(\.text-gray-400, \.text-gray-500, \.text-gray-600, \.text-gray-700, \.text-body\)\s*\{\s*color:\s*var\(--torneos-panel-muted-text\)/);

    // Cabecera y alineaciones no dependen del gris ni del azul fijos.
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-header\s*\{[^}]*background-color:\s*rgb\(2 44 34 \/ 78%\)/);
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-table thead th\s*\{[^}]*background-color:\s*var\(--torneos-panel-header-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-table tbody td\s*\{[^}]*background-color:\s*rgb\(255 255 255 \/ 72%\)/);

    // El modo oscuro se apoya en los mismos tokens y solo ajusta acentos.
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-toggle\s*\{[^}]*background-color:\s*rgb\(2 44 34 \/ 62%\)/);
    assert.match(estilos, /html\.theme-torneos \.sports-match-score-page \.se-acta-card--flush\s*\{\s*overflow:\s*hidden;/);
    assert.match(estilos, /html\.theme-torneos body\.dark \.sports-match-score-page \.se-acta-table thead th\s*\{\s*background-color:\s*#0b3b30/);
    assert.match(estilos, /html\.theme-torneos body\.dark \.sports-match-score-page \.se-acta-card--penales\s*\{/);

    // Ninguna regla del acta se aplica fuera del tema futbolero.
    assert.doesNotMatch(estilos, /^\.se-acta-card\s*\{/m);
    assert.doesNotMatch(estilos, /^\.se-acta-header\s*\{/m);
    assert.doesNotMatch(estilos, /^\.se-acta-table\s*\{/m);
});

test('los cuatro modales generales exponen ganchos de tema para encabezado, cuerpo y acciones', () => {
    for (const { nombre, contenido } of modales) {
        assert.match(contenido, /class="modal-torneos\s/ , `${nombre}: panel del modal`);
        assert.match(contenido, /modal-torneos__header/, `${nombre}: encabezado`);
        assert.match(contenido, /modal-torneos__body/, `${nombre}: cuerpo`);
        assert.match(contenido, /modal-torneos__actions/, `${nombre}: acciones`);
        assert.match(contenido, /modal-torneos__close-button/, `${nombre}: botón de cierre`);
    }

    assert.match(estilos, /html\.theme-torneos \.modal-torneos__header\s*\{\s*background:\s*linear-gradient\(110deg, var\(--torneos-deep\), var\(--torneos-forest\)\)/);
    assert.match(estilos, /html\.theme-torneos \.modal-torneos__body\s*\{\s*background-color:\s*#f0fdf4/);
    assert.match(estilos, /html\.theme-torneos \.modal-torneos \.modal-torneos__close-button/);
    assert.match(estilos, /html\.theme-torneos \.dark \.modal-torneos\s*\{/);
    assert.doesNotMatch(estilos, /^\.modal-torneos(?:__|\s)/m);
});

test('FormSection conserva la alineación original sin alterar tamaño ni espaciado de botones', () => {
    assert.match(formSection, /class="form-section__card panel/);
    assert.match(formSection, /class="form-section__actions/);
    assert.match(formSection, /form-section__actions--torneos/);
    assert.match(keypad, /flex flex-col items-center space-x-2 md:flex-row md:justify-end space-y-2 md:space-y-0/);
    assert.match(keypad, /class="btn btn-info text-xs uppercase"/);
    assert.match(keypad, /<slot name="botones" \/>/);
    assert.match(estilos, /html\.theme-torneos \.form-section__actions--torneos > \.flex\s*\{\s*align-items:\s*center/);
    assert.doesNotMatch(estilos, /html\.theme-torneos \.form-section__actions--torneos[^}]*\b(?:min-width|min-height|padding|margin|flex|width|height):/);
    assert.doesNotMatch(estilos, /^\.form-section__card\s*\{/m);
    assert.doesNotMatch(estilos, /^\.form-section__actions\s*\{/m);
});

test('los paneles usan tonos contenidos y variables reutilizables en Torneos', () => {
    assert.match(estilos, /--torneos-panel-surface:\s*rgb\(224 236 229 \/ 94%\)/);
    assert.match(estilos, /--torneos-panel-header-surface:\s*rgb\(210 226 217 \/ 96%\)/);
    assert.match(estilos, /html\.theme-torneos \.panel\s*\{\s*background-color:\s*var\(--torneos-panel-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.panel > div:first-of-type\s*\{\s*background-color:\s*var\(--torneos-panel-header-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.dark \.panel\s*\{\s*background-color:\s*var\(--torneos-panel-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.sports-locals-page \.sports-locals-panel\s*\{[^}]*background-color:\s*var\(--torneos-panel-surface\);[^}]*border-color:\s*var\(--torneos-panel-border\);[^}]*box-shadow:\s*var\(--torneos-panel-shadow\);[^}]*color:\s*var\(--torneos-panel-text\);/);
    assert.match(estilos, /html\.theme-torneos body\.dark\s*\{[^}]*--torneos-panel-surface:\s*rgb\(5 46 36 \/ 90%\)/);
    assert.match(estilos, /html\.theme-torneos \.dark \.panel\s*\{\s*background-color:\s*var\(--torneos-panel-surface\)/);
    assert.match(estilos, /html\.theme-torneos body\.dark \.sports-locals-page \.sports-locals-panel\s*\{\s*background-color:\s*var\(--torneos-panel-surface\)/);
});

test('FormSection adopta el panel esmeralda y textos legibles solo con PW00001 = 2', () => {
    assert.match(estilos, /html\.theme-torneos \{\s*[^}]*--torneos-form-surface-start:\s*rgb\(6 78 59 \/ 96%\)/);
    assert.match(estilos, /--torneos-form-text:\s*#ecfdf5/);
    assert.match(estilos, /html\.theme-torneos \.form-section \.form-section__card\.panel\s*\{[^}]*background:\s*linear-gradient\(145deg, var\(--torneos-form-surface-start\), var\(--torneos-form-surface-end\)\);[^}]*border:\s*1px solid var\(--torneos-form-border\);[^}]*color:\s*var\(--torneos-form-text\);/);
    assert.match(estilos, /html\.theme-torneos \.form-section \.form-section-header :is\(h1, h2, h3, h4, h5, h6\)\s*\{\s*color:\s*#f0fdf4;/);
    assert.match(estilos, /html\.theme-torneos \.form-section \.form-section-header p\s*\{\s*color:\s*var\(--torneos-form-muted\);/);
    assert.match(estilos, /html\.theme-torneos \.form-section__card label\s*\{\s*color:\s*var\(--torneos-form-text\);/);
    assert.match(estilos, /html\.theme-torneos \.form-section__card :is\(input, select, textarea\)\s*\{[^}]*background-color:\s*var\(--torneos-form-field\);[^}]*color:\s*var\(--torneos-form-field-text\);/);
    assert.match(estilos, /html\.theme-torneos \.form-section \.form-section__actions\s*\{[^}]*background:\s*linear-gradient\(110deg, var\(--torneos-form-actions-start\), var\(--torneos-form-actions-end\)\)/);
    assert.match(estilos, /html\.theme-torneos body\.dark\s*\{[^}]*--torneos-form-surface-start:\s*rgb\(2 44 34 \/ 96%\)/);
    /* Las superficies claras internas conservan texto oscuro. */
    assert.match(estilos, /html\.theme-torneos \.form-section__card \[class\*="bg-white"\],\s*\nhtml\.theme-torneos \.form-section__card :is\(\.bg-gray-50, \.bg-gray-100\)\s*\{\s*color:\s*var\(--torneos-form-field-text\)/);
    /* Los paneles anidados conservan la paleta del panel, no la del formulario. */
    assert.match(estilos, /html\.theme-torneos \.form-section__card \.panel label\s*\{\s*color:\s*var\(--torneos-panel-text\)/);
    assert.match(estilos, /html\.theme-torneos \.form-section__card \.panel :is\(\.text-gray-500, \.text-gray-600, \.text-gray-700\)\s*\{\s*color:\s*var\(--torneos-panel-muted-text\)/);
    /* Sin un mecanismo paralelo: el formulario hereda el tema del layout. */
    assert.doesNotMatch(formularioCrearLocales, /local-create-form--torneos/);
    assert.doesNotMatch(estilos, /local-create-form--torneos/);
    assert.doesNotMatch(estilos, /^\.form-section__card\s*\{/m);
});

test('la tabla de complejos adapta cabecera, filas, estados y paginación sin afectar otras tablas', () => {
    assert.match(listaLocales, /class="mt-6 w-full sports-locals-page"/);
    assert.match(listaLocales, /class="mt-5 panel sports-locals-panel p-0 border-0 overflow-hidden"/);
    assert.match(listaLocales, /class="block pl-10 form-input w-80 sports-locals-search"/);
    assert.match(listaLocales, /class="sports-locals-create-button /);
    assert.match(listaLocales, /<th>\s*Acciones\s*<\/th>[\s\S]*?<th>\s*Imagen\s*<\/th>[\s\S]*?<th>\s*Nombre\s*<\/th>[\s\S]*?<th>\s*Dirección\s*<\/th>[\s\S]*?<th>\s*Estado\s*<\/th>/);
    assert.match(listaLocales, /<Pagination :data="locales" \/>/);
    assert.match(estilos, /html\.theme-torneos \.sports-locals-page \.sports-locals-panel\s*\{[^}]*--table-header-bg:\s*var\(--torneos-panel-header-surface\)/);
    assert.match(estilos, /html\.theme-torneos \.sports-locals-page \.sports-locals-panel table\.table-striped tbody tr:nth-child\(odd\) td/);
    assert.match(estilos, /html\.theme-torneos \.sports-locals-page \.sports-locals-panel \[aria-label="Navegación de Paginación"\] \[aria-current="page"\]/);
    assert.match(estilos, /html\.theme-torneos body\.dark \.sports-locals-page \.sports-locals-panel\s*\{[^}]*--table-dark-header-bg:/);
    assert.match(estilos, /html\.theme-torneos body\.dark \.sports-locals-page \.sports-locals-panel table\.table-striped tbody tr:nth-child\(odd\) td\s*\{\s*background-color:\s*var\(--table-dark-row-hover\)/);
    assert.doesNotMatch(estilos, /^\.sports-locals-page|^\.sports-locals-panel/m);
});

test('el listado de cuentas tiene esquinas redondeadas y el pie legible solo en Torneos', () => {
    assert.match(cuentasOrganizaciones, /class="panel organization-accounts-list p-0"/);
    assert.match(footer, /class="app-footer /);
    assert.match(estilos, /html\.theme-torneos \.organization-accounts-list\s*\{\s*overflow:\s*hidden;\s*border-radius:\s*1rem;/);
    assert.match(estilos, /html\.theme-torneos \.main-content > \.app-footer\s*\{[^}]*background-color:\s*rgb\(2 44 34 \/ 78%\);[^}]*color:\s*#f0fdf4;/);
    assert.doesNotMatch(estilos, /^\.organization-accounts-list\s*\{/m);
    assert.doesNotMatch(estilos, /^\.app-footer\s*\{/m);
});

test('el paginador adoptó la variante de Torneos sin perder el diseño original', () => {
    // Reconoce el tema con el mismo prop compartido por el servidor.
    assert.match(paginacion, /page\.props\.sportsOrganization\?\.mode === true/);

    // Variante futbolera: barra esmeralda, botones oscuros e iconos claros.
    assert.match(paginacion, /bg-emerald-950\/70/);
    assert.match(paginacion, /bg-emerald-900\/85/);
    assert.match(paginacion, /text-emerald-50/);

    // Página activa en verde lima con número oscuro, sin el azul genérico.
    assert.match(paginacion, /clasesPaginaActiva/);
    assert.match(paginacion, /bg-lime-400 text-emerald-950/);
    assert.match(paginacion, /aria-current="page"/);

    // Estado deshabilitado distinguible y foco visible por teclado.
    assert.match(paginacion, /bg-emerald-950\/55 text-emerald-100\/45 cursor-not-allowed/);
    assert.match(paginacion, /focus-visible:ring-2 focus-visible:ring-lime-300/);

    // El diseño original se conserva literalmente como alternativa.
    assert.match(paginacion, /text-blue-700 bg-white rounded-lg hover:bg-blue-500/);
    assert.match(paginacion, /bg-\[\#4466F2\] text-white/);
    assert.match(paginacion, /bg-\[\#E8EBF3\] text-\[\#4A5E8D\]/);
    assert.match(paginacion, /bg-\[\#F3F5F9\] text-\[\#BDC5D8\]/);

    // La barra y los botones del tema consumen tokens dedicados del pager.
    assert.match(estilos, /--torneos-pager-button-bg:\s*rgb\(6 78 59 \/ 88%\)/);
    assert.match(estilos, /--torneos-pager-active-bg:\s*#a3e635/);
    assert.match(estilos, /--torneos-pager-active-text:\s*#052e16/);
    assert.match(estilos, /html\.theme-torneos \[aria-label="Navegación de Paginación"\] a\s*\{[^}]*background-color:\s*var\(--torneos-pager-button-bg\)/);
    assert.match(estilos, /html\.theme-torneos \[aria-label="Navegación de Paginación"\] \[aria-current="page"\]\s*\{[^}]*background-color:\s*var\(--torneos-pager-active-bg\);[^}]*color:\s*var\(--torneos-pager-active-text\)/);
    // Ninguna regla del paginador vive fuera del tema.
    assert.doesNotMatch(estilos, /^\[aria-label="Navegación de Paginación"\]/m);
});

test('la paleta original sigue declarada como valores de fábrica', () => {
    assert.match(estilos, /--brand-primary:\s*67 97 238;/);
    assert.match(estilos, /--brand-primary-light:\s*234 241 255;/);
    assert.match(estilos, /--brand-secondary:\s*128 93 202;/);
    assert.match(estilos, /--brand-secondary-light:\s*235 228 247;/);
});

test('los controles de formulario, selectores, fechas y paginadores tienen tema futbolero centralizado bajo html.theme-torneos', () => {
    // Tokens centralizados de controles y paginadores
    assert.match(estilos, /--torneos-control-bg:\s*rgb\(240 253 244 \/ 97%\)/);
    assert.match(estilos, /--torneos-control-border:\s*rgb\(16 185 129 \/ 45%\)/);
    assert.match(estilos, /--torneos-dropdown-bg:\s*#f0fdf4/);
    assert.match(estilos, /--torneos-pagination-surface:\s*rgb\(210 226 217 \/ 96%\)/);

    // A, B, C, D, E: Inputs de texto, búsqueda, numéricos, fechas, select nativo y textareas
    assert.match(estilos, /html\.theme-torneos :is\(\s*\.form-input,\s*\.form-textarea,\s*\.form-select,\s*\.form-multiselect,\s*input\[type="text"\],\s*input\[type="search"\]/);
    assert.match(estilos, /input\[type="number"\]/);
    assert.match(estilos, /input\[type="date"\],\s*input\[type="datetime-local"\],\s*input\[type="time"\]/);
    assert.match(estilos, /html\.theme-torneos select option,\s*\nhtml\.theme-torneos select optgroup/);

    // D: Selectores de bibliotecas (@suadelabs/vue3-multiselect y Ant Design Vue)
    assert.match(estilos, /html\.theme-torneos \.multiselect \.multiselect__tags/);
    assert.match(estilos, /html\.theme-torneos \.multiselect \.multiselect__content-wrapper/);
    assert.match(estilos, /html\.theme-torneos \.ant-select-single:not\(\.ant-select-customize-input\) \.ant-select-selector/);
    assert.match(estilos, /html\.theme-torneos \.ant-select-dropdown/);
    assert.match(estilos, /html\.theme-torneos \.ant-input-number/);

    // E: Selectores de fecha (Ant Picker y Flatpickr)
    assert.match(estilos, /html\.theme-torneos \.ant-picker\s*\{/);
    assert.match(estilos, /html\.theme-torneos \.ant-picker-dropdown \.ant-picker-panel-container/);
    assert.match(estilos, /html\.theme-torneos \.flatpickr-calendar\s*\{/);

    // Checkboxes, radios, switches y archivos
    assert.match(estilos, /html\.theme-torneos :is\(\s*input\[type="checkbox"\]:not\(\.sr-only\):not\(\.ant-checkbox-input\)/);
    assert.match(estilos, /html\.theme-torneos \.ant-checkbox-checked \.ant-checkbox-inner/);
    assert.match(estilos, /html\.theme-torneos \.ant-switch-checked/);
    assert.match(estilos, /html\.theme-torneos input\[type="file"\]::file-selector-button/);

    // Fase 4: Paginadores (Pagination.vue, Ant Pagination, vue3-datatable, DataTables)
    assert.match(estilos, /html\.theme-torneos \[aria-label="Navegación de Paginación"\]\s*\{/);
    assert.match(estilos, /html\.theme-torneos \[aria-label="Navegación de Paginación"\] a\s*\{/);
    assert.match(estilos, /html\.theme-torneos \[aria-label="Navegación de Paginación"\] \[aria-current="page"\]\s*\{/);
    assert.match(estilos, /html\.theme-torneos \[aria-label="Navegación de Paginación"\] button:disabled\s*\{/);
    assert.match(estilos, /html\.theme-torneos #per-page-select\s*\{/);
    assert.match(estilos, /html\.theme-torneos \.ant-pagination-item/);
    assert.match(estilos, /html\.theme-torneos \.datatable \.bh-pagination \.bh-page-item/);
    assert.match(estilos, /html\.theme-torneos div\.dt-container \.dt-paging \.dt-paging-button/);

    // Garantizar que ninguna regla nueva se aplique fuera de html.theme-torneos
    assert.doesNotMatch(estilos, /^\[aria-label="Navegación de Paginación"\]/m);
    assert.doesNotMatch(estilos, /^#per-page-select/m);
});

