<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Socialevents\Services\OrganizationTeamService;
use Modules\Socialevents\Services\SportsOrganizationService;

class OrganizationTeamController extends Controller
{
    public function store(Request $request, OrganizationTeamService $team, SportsOrganizationService $sports)
    {
        $organizationId = $this->organizationId($request, $sports);
        $team->assertCanManage($request->user());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:128',
            'telefono' => 'nullable|string|max:20',
            'tipo_identidad' => 'nullable|in:0,1,6',
            'numero_identidad' => 'nullable|string|max:20|unique:users,numero_identidad',
            'capabilities' => 'required|array|min:1',
            'capabilities.*' => 'string',
        ], [], [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña inicial',
            'capabilities' => 'tareas',
        ]);

        $team->createMember($request->user(), $organizationId, $validated);

        return back()->with('success', 'Usuario creado y vinculado a la organización.');
    }

    public function update(Request $request, int $userId, OrganizationTeamService $team, SportsOrganizationService $sports)
    {
        $organizationId = $this->organizationId($request, $sports);
        $team->assertCanManage($request->user());
        $validated = $request->validate([
            'capabilities' => 'required|array|min:1',
            'capabilities.*' => 'string',
        ]);

        $team->updateCapabilities($request->user(), $organizationId, $userId, $validated['capabilities']);

        return back()->with('success', 'Tareas del usuario actualizadas.');
    }

    public function destroy(Request $request, int $userId, OrganizationTeamService $team, SportsOrganizationService $sports)
    {
        $organizationId = $this->organizationId($request, $sports);
        $team->deactivate($request->user(), $organizationId, $userId);

        return back()->with('success', 'Usuario desactivado; el espacio quedó disponible.');
    }

    public function reactivate(Request $request, int $userId, OrganizationTeamService $team, SportsOrganizationService $sports)
    {
        $organizationId = $this->organizationId($request, $sports);
        $team->assertCanManage($request->user());
        $validated = $request->validate([
            'capabilities' => 'required|array|min:1',
            'capabilities.*' => 'string',
        ]);
        $team->reactivate($request->user(), $organizationId, $userId, $validated['capabilities']);

        return back()->with('success', 'Usuario reactivado.');
    }

    private function organizationId(Request $request, SportsOrganizationService $sports): int
    {
        $organizationId = $sports->organizationId($request->user());
        abort_unless($organizationId, 403, 'No hay una organización asociada a esta cuenta.');

        return $organizationId;
    }
}
