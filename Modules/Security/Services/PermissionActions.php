<?php

namespace Modules\Security\Services;

/**
 * Traduce los nombres de permiso del proyecto a las acciones del panel del Modo
 * Super Editor (Ver, Crear, Editar, Eliminar, Ejecutar).
 *
 * Es el ÚNICO lugar donde vive esa clasificación: la tabla de abajo dice, por
 * acción, cómo se llama en la interfaz, cómo se lee en una frase y con qué
 * sufijos se nombra un permiso de esa acción. Añadir una acción o reconocer un
 * sufijo nuevo es editar esta tabla, no repartir condiciones por el servicio.
 *
 * Dos reglas que la tabla expresa y conviene tener presentes:
 *   - Un permiso de una sola palabra (`usuarios`, `empresa`, `roles`) no lleva
 *     sufijo: es el permiso que abre una sección completa, así que su acción es
 *     Ver (el sufijo vacío está en la lista de Ver).
 *   - Lo que no se reconoce cae en "Otros" y sigue siendo configurable; nunca se
 *     oculta un permiso por no saber clasificarlo.
 */
final class PermissionActions
{
    /**
     * @var array<string, array{label: string, phrase: string, suffixes: list<string>}>
     */
    public const TABLE = [
        'ver' => [
            'label' => 'Ver',
            'phrase' => 'ver',
            'suffixes' => ['', 'listado', 'listar', 'lista', 'listas', 'ver', 'verimprimir', 'imprimir', 'consultar', 'dashboard', 'indicadores', 'reporte', 'reportes'],
        ],
        'crear' => [
            'label' => 'Crear',
            'phrase' => 'crear',
            'suffixes' => ['nuevo', 'nueva', 'crear', 'create', 'store', 'agregar', 'registrar', 'importar', 'cargar'],
        ],
        'editar' => [
            'label' => 'Editar',
            'phrase' => 'editar',
            'suffixes' => ['editar', 'edit', 'update', 'actualizar', 'modificar', 'aprobar', 'verificar', 'configurar', 'configuracion'],
        ],
        'eliminar' => [
            'label' => 'Eliminar',
            'phrase' => 'eliminar',
            'suffixes' => ['eliminar', 'destroy', 'delete', 'anular', 'borrar'],
        ],
        'ejecutar' => [
            'label' => 'Ejecutar',
            'phrase' => 'ejecutar',
            'suffixes' => ['ejecutar', 'exportar', 'enviar', 'cobrar', 'matricular', 'pagar', 'procesar', 'descargar', 'sincronizar', 'reenviar', 'generar'],
        ],
        'otros' => [
            'label' => 'Otros',
            'phrase' => 'usar',
            'suffixes' => [],
        ],
    ];

    /** Acciones en el orden en que se muestran. */
    public static function keys(): array
    {
        return array_keys(self::TABLE);
    }

    public static function label(string $action): string
    {
        return self::TABLE[$action]['label'] ?? self::TABLE['otros']['label'];
    }

    /** Verbo para frases del tipo "Roles que pueden <phrase> este elemento". */
    public static function phrase(string $action): string
    {
        return self::TABLE[$action]['phrase'] ?? self::TABLE['otros']['phrase'];
    }

    /**
     * Acción de un permiso por su nombre completo: `aca_estudiante_listado`
     * -> ver, `usuarios` -> ver, `usuarios_nuevo` -> crear, `res_insumos_compra`
     * -> otros.
     */
    public static function actionFor(string $permissionName): string
    {
        $name = trim($permissionName);

        if ($name === '') {
            return 'otros';
        }

        $parts = explode('_', $name);

        return self::actionOfSuffix(count($parts) > 1 ? (string) end($parts) : '');
    }

    /**
     * Nombre del permiso sin su sufijo de acción: `aca_estudiante_listado`
     * -> `aca_estudiante`. Si el último segmento no es una acción conocida el
     * nombre se deja entero, para no recortar de más.
     */
    public static function prefixFor(string $permissionName): string
    {
        $parts = explode('_', $permissionName);

        if (count($parts) > 1 && self::actionOfSuffix((string) end($parts)) !== 'otros') {
            array_pop($parts);
        }

        return implode('_', $parts) ?: $permissionName;
    }

    private static function actionOfSuffix(string $suffix): string
    {
        $suffix = mb_strtolower($suffix);

        foreach (self::TABLE as $action => $definition) {
            if (in_array($suffix, $definition['suffixes'], true)) {
                return $action;
            }
        }

        return 'otros';
    }
}
