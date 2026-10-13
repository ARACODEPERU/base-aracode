<?php

namespace Modules\CRM\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

/**
 * Permite que los administradores operen el chat "como" un usuario con rol
 * Asistente (hacerse pasar por el): los endpoints resuelven la persona y el
 * usuario efectivos a partir de los parametros acting_person_id/acting_user_id.
 *
 * Sin esos parametros el comportamiento es identico al de siempre.
 */
trait ResuelveIdentidadAsistente
{
    /**
     * Nombre del permiso que habilita ver el chat como los asistentes.
     */
    protected string $permisoAsistentes = 'crm_chat_asistente';

    /**
     * Solo los administradores (y con el permiso) pueden ver como los asistentes.
     */
    protected function puedeVerComoAsistentes(?User $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (! $user) {
            return false;
        }

        if (! $user->hasAnyRole(['admin', 'Administrador'])) {
            return false;
        }

        try {
            return $user->hasPermissionTo($this->permisoAsistentes);
        } catch (\Throwable $e) {
            // Permiso aun no creado (migracion pendiente): se niega el acceso.
            return false;
        }
    }

    /**
     * Usuarios que tienen alguno de los roles indicados.
     *
     * El rol Asistente lo crea cada instalacion cuando arma su equipo de
     * atencion, asi que puede no existir todavia. Spatie lanza RoleDoesNotExist
     * al consultarlo y la pantalla que solo queria avisar de que no hay
     * asistentes se caia con un error 500: aqui se consulta unicamente por los
     * roles que existen de verdad.
     */
    protected function usuariosConRol(string|array $roles): Builder
    {
        $existentes = collect((array) $roles)
            ->filter(fn (string $nombre) => Role::query()
                ->where('name', $nombre)
                ->where('guard_name', 'web')
                ->exists())
            ->values()
            ->all();

        if ($existentes === []) {
            // Sin roles que consultar se devuelve una consulta vacia para que
            // quien llama siga encadenando with(), where() o pluck().
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()->role($existentes);
    }

    /**
     * Usuario con rol Asistente que se esta suplantando en este request.
     */
    protected function asistenteSuplantado(Request $request): ?User
    {
        $personId = $request->input('acting_person_id') ?? $request->query('acting_person_id');
        $userId = $request->input('acting_user_id') ?? $request->query('acting_user_id');

        if (! $personId && ! $userId) {
            return null;
        }

        if (! $this->puedeVerComoAsistentes()) {
            abort(403, 'No tienes permiso para ver el chat como un asistente.');
        }

        $asistente = $this->usuariosConRol('Asistente')
            ->when($userId, fn ($query) => $query->where('id', $userId))
            ->when($personId, fn ($query) => $query->where('person_id', $personId))
            ->first();

        if (! $asistente) {
            abort(403, 'El usuario indicado no es un asistente.');
        }

        return $asistente;
    }

    /**
     * person_id con el que debe operar el chat.
     */
    protected function personaEfectiva(Request $request): ?int
    {
        return $this->asistenteSuplantado($request)?->person_id ?? Auth::user()?->person_id;
    }

    /**
     * users.id con el que debe operar el chat (participantes, hilos de IA, etc).
     */
    protected function usuarioEfectivo(Request $request): ?int
    {
        return $this->asistenteSuplantado($request)?->id ?? Auth::id();
    }

    /**
     * Usuarios que ademas reciben el aviso en tiempo real: los admins, para que
     * su campanita ("Mensaje para tus Asistentes") se actualice sola.
     */
    protected function usuariosNotificadosAdicionales(): array
    {
        return $this->usuariosConRol(['admin', 'Administrador'])
            ->pluck('id')
            ->all();
    }
}
