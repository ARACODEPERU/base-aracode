<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permiso para BORRAR un mensaje de contacto desde /cms/contact-messages.
 *
 * Va aparte de `cms_mensajes_contacto` (ver el listado) y de
 * `cms_mensajes_contacto_editar` (responder y cambiar estado) para que el
 * borrado se pueda dar y quitar sin tocar lo demás: quien revisa el buzón no
 * tiene por qué poder eliminar registros.
 *
 * Se concede a los mismos tres roles que el seeder del módulo CMS trata como
 * administración (`webAdmin`, `admin` y `Administrador`), que son los que ya
 * tienen el resto del buzón. A partir de ahí, el editor de Roles del módulo
 * permite dárselo a cualquier otro rol, y también se puede conceder a una
 * persona concreta como permiso directo del usuario.
 */
return new class extends Migration
{
    private const PERMISSION = 'cms_mensajes_contacto_eliminar';

    public function up(): void
    {
        Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        // En una instalación existente los roles ya están creados. En una nueva,
        // esta migración corre antes que los seeders (todavía no hay roles) y el
        // seeder del módulo CMS —que sí incluye este permiso— termina el trabajo.
        foreach (['webAdmin', 'admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo(self::PERMISSION)) {
                $role->givePermissionTo(self::PERMISSION);
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION)->delete();
    }
};
