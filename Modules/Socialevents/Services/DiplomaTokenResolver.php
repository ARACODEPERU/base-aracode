<?php

namespace Modules\Socialevents\Services;

use Carbon\CarbonInterface;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;

/**
 * Reemplaza las variables del diseño ({{premio_ganador}}, {{torneo}}…) por
 * datos reales. El servidor es el único que resuelve: el navegador nunca
 * inventa datos del premiado.
 */
class DiplomaTokenResolver
{
    /**
     * Variables disponibles, para mostrarlas en el editor.
     *
     * Las primeras seis son las del nuevo flujo de modelos reutilizables; el
     * resto se conserva para que los diseños ya guardados sigan resolviendo.
     */
    public const TOKENS = [
        'nombre_equipo' => 'Equipo o promoción',
        'edicion' => 'Edición del torneo',
        'lista_jugadores' => 'Lista de jugadores del plantel',
        'puesto' => 'Puesto obtenido',
        'nombre_jugador' => 'Nombre del jugador',
        'tipo_premio' => 'Tipo de reconocimiento',
        'premio' => 'Nombre del premio',
        'premio_ganador' => 'Nombre del premiado',
        'premio_equipo' => 'Equipo del premiado',
        'torneo' => 'Nombre del torneo',
        'edicion' => 'Nombre de la edición',
        'anio' => 'Año de la edición',
        'categoria' => 'Categoría del torneo',
        'puesto' => 'Puesto del premio (1°, 2°…)',
        'fecha' => 'Fecha de emisión',
        'organizacion' => 'Organización del torneo',
        'jugador' => 'Jugador premiado',
        'equipo' => 'Equipo premiado',
        'camiseta' => 'Número de camiseta',
        'posicion' => 'Posición del jugador',
        'goles' => 'Goles del premio',
        'asistencias' => 'Asistencias del premio',
        'atajadas' => 'Atajadas del premio',
        'mvp' => 'MVPs del premio',
        'puntos' => 'Puntos del premio',
        'contacto' => 'Contacto del torneo',
    ];

    /** Variables que tienen sentido en un modelo por equipo. */
    private const TEAM_TOKENS = [
        'nombre_equipo', 'edicion', 'lista_jugadores', 'puesto',
        'premio', 'torneo', 'anio', 'categoria', 'fecha', 'organizacion',
        'equipo', 'contacto',
    ];

    /** Variables de un modelo individual. `nombre_equipo` ubica al jugador. */
    private const PLAYER_TOKENS = [
        'nombre_equipo', 'edicion', 'nombre_jugador', 'tipo_premio',
        'premio', 'premio_ganador', 'premio_equipo', 'torneo', 'anio', 'categoria',
        'fecha', 'organizacion', 'jugador', 'equipo', 'camiseta', 'posicion',
        'goles', 'asistencias', 'atajadas', 'mvp', 'puntos', 'contacto',
    ];

    /**
     * Variables disponibles para un tipo de modelo, en el orden del catálogo.
     *
     * @return array<string, string>
     */
    public static function forType(string $diplomaType): array
    {
        $allowed = $diplomaType === 'team' ? self::TEAM_TOKENS : self::PLAYER_TOKENS;

        return array_intersect_key(self::TOKENS, array_flip($allowed));
    }

    /**
     * Contexto de sustitución para una edición y un premio.
     *
     * @param  array<int, array{name?: string}>  $squad  Plantel ya resuelto del equipo premiado.
     * @return array<string, string>
     */
    public function context(
        EventEdition $edition,
        ?EventEditionAward $award = null,
        ?CarbonInterface $issuedAt = null,
        array $squad = []
    ): array {
        $meta = $award?->meta ?? [];
        $date = $issuedAt ?? now();

        $playerName = $award?->recipient_name ?? 'Nombre del premiado';
        $teamName = $award?->team_name ?? '';
        $awardLabel = $award?->label ?? 'Premio';
        $squadNames = implode(', ', array_values(array_filter(array_map(
            fn ($row) => is_array($row) ? trim((string) ($row['name'] ?? '')) : '',
            $squad
        ))));

        return [
            // Variables del flujo de modelos reutilizables.
            'nombre_equipo' => $teamName ?: ($award?->isTeamAward() ? $playerName : ''),
            'edicion' => $edition->name ?? '',
            'lista_jugadores' => $squadNames,
            'puesto' => $award?->place ? $award->place.'°' : '',
            'nombre_jugador' => $playerName,
            'tipo_premio' => $awardLabel,
            // Variables anteriores, mantenidas por compatibilidad.
            'premio' => $awardLabel,
            'premio_ganador' => $playerName,
            'premio_equipo' => $teamName,
            'torneo' => $edition->evento?->title ?? 'Torneo',
            'edicion' => $edition->name ?? '',
            'anio' => (string) ($edition->year ?? ''),
            'categoria' => (string) ($edition->evento?->category?->description ?? ''),
            'puesto' => $award?->place ? $award->place.'°' : '',
            'fecha' => $date->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'organizacion' => (string) ($edition->evento?->organization?->name ?? ''),
            'jugador' => $playerName,
            'equipo' => $teamName ?: $playerName,
            'camiseta' => $this->stringValue($meta['camiseta'] ?? null),
            'posicion' => $this->stringValue($meta['posicion'] ?? null),
            'goles' => $this->stringValue($meta['goles'] ?? null),
            'asistencias' => $this->stringValue($meta['asistencias'] ?? null),
            'atajadas' => $this->stringValue($meta['atajadas'] ?? null),
            'mvp' => $this->stringValue($meta['mvp'] ?? null),
            'puntos' => $this->stringValue($meta['puntos'] ?? null),
            'contacto' => (string) ($edition->contact_whatsapp ?: $edition->contact_phone ?: ''),
        ];
    }

    /**
     * Sustituye {{token}} por su valor. Las variables desconocidas quedan vacías
     * para no imprimir llaves en el diploma.
     *
     * @param  array<string, string>  $context
     */
    public function resolve(?string $text, array $context): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/i',
            fn (array $match) => (string) ($context[strtolower($match[1])] ?? ''),
            $text
        ) ?? '';
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        }

        return (string) $value;
    }
}
