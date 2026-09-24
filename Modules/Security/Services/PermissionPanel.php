<?php

namespace Modules\Security\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Security\Entities\SuperEditorStagedChange;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Datos del panel "Configurar permisos de acceso".
 *
 * Vive aparte de SuperEditorService a propósito: aquel es el ciclo de vida del
 * modo (entrar, borrador, aplicar, auditar) y esto es lo que el panel pinta
 * (acciones de un elemento, estado de los roles y borrador de esa familia de
 * permisos). Se construye sin sesión de edición y sin petición HTTP: solo recibe
 * el id de la sesión cuando necesita leer el borrador, así que se puede probar
 * por su cuenta.
 *
 * La clasificación de los permisos (qué sufijo es Ver / Crear / ...) no se
 * decide aquí: la tabla única está en PermissionActions.
 */
class PermissionPanel
{
    /** Permisos exigidos por rutas, memorizados para toda la petición. */
    private ?array $routePermissions = null;

    /**
     * Todo lo que necesita el panel para un elemento.
     */
    public function element(string $permissionName, array $meta = [], ?int $sessionId = null): array
    {
        $permissionName = trim($permissionName);
        $groups = $this->actions($permissionName);
        $names = $this->permissionNames($groups);

        return [
            'element' => [
                'permission' => $permissionName,
                'exists' => Permission::where('name', $permissionName)->exists(),
                'label' => Str::limit(strip_tags((string) ($meta['label'] ?? '')), 120, '') ?: $permissionName,
                'kind' => (string) ($meta['kind'] ?? 'elemento'),
                'url' => Str::limit((string) ($meta['url'] ?? ''), 490, ''),
                'actions' => $groups,
            ],
            'roles' => $this->roles($names, $sessionId),
            'staged' => $this->stagedChanges($names, $sessionId),
        ];
    }

    /**
     * Acciones que ofrece el panel para un elemento, una por acción y no una
     * por permiso: `usuarios` y `usuarios_ver` son dos formas de decir "Ver",
     * así que salen como una pestaña con dos candidatos y no como dos pestañas
     * idénticas.
     *
     * Cada grupo trae el permiso que se edita (`permission`), si es el del
     * propio elemento (`own`), si alguna ruta lo exige (`enforced`, lo que
     * separa un permiso en uso de uno huérfano) y las alternativas de la acción
     * en orden de preferencia, para que ninguna quede inaccesible.
     */
    public function actions(string $permissionName): array
    {
        $permissionName = trim($permissionName);
        $enforced = array_flip($this->routePermissions());
        $byAction = [];

        foreach ($this->siblingPermissions($permissionName) as $name) {
            $byAction[PermissionActions::actionFor($name)][] = [
                'permission' => $name,
                'own' => $name === $permissionName,
                'enforced' => isset($enforced[$name]),
            ];
        }

        // Preferencia: el permiso del propio elemento, luego uno exigido por
        // alguna ruta, luego el nombre más corto (el permiso suelto del prefijo,
        // que es el que este proyecto usa para abrir la sección).
        $priority = fn (array $candidate) => [
            $candidate['own'] ? 0 : 1,
            $candidate['enforced'] ? 0 : 1,
            strlen($candidate['permission']),
        ];

        $groups = [];

        foreach (PermissionActions::keys() as $action) {
            if (empty($byAction[$action])) {
                continue;
            }

            $candidates = $byAction[$action];
            usort($candidates, fn (array $a, array $b) => $priority($a) <=> $priority($b));

            $groups[] = [
                'action' => $action,
                'label' => PermissionActions::label($action),
                'phrase' => PermissionActions::phrase($action),
                'permission' => $candidates[0]['permission'],
                'own' => $candidates[0]['own'],
                'enforced' => $candidates[0]['enforced'],
                'candidates' => $candidates,
            ];
        }

        return $groups;
    }

