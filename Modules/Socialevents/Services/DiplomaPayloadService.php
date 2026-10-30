<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiploma;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;

/**
 * Prepara todo lo que el render Konva necesita: tamaño del lienzo, fondo,
 * elementos con los textos ya resueltos, el plantel ordenado y el texto del QR.
 *
 * El cliente sólo dibuja; nunca decide datos del premiado.
 */
class DiplomaPayloadService
{
    public function __construct(
        private DiplomaTokenResolver $tokens,
        private EditionAwardService $awards,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        EventEdition $edition,
        EventEditionAward $award,
        EventEditionDiplomaTemplate $template,
        ?EventEditionDiploma $diploma = null
    ): array {
        $layout = $diploma?->layout_snapshot ?: ($template->layout ?? []);
        $canvas = $template->canvas();
        $squad = $diploma?->squad_snapshot ?: $this->awards->squad($award);

        // Un diploma ya emitido se reimprime con sus datos congelados: si luego
        // se renombra al jugador o se edita el premio, el documento no cambia.
        $awardData = $diploma ? (clone $award) : $award;

        if ($diploma) {
            $awardData->forceFill([
                'label' => $diploma->award_label,
                'recipient_type' => $diploma->recipient_type,
                'recipient_name' => $diploma->recipient_name,
                'team_name' => $diploma->team_name,
            ]);
        }

        // El plantel se pasa al contexto para poder resolver {{lista_jugadores}}
        // sin duplicar consultas: ya se obtuvo arriba.
        $context = $this->tokens->context($edition, $awardData, $diploma?->issued_at, $squad);

        // Variables usadas en el diseño que quedaron vacías: se avisa antes de
        // descargar en lugar de imprimir una etiqueta o un hueco silencioso.
        $missingTokens = $this->missingTokens($layout, $context);

        $elements = [];

        foreach (($layout['elements'] ?? []) as $element) {
            if (! is_array($element) || ($element['visible'] ?? true) === false) {
                continue;
            }

            $elements[] = $this->elementPayload($element, $context, $squad, $edition, $diploma);
        }

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'award_key' => $template->award_key,
            ],
            'award' => [
                'id' => $award->id,
                'award_key' => $award->award_key,
                'label' => $awardData->label,
                'recipient_type' => $awardData->recipient_type,
                'recipient_name' => $awardData->recipient_name,
                'team_name' => $awardData->team_name,
                'place' => $award->place,
                'meta' => $award->meta ?? [],
            ],
            'side' => [
                'key' => 'front',
                'width' => $canvas['width'],
                'height' => $canvas['height'],
                'background_url' => $this->publicUrl($template->background_path),
                'elements' => $elements,
            ],
            // Valores reales de las variables: el editor los usa para mostrar en
            // el lienzo el texto del layout con {{tokens}} ya resueltos.
            'token_values' => $context,
            'file_name' => $this->fileName($awardData),
            'missing_tokens' => $missingTokens,
            'public_url' => null,
            'public_token' => $diploma?->public_token,
            'diploma_id' => $diploma?->id,
        ];
    }

    /**
     * Variables del layout que no pudieron resolverse con datos reales.
     *
     * @param  array<string, mixed>  $layout
     * @param  array<string, string>  $context
     * @return array<int, string>
     */
    private function missingTokens(array $layout, array $context): array
    {
        $missing = [];

        foreach (($layout['elements'] ?? []) as $element) {
            foreach (['text', 'title'] as $key) {
                $value = $element[$key] ?? null;

                if (! is_string($value)) {
                    continue;
                }

                preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $value, $matches);

                foreach ($matches[1] as $token) {
                    $token = strtolower($token);

                    if (trim((string) ($context[$token] ?? '')) === '') {
                        $missing[] = $token;
                    }
                }
            }
        }

        return array_values(array_unique($missing));
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, string>  $context
     * @param  array<int, array<string, mixed>>  $squad
     * @return array<string, mixed>
     */
    private function elementPayload(
        array $element,
        array $context,
        array $squad,
        EventEdition $edition,
        ?EventEditionDiploma $diploma
    ): array {
        $type = $element['type'] ?? 'text';

        $base = [
            'id' => $element['id'] ?? (string) Str::uuid(),
            'type' => $type,
            'name' => $element['name'] ?? null,
            'x' => (float) ($element['x'] ?? 0),
            'y' => (float) ($element['y'] ?? 0),
            'rotation' => (float) ($element['rotation'] ?? 0),
            'locked' => (bool) ($element['locked'] ?? false),
        ];

        return match ($type) {
            'text' => array_merge($base, [
                'text' => $this->tokens->resolve($element['text'] ?? '', $context),
                'width' => isset($element['width']) ? (float) $element['width'] : null,
                'fontFamily' => $element['fontFamily'] ?? 'Poppins Light',
                'fontSize' => (float) ($element['fontSize'] ?? 32),
                'color' => $element['color'] ?? '#111111',
                'align' => $element['align'] ?? 'center',
                'lineHeight' => (float) ($element['lineHeight'] ?? 1.2),
                'letterSpacing' => (float) ($element['letterSpacing'] ?? 0),
            ]),
            'squad' => array_merge($base, [
                'columns' => max(1, min(4, (int) ($element['columns'] ?? 2))),
                'width' => (float) ($element['width'] ?? 800),
                'fontFamily' => $element['fontFamily'] ?? 'Poppins Light',
                'fontSize' => (float) ($element['fontSize'] ?? 20),
                'color' => $element['color'] ?? '#111111',
                'align' => $element['align'] ?? 'left',
                'lineHeight' => (float) ($element['lineHeight'] ?? 1.4),
                'gap' => (float) ($element['gap'] ?? 24),
                'showJerseyNumber' => (bool) ($element['showJerseyNumber'] ?? true),
                'showPosition' => (bool) ($element['showPosition'] ?? false),
                'title' => $this->tokens->resolve($element['title'] ?? '', $context),
                'titleFontSize' => (float) ($element['titleFontSize'] ?? 22),
                'items' => $this->squadItems($squad, $element),
            ]),
            'image' => array_merge($base, [
                'src' => $this->publicUrl($element['src'] ?? null),
                'width' => (float) ($element['width'] ?? 120),
                'height' => (float) ($element['height'] ?? 120),
            ]),
            'qr' => array_merge($base, [
                'size' => (float) ($element['size'] ?? 120),
                'mode' => $element['mode'] ?? 'landing',
                'qr_text' => $this->qrText($edition, $diploma, $element),
            ]),
            'rect' => array_merge($base, [
                'width' => (float) ($element['width'] ?? 200),
                'height' => (float) ($element['height'] ?? 100),
                'stroke' => $element['stroke'] ?? '#111111',
                'strokeWidth' => (float) ($element['strokeWidth'] ?? 2),
                'radius' => (float) ($element['radius'] ?? 0),
                'fill' => $element['fill'] ?? null,
            ]),
            'line' => array_merge($base, [
                'width' => (float) ($element['width'] ?? 200),
                'stroke' => $element['stroke'] ?? '#111111',
                'strokeWidth' => (float) ($element['strokeWidth'] ?? 2),
            ]),
            default => array_merge($base, ['type' => 'text', 'text' => '']),
        };
    }

    /**
     * Arma las líneas del plantel según la configuración del elemento.
     *
     * @param  array<int, array<string, mixed>>  $squad
     * @param  array<string, mixed>  $element
     * @return array<int, array{line: string, name: string, jersey: ?int, position: ?string}>
     */
    private function squadItems(array $squad, array $element): array
    {
        $sortBy = $element['sortBy'] ?? 'number';
        $showJersey = (bool) ($element['showJerseyNumber'] ?? true);
        $showPosition = (bool) ($element['showPosition'] ?? false);

        $items = $squad;

        usort($items, function (array $a, array $b) use ($sortBy) {
            if ($sortBy === 'name') {
                return strcasecmp((string) $a['name'], (string) $b['name']);
            }

            if ($sortBy === 'position') {
                $byPosition = strcasecmp((string) ($a['position'] ?? ''), (string) ($b['position'] ?? ''));

                return $byPosition !== 0 ? $byPosition : strcasecmp((string) $a['name'], (string) $b['name']);
            }

            $byNumber = ((int) ($a['jersey'] ?? 999)) <=> ((int) ($b['jersey'] ?? 999));

            return $byNumber !== 0 ? $byNumber : strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return array_map(function (array $item) use ($showJersey, $showPosition) {
            $parts = [];

            if ($showJersey && ! empty($item['jersey'])) {
                $parts[] = $item['jersey'].'.';
            }

            $parts[] = $item['name'];

            if ($showPosition && ! empty($item['position'])) {
                $parts[] = '('.ucfirst((string) $item['position']).')';
            }

            return [
                'line' => implode(' ', $parts),
                'name' => $item['name'],
                'jersey' => $item['jersey'],
                'position' => $item['position'],
            ];
        }, $items);
    }

    /**
     * Texto del QR. Mientras la página pública de logros no exista, apunta a la
     * landing del torneo; cuando se implemente, el modo `diploma` la usará.
     */
    private function qrText(EventEdition $edition, ?EventEditionDiploma $diploma, array $element): string
    {
        $mode = $element['mode'] ?? 'landing';

        if ($mode === 'diploma' && $diploma && Route::has('socialevents_torneos_diploma')) {
            return route('socialevents_torneos_diploma', [
                'slug' => $edition->landingSlug(),
                'token' => $diploma->public_token,
            ]);
        }

        return $edition->landingUrl();
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
    }

    private function fileName(EventEditionAward $award): string
    {
        return 'diploma_'.Str::slug($award->award_key.'_'.$award->recipient_name, '_');
    }
}
