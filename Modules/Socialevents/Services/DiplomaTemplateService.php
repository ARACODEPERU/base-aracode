<?php

namespace Modules\Socialevents\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use InvalidArgumentException;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;

/**
 * Modelos de diploma reutilizables de un torneo.
 *
 * Un modelo pertenece al torneo y declara su tipo (equipo o jugador). El mismo
 * diseño se usa en varias ediciones: al emitir se resuelven sus variables con
 * los datos del premiado, no se crea un modelo por ganador.
 *
 * El fondo define el tamaño del lienzo. Se reescala a un máximo de 2500 px por
 * lado para que el editor y la exportación no congelen el navegador.
 */
class DiplomaTemplateService
{
    private const MAX_SIDE = 2500;

    private const DIRECTORY = 'socialevents/diplomas';

    /**
     * Crea un modelo con su fondo y un layout inicial según el tipo.
     */
    public function create(
        int $eventId,
        int $editionId,
        string $diplomaType,
        string $name,
        ?int $year,
        UploadedFile|string $background,
        int $userId
    ): EventEditionDiplomaTemplate {
        if (! in_array($diplomaType, EventEditionDiplomaTemplate::types(), true)) {
            throw new InvalidArgumentException('El tipo de modelo de diploma no es válido.');
        }

        $stored = $this->storeBackground($eventId, $background);

        return EventEditionDiplomaTemplate::create([
            'event_id' => $eventId,
            'edition_id' => $editionId,
            // Columna heredada: se usa como referencia del tipo, no como
            // asociación exclusiva al premio.
            'award_key' => $diplomaType,
            'diploma_type' => $diplomaType,
            'year' => $year ?: (int) now()->format('Y'),
            'name' => $name,
            'background_path' => $stored['path'],
            'background_width' => $stored['width'],
            'background_height' => $stored['height'],
            'layout' => $this->defaultLayout($diplomaType, $stored['width'], $stored['height']),
            // Varios modelos del mismo tipo pueden estar vigentes: la edición
            // elige explícitamente cuál usar.
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }

    /**
     * Modelos del torneo, primero los vigentes y luego los más recientes.
     *
     * @return \Illuminate\Support\Collection<int, EventEditionDiplomaTemplate>
     */
    public function listForEvent(int $eventId)
    {
        return EventEditionDiplomaTemplate::where('event_id', $eventId)
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Reemplaza el fondo y devuelve las dimensiones reales del nuevo lienzo.
     *
     * @return array{path: string, url: string, width: int, height: int}
     */
    public function replaceBackground(EventEditionDiplomaTemplate $template, UploadedFile|string $background): array
    {
        $stored = $this->storeBackground($this->eventId($template), $background);

        $this->deleteFile($template->background_path);

        $template->update([
            'background_path' => $stored['path'],
            'background_width' => $stored['width'],
            'background_height' => $stored['height'],
        ]);

        return [
            'path' => $stored['path'],
            'url' => $this->backgroundUrl($template),
            'width' => $stored['width'],
            'height' => $stored['height'],
        ];
    }

    /**
     * Guarda el layout validado y, opcionalmente, el nombre del modelo.
     *
     * @param  array<string, mixed>  $layout
     */
    public function saveLayout(EventEditionDiplomaTemplate $template, array $layout, ?string $name = null): EventEditionDiplomaTemplate
    {
        $template->layout = $this->normalizeLayout($layout);

        if ($name !== null && trim($name) !== '') {
            $template->name = trim($name);
        }

        $template->save();

        return $template->refresh();
    }

    /**
     * Activa o desactiva el modelo.
     *
     * No desactiva los demás: puede haber varios vigentes del mismo tipo y la
     * edición decide cuál usar en cada premio.
     */
    public function setActive(EventEditionDiplomaTemplate $template, bool $active = true): EventEditionDiplomaTemplate
    {
        $template->forceFill(['is_active' => $active])->save();

        return $template->refresh();
    }

    /** Compatibilidad con el flujo anterior. */
    public function activate(EventEditionDiplomaTemplate $template): EventEditionDiplomaTemplate
    {
        return $this->setActive($template, true);
    }

    /**
     * Elimina el modelo sólo si nunca se usó.
     *
     * Si ya emitió diplomas o está asignado a un premio, se desactiva: borrarlo
     * dejaría reimpresiones sin diseño de referencia.
     *
     * @return string deleted|deactivated
     */
    public function destroy(EventEditionDiplomaTemplate $template): string
    {
        $inUse = $template->diplomas()->exists()
            || EventEditionAward::where('template_id', $template->id)->exists();

        if ($inUse) {
            $this->setActive($template, false);

            return 'deactivated';
        }

        $this->deleteFile($template->background_path);
        $template->delete();

        return 'deleted';
    }

    public function backgroundUrl(EventEditionDiplomaTemplate $template): ?string
    {
        if (! $template->background_path) {
            return null;
        }

        return asset('storage/'.ltrim(str_replace('\\', '/', $template->background_path), '/'));
    }

    /**
     * Layout inicial por tipo. Usa las variables canónicas del nuevo flujo
     * ({{nombre_equipo}}, {{nombre_jugador}}, {{tipo_premio}}…).
     *
     * @return array<string, mixed>
     */
    public function defaultLayout(string $diplomaType, int $width, int $height): array
    {
        $isTeam = $diplomaType === EventEditionDiplomaTemplate::TYPE_TEAM;

        $elements = [
            [
                'id' => 'premio',
                'type' => 'text',
                'name' => 'Premio',
                'x' => (int) round($width * 0.12),
                'y' => (int) round($height * 0.20),
                'width' => (int) round($width * 0.76),
                'text' => '{{tipo_premio}}',
                'fontFamily' => 'Poppins Light',
                'fontSize' => 46,
                'color' => '#0b1f2b',
                'align' => 'center',
                'lineHeight' => 1.2,
                'letterSpacing' => 3,
                'rotation' => 0,
                'visible' => true,
                'locked' => false,
            ],
            [
                'id' => 'ganador',
                'type' => 'text',
                'name' => 'Ganador',
                'x' => (int) round($width * 0.08),
                'y' => (int) round($height * 0.31),
                'width' => (int) round($width * 0.84),
                'text' => $isTeam ? '{{nombre_equipo}}' : '{{nombre_jugador}}',
                'fontFamily' => $isTeam ? 'Poppins Light' : 'Pacifico Regular',
                'fontSize' => $isTeam ? 62 : 76,
                'color' => '#0b1f2b',
                'align' => 'center',
                'lineHeight' => 1.2,
                'letterSpacing' => 0,
                'rotation' => 0,
                'visible' => true,
                'locked' => false,
            ],
            [
                'id' => 'torneo',
                'type' => 'text',
                'name' => 'Torneo',
                'x' => (int) round($width * 0.10),
                'y' => (int) round($height * 0.44),
                'width' => (int) round($width * 0.8),
                'text' => '{{torneo}} · {{edicion}}',
                'fontFamily' => 'Poppins Light',
                'fontSize' => 26,
                'color' => '#334155',
                'align' => 'center',
                'lineHeight' => 1.25,
                'letterSpacing' => 1,
                'rotation' => 0,
                'visible' => true,
                'locked' => false,
            ],
        ];

        if ($isTeam) {
            $elements[] = [
                'id' => 'puesto',
                'type' => 'text',
                'name' => 'Puesto',
                'x' => (int) round($width * 0.30),
                'y' => (int) round($height * 0.50),
                'width' => (int) round($width * 0.4),
                'text' => '{{puesto}}',
                'fontFamily' => 'Poppins Light',
                'fontSize' => 28,
                'color' => '#F5C451',
                'align' => 'center',
                'lineHeight' => 1.2,
                'letterSpacing' => 2,
                'rotation' => 0,
                'visible' => true,
                'locked' => false,
            ];

            $elements[] = [
                'id' => 'plantel',
                'type' => 'squad',
                'name' => 'Plantel',
                'x' => (int) round($width * 0.16),
                'y' => (int) round($height * 0.56),
                'width' => (int) round($width * 0.68),
                'columns' => 3,
                'fontFamily' => 'Poppins Light',
                'fontSize' => 20,
                'color' => '#0b1f2b',
                'align' => 'left',
                'lineHeight' => 1.45,
                'gap' => 28,
                'showJerseyNumber' => true,
                'showPosition' => false,
                'sortBy' => 'number',
                'title' => 'PLANTEL',
                'titleFontSize' => 22,
                'rotation' => 0,
                'visible' => true,
                'locked' => false,
            ];
        } else {
            $elements[] = [
                'id' => 'equipo',
                'type' => 'text',
                'name' => 'Equipo',
                'x' => (int) round($width * 0.15),
                'y' => (int) round($height * 0.50),
                'width' => (int) round($width * 0.7),
                'text' => '{{nombre_equipo}}',
                'fontFamily' => 'Poppins Light',
                'fontSize' => 26,
                'color' => '#334155',
                'align' => 'center',
                'lineHeight' => 1.2,
                'letterSpacing' => 1,
                'rotation' => 0,
                'visible' => true,
                'locked' => true,
            ];
        }

        $elements[] = [
            'id' => 'fecha',
            'type' => 'text',
            'name' => 'Fecha',
            'x' => (int) round($width * 0.20),
            'y' => (int) round($height * 0.88),
            'width' => (int) round($width * 0.6),
            'text' => '{{fecha}}',
            'fontFamily' => 'Poppins Light',
            'fontSize' => 20,
            'color' => '#334155',
            'align' => 'center',
            'lineHeight' => 1.2,
            'letterSpacing' => 0,
            'rotation' => 0,
            'visible' => true,
            'locked' => false,
        ];

        $elements[] = [
            'id' => 'qr',
            'type' => 'qr',
            'name' => 'QR',
            'x' => $width - 190,
            'y' => $height - 190,
            'size' => 130,
            'mode' => 'landing',
            'rotation' => 0,
            'visible' => true,
            'locked' => false,
        ];

        return [
            'version' => 1,
            'background' => [
                'path' => '',
                'width' => $width,
                'height' => $height,
            ],
            'elements' => $elements,
        ];
    }

    /**
     * Normaliza ids y orden de los elementos sin alterar la geometría.
     *
     * @param  array<string, mixed>  $layout
     * @return array<string, mixed>
     */
    private function normalizeLayout(array $layout): array
    {
        $elements = [];

        foreach (($layout['elements'] ?? []) as $index => $element) {
            if (! is_array($element)) {
                continue;
            }

            $element['id'] = $element['id'] ?? 'el_'.($index + 1);
            $elements[] = $element;
        }

        return array_merge($layout, ['elements' => $elements]);
    }

    /**
     * Guarda el fondo en el disco público y devuelve ruta y dimensiones.
     *
     * @return array{path: string, width: int, height: int}
     */
    private function storeBackground(int $eventId, UploadedFile|string $background): array
    {
        if ($background instanceof UploadedFile) {
            $image = Image::make($background->getRealPath());
        } else {
            // El editor puede enviar el fondo como data URL base64.
            $decoded = base64_decode(preg_replace('#^data:image/[a-z0-9.+-]+;base64,#i', '', $background));
            $image = Image::make($decoded);
        }

        $image->resize(self::MAX_SIDE, self::MAX_SIDE, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $folder = 'event_'.$eventId;
        $path = self::DIRECTORY.'/'.$folder.'/front_'.now()->format('YmdHis').'_'.mt_rand(1000, 9999).'.png';

        Storage::disk('public')->put($path, (string) $image->encode('png'));

        return [
            'path' => $path,
            'width' => (int) $image->width(),
            'height' => (int) $image->height(),
        ];
    }

    private function eventId(EventEditionDiplomaTemplate $template): int
    {
        if ($template->event_id) {
            return (int) $template->event_id;
        }

        return (int) ($template->edition?->event_id ?? 0);
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete(ltrim(str_replace('\\', '/', $path), '/'));
        }
    }
}
