<?php

namespace Modules\Socialevents\Support;

use Illuminate\Validation\ValidationException;

class OrganizationCapabilities
{
    /**
     * Permisos mínimos para entrar al módulo y llegar a las ediciones.
     * No se muestran como tarjeta: son la base de cualquier cuenta limitada.
     */
    public const BASE_PERMISSIONS = [
        'even_dashboard',
        'even_ediciones_listado',
    ];

    /**
     * Todos los permisos deportivos que puede recibir una cuenta operativa.
     * No incluye administración de organizaciones ni gestión de usuarios.
     */
    public const FULL_PERMISSIONS = [
        'even_dashboard',
        'even_evento_listado',
        'even_evento_nuevo',
        'even_evento_editar',
        'even_evento_eliminar',
        'even_equipos_listado',
        'even_equipos_nuevo',
        'even_equipos_editar',
        'even_equipos_eliminar',
        'even_ediciones_actas_nuevo',
        'even_ediciones_actas_solucion_update',
        'even_ediciones_actas_file_update',
        'even_ediciones_actas_protest_file_destroy',
        'even_ediciones_match_accordance_update',
        'even_ediciones_exclusiones_store',
        'even_ediciones_suspensiones_store',
        'even_ediciones_equipos_agregar',
        'even_ediciones_equipos_bonus_store',
        'even_ediciones_equipos_bonus_destroy',
        'even_ediciones_equipos_recalculate',
        'even_ediciones_pago_sanciones_store',
        'even_ediciones_sanciones_card_store',
        'even_team_player_store',
        'even_team_player_update',
        'even_team_player_image_update',
        'even_ediciones_equipos_player_destroy',
        'even_ediciones_listado',
        'even_ediciones_nuevo',
        'even_ediciones_editar',
        'even_ediciones_eliminar',
        'even_ediciones_equipos',
        'even_ediciones_equipos_eliminar',
        'even_ediciones_equipo_jugadores',
        'even_ediciones_fixtures',
        'even_ediciones_fixtures_nuevo',
        'even_ediciones_partido_resultado',
        'even_ediciones_partido_eliminar',
        'even_ediciones_sanciones',
        'even_ediciones_sanciones_registrar',
        'even_ediciones_pago_sanciones',
        'even_ediciones_partido_acta',
        'even_ediciones_actas',
        'even_ediciones_acta_editar',
        'even_ediciones_partido_acta_editar',
        'even_ediciones_galeria',
        'even_ediciones_galeria_nuevo',
        'even_ediciones_galeria_eliminar',
        'even_ediciones_exclusiones',
        'even_ediciones_suspensiones',
        'even_ediciones_diplomas',
        'even_ediciones_diplomas_disenar',
        'even_ediciones_premiados',
        'even_local_listado',
        'even_local_nuevo',
        'even_local_editar',
        'even_local_eliminar',
    ];

    /**
     * Catálogo visible para el organizador. La clave es la que se guarda en
     * socialevent_organization_users.capabilities.
     *
     * @return array<string, array{label: string, description: string, permissions: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            'results' => [
                'label' => 'Registrar resultados',
                'description' => 'Abrir los partidos y anotar el marcador.',
                'permissions' => [
                    'even_ediciones_fixtures',
                    'even_ediciones_partido_resultado',
                ],
            ],
            'acts' => [
                'label' => 'Registrar actas y sanciones',
                'description' => 'Completar actas, registrar incidencias y sanciones del partido.',
                'permissions' => [
                    'even_ediciones_fixtures',
                    'even_ediciones_actas',
                    'even_ediciones_actas_nuevo',
                    'even_ediciones_partido_acta',
                    'even_ediciones_partido_acta_editar',
                    'even_ediciones_acta_editar',
                    'even_ediciones_actas_solucion_update',
                    'even_ediciones_actas_file_update',
                    'even_ediciones_actas_protest_file_destroy',
                    'even_ediciones_match_accordance_update',
                    'even_ediciones_sanciones',
                    'even_ediciones_sanciones_registrar',
                ],
            ],
            'fixtures' => [
                'label' => 'Programar partidos',
                'description' => 'Crear y ordenar el fixture de la edición.',
                'permissions' => [
                    'even_ediciones_fixtures',
                    'even_ediciones_fixtures_nuevo',
                ],
            ],
            'teams' => [
                'label' => 'Gestionar equipos y jugadores',
                'description' => 'Inscribir equipos, agregar jugadores y administrar sus datos.',
                'permissions' => [
                    'even_equipos_listado',
                    'even_equipos_nuevo',
                    'even_equipos_editar',
                    'even_equipos_eliminar',
                    'even_ediciones_equipos',
                    'even_ediciones_equipos_eliminar',
                    'even_ediciones_equipo_jugadores',
                    'even_team_player_store',
                    'even_team_player_update',
                    'even_team_player_image_update',
                    'even_ediciones_equipos_player_destroy',
                    'even_ediciones_equipos_agregar',
                    'even_ediciones_equipos_bonus_store',
                    'even_ediciones_equipos_bonus_destroy',
                    'even_ediciones_equipos_recalculate',
                ],
            ],
            'awards' => [
                'label' => 'Entregar diplomas y premiados',
                'description' => 'Registrar premiados, diseñar diplomas y emitir los certificados del torneo.',
                'permissions' => [
                    'even_ediciones_diplomas',
                    'even_ediciones_diplomas_disenar',
                    'even_ediciones_premiados',
                ],
            ],
            'reports' => [
                'label' => 'Consultar reportes',
                'description' => 'Ver fixture, actas y galería sin modificar los registros.',
                'permissions' => [
                    'even_ediciones_fixtures',
                    'even_ediciones_actas',
                    'even_ediciones_galeria',
                ],
            ],
            'full' => [
                'label' => 'Administrar todo el torneo',
                'description' => 'Todas las tareas deportivas: torneos, equipos, fixture, resultados, actas, sanciones y locales.',
                'permissions' => self::FULL_PERMISSIONS,
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Une la base con las tareas elegidas. La tarjeta "full" ya trae todo.
     *
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    public static function permissionsFor(array $keys): array
    {
        $keys = array_values(array_unique(array_intersect($keys, self::keys())));
        if (in_array('full', $keys, true)) {
            return self::FULL_PERMISSIONS;
        }

        $permissions = self::BASE_PERMISSIONS;
        $catalog = self::all();
        foreach ($keys as $key) {
            $permissions = array_merge($permissions, $catalog[$key]['permissions']);
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Valida que las claves existan y que no se intente enviar un permiso
     * técnico directamente desde el navegador.
     *
     * @param array<int, string> $keys
     */
    public static function assertValidKeys(array $keys): void
    {
        $invalid = array_diff($keys, self::keys());
        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'capabilities' => 'Selecciona solo tareas disponibles para la organización.',
            ]);
        }
    }

    /**
     * Datos listos para la vista: la clave y su descripción, sin permisos
     * técnicos.
     *
     * @return array<int, array{key: string, label: string, description: string}>
     */
    public static function describe(): array
    {
        return array_map(
            fn (string $key, array $item) => [
                'key' => $key,
                'label' => $item['label'],
                'description' => $item['description'],
            ],
            array_keys(self::all()),
            array_values(self::all())
        );
    }

    /** @param array<int, string> $keys */
    public static function labelFor(array $keys): string
    {
        $catalog = self::all();
        $labels = [];
        foreach ($keys as $key) {
            if (isset($catalog[$key])) {
                $labels[] = $catalog[$key]['label'];
            }
        }

        return $labels ? implode(' · ', $labels) : 'Sin tareas asignadas';
    }
}