    /**
     * Roles con el estado (real o en borrador) de los permisos indicados.
     */
    public function roles(array $permissionNames, ?int $sessionId = null): array
    {
        $staged = $this->stagedChanges($permissionNames, $sessionId);

        return Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) use ($permissionNames, $staged) {
                // Los permisos concedidos se leen de la relación y no con
                // hasPermissionTo(): el panel también muestra elementos cuyo
                // permiso todavía no existe, y ese método lanzaría excepción.
                $granted = $role->permissions->pluck('name')->all();
                $current = [];
                $draft = [];

                foreach ($permissionNames as $name) {
                    $current[$name] = in_array($name, $granted, true);

                    $change = $this->findChange($staged, (int) $role->id, $name);

                    $draft[$name] = $change === null ? null : (bool) $change['allowed'];
                }

                return [
                    'id' => (int) $role->id,
                    'name' => $role->name,
                    'current' => $current,
                    'staged' => $draft,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Todos los permisos que el panel puede editar para un elemento, en el orden
     * de las acciones.
     */
    private function permissionNames(array $groups): array
    {
        $names = [];

        foreach ($groups as $group) {
            foreach ($group['candidates'] as $candidate) {
                $names[$candidate['permission']] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * Cambios en borrador de estos permisos en esta sesión de edición (sin
     * sesión no hay borrador).
     */
    private function stagedChanges(array $permissionNames, ?int $sessionId): array
    {
        if ($sessionId === null || $permissionNames === []) {
            return [];
        }

        return SuperEditorStagedChange::where('session_id', $sessionId)
            ->whereIn('permission_name', $permissionNames)
            ->get()
            ->map(fn (SuperEditorStagedChange $change) => [
                'permission' => $change->permission_name,
                'role_id' => (int) $change->role_id,
                'role_name' => $change->role_name,
                'allowed' => (bool) $change->allowed,
                'allowed_before' => (bool) $change->allowed_before,
            ])
            ->all();
    }

    private function findChange(array $changes, int $roleId, string $permissionName): ?array
    {
        foreach ($changes as $change) {
            if ($change['role_id'] === $roleId && $change['permission'] === $permissionName) {
                return $change;
            }
        }

        return null;
    }

    /**
     * Permisos de la misma familia que uno dado: mismo prefijo
     * (`integrationhub_listado` -> todos los `integrationhub_*`) más el permiso
     * suelto del prefijo (`usuarios`), que es el que este proyecto usa para
     * abrir la sección.
     */
    private function siblingPermissions(string $permissionName): array
    {
        $prefix = PermissionActions::prefixFor($permissionName);
        $limit = max(1, (int) config('security.super_editor.max_group_size', 20));

        // LIKE ancho y filtro estricto en PHP: el guion bajo es comodín en SQL
        // (y no significa lo mismo en mysql que en sqlite), así que la decisión
        // de quién es hermano de quién no se toma en código, no en la consulta.
        $candidates = Permission::query()
            ->where('name', 'like', $prefix . '%')
            ->orderBy('name')
            ->limit(200)
            ->pluck('name')
            ->all();

        $names = [];

        foreach ($candidates as $candidate) {
            if ($candidate === $permissionName || $candidate === $prefix || str_starts_with($candidate, $prefix . '_')) {
                $names[] = $candidate;
            }

            if (count($names) >= $limit) {
                break;
            }
        }

        if (! in_array($permissionName, $names, true)) {
            array_unshift($names, $permissionName);
        }

        return $names;
    }

    /**
     * Permisos exigidos por las rutas registradas mediante el middleware
     * `permission:` de spatie: la capa que de verdad bloquea el backend. Un
     * permiso que no está aquí existe en la tabla pero nadie lo pide (por caso
     * `usuarios_ver`), y el panel lo advierte.
     *
     * Se lee el middleware declarado en la propia ruta y no
     * `gatherMiddleware()`, porque ese último resuelve el controlador por el
     * contenedor: pintar una etiqueta no puede costar construir controladores
     * ajenos.
     */
    private function routePermissions(): array
    {
        if ($this->routePermissions !== null) {
            return $this->routePermissions;
        }

        $names = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->middleware() as $middleware) {
                if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                    continue;
                }

                foreach (preg_split('/[|,]/', substr($middleware, strlen('permission:'))) as $name) {
                    if (($name = trim($name)) !== '') {
                        $names[$name] = true;
                    }
                }
            }
        }

        return $this->routePermissions = array_keys($names);
    }
}
