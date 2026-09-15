<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Services\OpenAiAssistantService;

class BlogAiController extends Controller
{
    /**
     * Corrige ortografía y gramática del texto enviado.
     * POST /blog/ai/correct-spelling
     */
    public function correctSpelling(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:100000',
        ]);

        $text = trim($request->input('text'));

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'No hay texto para corregir.',
            ]);
        }

        try {
            $corrected = app(OpenAiAssistantService::class)->correctText(
                Auth::id(),
                $text,
                false
            );

            return response()->json([
                'success' => true,
                'original' => $text,
                'corrected' => trim($corrected),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo corregir con IA: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Genera un artículo completo a partir de un tema y descripción.
     * POST /blog/ai/generate-article
     */
    public function generateArticle(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:500',
            'description' => 'required|string|max:1000',
        ]);

        $topic = trim($request->input('topic'));
        $description = trim($request->input('description'));

        $prompt = <<<PROMPT
Eres un redactor profesional de blogs académicos y profesionales en español.

Escribe un artículo completo y bien estructurado sobre el siguiente tema:
**Tema:** {$topic}
**Descripción:** {$description}

INSTRUCCIONES DE FORMATO:
1. La PRIMERA línea del contenido debe ser el título del artículo, así:
   TITULO: [aquí el título]
2. La SEGUNDA línea debe ser la descripción corta del artículo, así:
   DESCRIPCION: [aquí la descripción en máximo 155 caracteres]
3. Luego una línea en blanco.
4. El contenido del artículo debe estar en HTML semántico y bien formateado:
   - Usa <h2> para subtítulos de secciones
   - Usa <p> para párrafos
   - Usa <ul> y <li> para listas
   - Usa <blockquote> para citas o definiciones importantes
   - Usa <strong> para énfasis y <em> para acentos
   - Usa <table> con <thead> y <tbody> cuando necesites mostrar datos comparativos
   - Extensión: entre 800 y 1500 palabras
   - Tono profesional, claro y accesible

PROHIBIDO:
- No uses etiquetas <html>, <head>, <body>, <style>, <script>
- No uses clases CSS inline
- No agregues imágenes (solo texto)
- No uses encabezados <h1> ni <h3>, solo <h2>

RECUERDA: La primera línea es TITULO: y la segunda es DESCRIPCION:, luego el contenido HTML.
PROMPT;

        try {
            $result = app(OpenAiAssistantService::class)->sendPrompt(
                Auth::id(),
                $prompt,
                null,
                null,
                false
            );

            // Parsear la respuesta
            $lines = explode("\n", $result, 3);
            $title = '';
            $shortDescription = '';
            $content = $result;

            if (count($lines) >= 2) {
                // Extraer título
                $firstLine = trim($lines[0]);
                if (str_starts_with(strtoupper($firstLine), 'TITULO:')) {
                    $title = trim(substr($firstLine, 7));
                } elseif (preg_match('/^T[ÍI]TULO:\s*(.+)/i', $firstLine, $m)) {
                    $title = trim($m[1]);
                } else {
                    $title = $firstLine;
                }

                // Extraer descripción
                $secondLine = trim($lines[1]);
                if (str_starts_with(strtoupper($secondLine), 'DESCRIPCION:')) {
                    $shortDescription = trim(substr($secondLine, 12));
                } elseif (preg_match('/^DESCRIPCI[ÓO]N:\s*(.+)/i', $secondLine, $m)) {
                    $shortDescription = trim($m[1]);
                } else {
                    $shortDescription = $secondLine;
                }

                // El contenido es todo lo que queda después de las dos primeras líneas
                if (isset($lines[2])) {
                    $remainingLines = array_slice($lines, 2);
                    $content = implode("\n", $remainingLines);
                }
            }

            // Limpiar marcadores que no se parsearon
            $content = preg_replace('/^T[ÍI]TULO:.*$/im', '', $content);
            $content = preg_replace('/^DESCRIPCI[ÓO]N:.*$/im', '', $content);
            $content = trim($content);

            return response()->json([
                'success' => true,
                'title' => $title,
                'description' => $shortDescription,
                'content' => $content,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el artículo: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verifica la información/veracidad del contenido de un artículo.
     * POST /blog/ai/verify-content
     */
    public function verifyContent(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:100000',
        ]);

        $text = trim($request->input('text'));

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'No hay contenido para verificar.',
            ]);
        }

        $prompt = <<<PROMPT
Eres un verificador de datos y analista de contenido profesional. Tu tarea es analizar el siguiente artículo y proporcionar un informe detallado de verificación.

FORMATO DE RESPUESTA (responde en texto plano estructurado):

CALIFICACION: [Alta / Media / Baja]

AFIRMACIONES:
- [Afirmación 1] → [Verificable / Posiblemente verdadero / No verificable / Incorrecto] — [Breve justificación]
- [Afirmación 2] → [Verificable / Posiblemente verdadero / No verificable / Incorrecto] — [Breve justificación]
(Lista todas las afirmaciones clave que puedas identificar, máximo 10)

OBSERVACIONES:
- [Observación 1]
- [Observación 2]

RECOMENDACIONES:
- [Recomendación 1]
- [Recomendación 2]

REGLAS:
1. Sé objetivo y basado en hechos conocidos
2. Si no puedes verificar algo, di "No verificable" en vez de adivinar
3. Si una afirmación es claramente incorrecta, indícalo
4. No inventes fuentes, solo comenta si conoces la información de fuentes confiables
5. Las afirmaciones sobre normativas contables (NIIF, SUNAT, etc.) deben verificarse con cuidado
6. Responde SIEMPRE en español

ARTÍCULO A VERIFICAR:
{$text}
PROMPT;

        try {
            $result = app(OpenAiAssistantService::class)->sendPrompt(
                Auth::id(),
                $prompt,
                null,
                null,
                false
            );

            // Parsear la respuesta en estructura
            $parsed = $this->parseVerificationResult($result);

            return response()->json(array_merge(['success' => true], $parsed));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo verificar el contenido: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Parsea el resultado de verificación en un array estructurado.
     */
    private function parseVerificationResult(string $text): array
    {
        $result = [
            'rating' => 'No determinada',
            'raw' => $text,
            'claims' => [],
            'observations' => [],
            'recommendations' => [],
        ];

        // Extraer calificación
        if (preg_match('/CALIFICACI[ÓO]N:\s*(.+)/i', $text, $m)) {
            $result['rating'] = trim($m[1]);
        }

        // Extraer afirmaciones
        if (preg_match_all('/^-?\s*(.+?)\s*→\s*(.+?)\s*—\s*(.+)/u', $text, $m)) {
            foreach ($m[1] as $i => $claim) {
                $result['claims'][] = [
                    'claim' => trim($claim),
                    'status' => trim($m[2][$i]),
                    'justification' => trim($m[3][$i]),
                ];
            }
        }

        // Extraer observaciones
        if (preg_match_all('/OBSERVACIONES:(.+?)(?=RECOMENDACIONES:|$)/si', $text, $mObs)) {
            $lines = explode("\n", trim($mObs[1][0]));
            foreach ($lines as $line) {
                $line = preg_replace('/^\s*[-•]\s*/', '', trim($line));
                if ($line !== '') {
                    $result['observations'][] = $line;
                }
            }
        }

        // Extraer recomendaciones
        if (preg_match_all('/RECOMENDACIONES:(.+)/si', $text, $mRec)) {
            $lines = explode("\n", trim($mRec[1][0]));
            foreach ($lines as $line) {
                $line = preg_replace('/^\s*[-•]\s*/', '', trim($line));
                if ($line !== '') {
                    $result['recommendations'][] = $line;
                }
            }
        }

        return $result;
    }
}
