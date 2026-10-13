<?php

namespace Tests\Unit\Modules\CMS;

use App\Http\Middleware\UserActivityLogMiddleware;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\CMS\Http\Controllers\CmsContactMessageController;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Unit\Modules\CMS\Concerns\BuildsContactMessagesSchema;

/**
 * Borrado de un mensaje de contacto desde /cms/contact-messages.
 *
 * El botón de la columna ACCIONES solo debe verlo quien tenga
 * `cms_mensajes_contacto_eliminar`, y quien decide de verdad es la ruta: el
 * permiso va aparte de ver y de editar, se concede a los roles de administración
 * y se puede dar a otro rol (editor de Roles) o a una persona concreta (permiso
 * directo del usuario). Aquí se fija ese contrato:
 *   - la migración crea el permiso y lo concede a los roles de administración;
 *   - con el permiso —heredado del rol o directo— el mensaje se borra;
 *   - sin él la ruta responde 403 y el mensaje sigue en el buzón;
 *   - también funciona sin JavaScript (redirección con aviso), y el borrado deja
 *     rastro en el log de quién lo hizo.
 */
class ContactMessageDeletePermissionTest extends TestCase
{
    use BuildsContactMessagesSchema;

    private const PERMISSION = 'cms_mensajes_contacto_eliminar';

    protected function setUp(): void
    {
        parent::setUp();

        // El registro de actividad es de otra responsabilidad y necesitaría su
        // propia tabla: se desactiva para no mezclar contratos.
        $this->withoutMiddleware(UserActivityLogMiddleware::class);

        $this->dropContactMessagesSchema();
        $this->createContactMessagesSchema();
    }

