<?php

namespace Modules\Academic\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

/**
 * Guía de estilo y estructura para el contenido que redacta el agente. El
 * modelo la lee antes de escribir las clases, para que los cursos se parezcan
 * a los que produce el equipo docente.
 */
#[Uri('academic://guides/content-style')]
#[MimeType('text/markdown')]
#[Description('Guía de estilo y estructura pedagógica para redactar clases de los cursos del ERP: extensión, tono, estructura, ejemplos, evaluación y etiquetas HTML permitidas.')]
class ContentStyleGuideResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(<<<'MD'
# Guía de contenido para cursos

## Estructura de cada clase
1. **Objetivo** en una frase: qué sabrá hacer el alumno al terminar.
2. **Contexto** breve: por qué importa en el trabajo real.
3. **Desarrollo** en pasos o secciones con subtítulos `h3`.
4. **Ejemplo aplicado** con datos verosímiles del Perú (montos en soles, RUC, fechas).
5. **Errores frecuentes** en una lista corta.
6. **Cierre** con tres ideas clave y una pregunta de repaso.

## Extensión y tono
- Entre 400 y 700 palabras por clase; si el tema es largo, divídelo en dos clases.
- Español neutro, segunda persona («vas a construir», «revisa»), sin marketing.
- Frases cortas, voz activa, sin adjetivos vacíos.
- Cada afirmación técnica debe poder comprobarse; si no estás seguro, no lo escribas.

## Formato HTML
- Etiquetas permitidas: `p`, `br`, `h2`, `h3`, `h4`, `ul`, `ol`, `li`, `strong`, `em`, `blockquote`, `table`, `thead`, `tbody`, `tr`, `th`, `td`, `a`, `code`, `pre`, `hr`.
- No uses `script`, `style`, `iframe` ni atributos `on…`; el ERP los elimina al guardar.
- Tablas solo cuando comparan datos; nada de tablas para maquetar.
- Enlaces solo con `https://`.

## Módulos y clases
- Módulo: título de hasta 200 caracteres que nombra una competencia («Armar el flujo de caja»).
- Clase: título de hasta 255 caracteres, concreto y accionable («Proyectar cobros y pagos a 90 días»).
- Un módulo de 3 a 5 clases; un curso de 4 a 6 módulos.

## Evaluación
- Cada examen tiene entre 5 y 10 preguntas de alternativas.
- Una sola respuesta correcta por pregunta en «Alternativas».
- Puntaje total 20; cada pregunta vale 2 puntos salvo que el brief diga otra cosa.
- Los distractores deben ser plausibles, no absurdos.

## Qué no hacer
- No inventes categorías, modalidades ni docentes: usa `academic_get_course_options` y `academic_list_teachers`.
- No prometas certificaciones, becas ni precios que no estén en el brief.
- No copies texto de terceros sin adaptarlo.
MD);
    }
}
