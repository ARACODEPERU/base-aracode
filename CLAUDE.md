# Instrucciones de idioma

- Responde siempre en español.
- Explica los cambios y resultados en español.
- Mantén los nombres de variables, funciones, clases, comandos y código en su idioma original.
- Los comentarios nuevos en el código deben escribirse en español.
- Si encuentras documentación o mensajes en inglés, explícalos en español.

# Skills de diseño (UI/UX)

En `.agents/skills/` están instaladas las skills de diseño. **Antes de crear, rediseñar, maquetar o revisar cualquier pantalla, landing, dashboard, modal o componente visual, léelas y aplícalas.**

## Cuándo usar cada una

- `design-taste-frontend` (Taste Skill v2): dirección de arte para páginas públicas (landing, producto, portafolio, one-pager, hero) y también para pantallas de app (dashboards, vistas de administración, ajustes). Evita el "look genérico de IA". Trae scripts que necesitan **Node 22+ y Chrome/Edge/Chromium** instalados: `scripts/draw.mjs` (reparte direcciones de diseño) y `scripts/inspect.mjs` (audita la página en un navegador real).
- `emil-design-eng`: skill base de Emil Kowalski, criterio de detalle fino — microinteracciones, sombras y bordes, foco, curvas, cuándo animar y cuándo no.
- `animate` — construir una animación desde cero. `review-animations` — revisarla con criterio estricto. `improve-animations` — auditar todo el motion del código y devolver un plan priorizado. `find-animation-opportunities` / `remove-animations` — añadir o quitar movimiento donde toca. `animation-performance` — arreglar animaciones que pierden frames. `animation-vocabulary` — nombrar un efecto.
- `apple-design` y `mobile-native`: springs, gestos, hojas, safe areas, hover pegado en táctil, bug de `100vh`, inputs que hacen zoom en el móvil.
- `break-ui`: probar la UI con datos del peor caso (nombres larguísimos, listas vacías, números enormes, emoji).
- `prototype` y `pick-ui-library`: solo cuando se invoquen explícitamente.

## Reglas de convivencia con este proyecto

1. La skill `estetica-vristo-erp` y el sistema existente (tema Vristo + Tailwind + componentes del núcleo) **mandan** sobre cualquier regla estética de estas skills. No introducir paletas, tipografías ni librerías nuevas sin pedirlo.
2. Aplicar el criterio de las skills **dentro** de las clases y componentes que ya existen (`panel`, `form-input`, `form-select`, `btn btn-primary`, modales del núcleo, Swal2), no reemplazándolos.
3. Toda la prosa, los comentarios y los textos de interfaz en español.
4. Una pantalla no se da por terminada sin verla en el navegador real: capturas a 360, 768 y 1280 px, y revisar hover, foco, vacío, error y carga.