    protected function tearDown(): void
    {
        $this->dropContactMessagesSchema();

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // El permiso
    // -----------------------------------------------------------------

    public function test_la_migracion_crea_el_permiso_y_lo_concede_a_los_roles_de_administracion(): void
    {
        // Arriba ya corrió la migración (creó el permiso). Aquí se corre otra vez
        // con los roles de administración ya creados: es exactamente lo que pasa
        // al desplegar sobre una instalación que ya existe, y la migración está
        // escrita para poder repetirse sin duplicar nada.
        foreach (['webAdmin', 'admin', 'Administrador'] as $rolAdministrador) {
            $this->role($rolAdministrador);
        }

        (require base_path('Modules/CMS/Database/Migrations/2026_10_13_000001_add_contact_messages_delete_permission.php'))->up();

        $this->assertSame(1, Permission::where('name', self::PERMISSION)->count());

        // Los tres roles que el seeder del módulo CMS trata como administración.
        foreach (['webAdmin', 'admin', 'Administrador'] as $rolAdministrador) {
            $this->assertTrue(
                Role::findByName($rolAdministrador)->hasPermissionTo(self::PERMISSION),
                "El rol {$rolAdministrador} no recibió el permiso de borrado."
            );
        }
    }

    // -----------------------------------------------------------------
    // Borrar con permiso
    // -----------------------------------------------------------------

    public function test_un_usuario_con_el_permiso_por_su_rol_puede_eliminar(): void
    {
        $mensaje = $this->mensajeDeSpam();

        $admin = $this->usuarioConRol('admin', self::PERMISSION);

        $this->actingAs($admin)
            ->deleteJson(route('cms_contact_messages_destroy', $mensaje->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('contact_messages', ['id' => $mensaje->id]);
    }

    public function test_un_usuario_con_el_permiso_directo_puede_eliminar(): void
    {
        $mensaje = $this->mensajeDeSpam();

        // Sin ningún rol: el permiso concedido a la persona, que es como se
        // habilita el botón para un usuario concreto.
        $persona = $this->user('persona-de-confianza@example.com');
        $persona->givePermissionTo($this->permission(self::PERMISSION));

        $this->assertTrue($persona->can(self::PERMISSION));

        $this->actingAs($persona)
            ->deleteJson(route('cms_contact_messages_destroy', $mensaje->id))
            ->assertOk();

        $this->assertDatabaseMissing('contact_messages', ['id' => $mensaje->id]);
    }

    public function test_el_borrado_tambien_funciona_sin_javascript(): void
    {
        $mensaje = $this->mensajeDeSpam();

        $admin = $this->usuarioConRol('admin', self::PERMISSION);

        $this->actingAs($admin)
            ->delete(route('cms_contact_messages_destroy', $mensaje->id), [], [
                'referer' => route('cms_contact_messages_list'),
            ])
            ->assertRedirect(route('cms_contact_messages_list'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('contact_messages', ['id' => $mensaje->id]);
    }

    public function test_el_borrado_deja_rastro_de_quien_lo_hizo(): void
    {
        Log::spy();

        $mensaje = $this->mensajeDeSpam();
        $admin = $this->usuarioConRol('admin', self::PERMISSION);

        $this->actingAs($admin)
            ->deleteJson(route('cms_contact_messages_destroy', $mensaje->id))
            ->assertOk();

        // Un mensaje del buzón puede ser el reclamo de un cliente: el log dice
        // cuál se borró y quién lo borró.
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $aviso, array $contexto) => str_contains($aviso, 'mensaje eliminado')
                && $contexto['id'] === $mensaje->id
                && $contexto['user_id'] === $admin->id)
            ->once();
    }

    // -----------------------------------------------------------------
    // Sin permiso no hay borrado
    // -----------------------------------------------------------------

    public function test_sin_el_permiso_la_ruta_responde_403_y_el_mensaje_sigue_en_el_buzon(): void
    {
        $mensaje = $this->mensajeDeSpam();

        // Quien revisa el buzón y responde (ver + editar) no puede borrar.
        $recepcion = $this->usuarioConRol('recepcion', 'cms_mensajes_contacto', 'cms_mensajes_contacto_ver', 'cms_mensajes_contacto_editar');

        $this->actingAs($recepcion)
            ->deleteJson(route('cms_contact_messages_destroy', $mensaje->id))
            ->assertForbidden();

        $this->assertDatabaseHas('contact_messages', ['id' => $mensaje->id]);
    }

    public function test_un_usuario_sin_sesion_no_puede_eliminar(): void
    {
        $mensaje = $this->mensajeDeSpam();

        $this->deleteJson(route('cms_contact_messages_destroy', $mensaje->id))
            ->assertUnauthorized();

        $this->assertDatabaseHas('contact_messages', ['id' => $mensaje->id]);
    }

    // -----------------------------------------------------------------
    // El botón del listado
    // -----------------------------------------------------------------

    public function test_el_boton_del_listado_y_la_ruta_van_atados_al_mismo_permiso(): void
    {
        $rutas = file_get_contents(base_path('Modules/CMS/Routes/web.php'));
        $controlador = file_get_contents(base_path('Modules/CMS/Http/Controllers/CmsContactMessageController.php'));
        $vista = file_get_contents(base_path('Modules/CMS/Resources/assets/js/Pages/ContactMessages/List.vue'));

        // La ruta exige el permiso y el listado pregunta por ESE permiso —con
        // `can()`, que también ve los permisos directos del usuario—: así el
        // botón no le aparece a quien recibiría un 403.
        $this->assertStringContainsString('permission:' . self::PERMISSION, $rutas);
        $this->assertStringContainsString("can('" . self::PERMISSION . "')", $controlador);

        // La capacidad viaja al frontend y el botón de la columna ACCIONES solo
        // se pinta con ella, llamando a la ruta de borrado.
        $this->assertStringContainsString('canDelete', $controlador);
        $this->assertStringContainsString('v-if="canDelete"', $vista);
        $this->assertStringContainsString("route('cms_contact_messages_destroy'", $vista);
    }

    public function test_el_controlador_publica_la_capacidad_solo_a_quien_puede_borrar(): void
    {
        // El prop que pinta el botón se calcula con el mismo guard que la ruta,
        // así que se puede comprobar sin renderizar la pantalla completa.
        $admin = $this->usuarioConRol('admin', self::PERMISSION);
        $recepcion = $this->usuarioConRol('recepcion', 'cms_mensajes_contacto_ver');

        $this->assertTrue($admin->can(self::PERMISSION));
        $this->assertFalse($recepcion->can(self::PERMISSION));
    }

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

    private function mensajeDeSpam(): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Bot Promociones',
            'email' => 'bot@spam.example',
            'service' => 'otro',
            'message' => 'OFERTAS IMPERDIBLES en mi sitio',
        ]);
    }

    /**
     * Usuario con un rol al que se le conceden los permisos indicados.
     */
    private function usuarioConRol(string $rol, string ...$permisos): User
    {
        $role = $this->role($rol);

        foreach ($permisos as $permiso) {
            $role->givePermissionTo($this->permission($permiso));
        }

        $user = $this->user($rol . '@example.com');
        $user->assignRole($role);

        return $user;
    }
}
