<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ai\Services\AiManager;
use Modules\Ai\Support\EnmascaradorDatos;
use Modules\Ai\Support\MapaSistema;
use Throwable;

/**
 * Aracodito con IA.
 *
 * Solo responde sobre el uso del sistema: para qué sirve una pantalla, qué
 * puede hacer un usuario o los pasos de un proceso. No recibe datos del
 * paciente: la pregunta se enmascara y solo viajan la ruta actual y el mapa de
 * pantallas que el usuario ya puede ver.
 *
 * La ruta que propone el modelo se valida contra ese mismo mapa antes de
 * devolverla; si no coincide, se responde sin enlace.
 */
class AracoditoController extends Controller
{
    public function preguntar(Request $request, AiManager $manager)
    {
        $data = $request->validate([
            'pregunta' => 'required|string|max:1000',
            'ruta' => 'nullable|string|max:255',
        ]);

        if (! $request->user()?->aracodito_ia_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Aracodito con IA no está activado en tu cuenta.',
            ], 403);
        }

        // El mapa se construye en el servidor desde las rutas reales y se
        // filtra por los permisos del usuario: no se confía en una lista
        // enviada por el navegador.
        $pantallas = collect(MapaSistema::paraUsuario($request->user()));

        $mapa = $pantallas
            ->map(fn ($pantalla) => '- ' . $pantalla['texto'] . ' => ' . $pantalla['url'])
            ->implode("\n");

        $instrucciones = (string) config('ai.aracodito.instrucciones', '')
            . "\n\nPantalla actual: " . (($data['ruta'] ?? '') !== '' ? $data['ruta'] : 'no indicada')
            . "\n\nPantallas que este usuario puede abrir:\n" . ($mapa !== '' ? $mapa : '(sin pantallas)')
            . "\n\nSi varias pantallas de la lista se parecen, elige la que use exactamente las palabras de la pregunta;"
            . ' si la pregunta es de pacientes, atenciones, historia clínica o citas y existe en Salud, usa la de Salud.'
            . ' Nunca digas que no hay acceso a una pantalla que sí aparece en la lista.'
            . "\n\nResponde en máximo 6 líneas, en español, solo sobre cómo usar el sistema."
            . ' Si la respuesta corresponde a una de las pantallas de la lista, agrega al final una línea exacta'
            . ' con el formato RUTA: <url de la lista>. Si no corresponde, no agregues esa línea.'
            . ' Si la persona pide una pantalla que no está en la lista, dile que su rol no tiene acceso y que'
            . ' pida los permisos a un administrador en Seguridad → Roles y permisos.'
            . ' No inventes pantallas, botones ni rutas.';

        $pregunta = EnmascaradorDatos::enmascarar(trim((string) $data['pregunta']));

        try {
            $result = $manager->chat($request->user()->id, $pregunta, [
                'instructions' => $instrucciones,
                'screen' => 'aracodito',
                'max_output_tokens' => 500,
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        [$respuesta, $url] = $this->separarRuta($result->text, $pantallas->pluck('url')->all());

        // El servidor no confía en la ruta propuesta por el modelo: solo vale
        // si está en el mapa de pantallas que el usuario ya tiene permitidas.
        // La comparación ignora la barra final y devuelve la dirección canónica.
        if ($url !== null) {
            $buscada = rtrim($url, '/');
            $permitida = $pantallas->first(
                fn ($pantalla) => rtrim($pantalla['url'], '/') === $buscada
            );

            $url = $permitida['url'] ?? null;
        }

        return response()->json([
            'success' => true,
            'respuesta' => $respuesta !== '' ? $respuesta : config('ai.aracodito.mensaje_fuera_de_alcance'),
            'url' => $url,
            'provider' => $result->provider,
            'model' => $result->model,
            'total_tokens' => $result->totalTokens,
            'cost_usd' => $result->costUsd,
        ]);
    }

    /**
     * Separa la respuesta del enlace opcional "RUTA: ...".
     *
     * El modelo no siempre devuelve la línea limpia: suele agregar viñetas,
     * negritas o la dirección completa. Se aceptan todas esas variantes y, si
     * no hay marcador, se busca una dirección permitida dentro del texto.
     *
     * @param  array<int, string>  $urlsPermitidas
     * @return array{0: string, 1: ?string}
     */
    private function separarRuta(string $texto, array $urlsPermitidas = []): array
    {
        $url = null;

        $patron = '/(?:^|\n)\s*(?:[-*•]\s*)?\**RUTA\**\s*:\s*\**\s*(\S+?)\**\s*(?=\n|$)/iu';

        if (preg_match($patron, $texto, $coincidencia)) {
            $url = $this->normalizarUrl($coincidencia[1]);
            $texto = preg_replace($patron, '', $texto) ?? $texto;
        }

        // Sin marcador: si el texto menciona una pantalla permitida, se usa
        // como destino del botón. La validación final la hace el controlador.
        if ($url === null) {
            // Se queda la coincidencia más larga: así /health/patients/create
            // no se confunde con /health/patients.
            foreach ($urlsPermitidas as $candidata) {
                if ($candidata === '') {
                    continue;
                }

                $patronCandidata = '~' . preg_quote($candidata, '~') . '(?![A-Za-z0-9\-_/])~';

                if (preg_match($patronCandidata, $texto)
                    && ($url === null || strlen($candidata) > strlen($url))) {
                    $url = $candidata;
                }
            }
        }

        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return [trim($texto), $url];
    }

    /**
     * Deja la ruta como una dirección interna: quita el dominio si el modelo
     * devolvió una URL completa y asegura la barra inicial.
     */
    private function normalizarUrl(string $url): string
    {
        $url = trim($url, " \t\n\r\0\x0B*`\"'");

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $url = (string) parse_url($url, PHP_URL_PATH);
        }

        if ($url === '') {
            return '';
        }

        return str_starts_with($url, '/') ? $url : '/' . $url;
    }
}
