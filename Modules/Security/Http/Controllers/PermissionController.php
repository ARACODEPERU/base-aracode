<?php

namespace Modules\Security\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DataTables;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Module;
use PhpParser\Node\Expr\AssignOp\Mod;

class PermissionController extends Controller
{
    use ValidatesRequests;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return Inertia::render('Security::Permissions/List');
    }

    public function getData()
    {
        $model = Permission::query();
        $model = $model->select('id', 'name', 'guard_name', 'created_at', 'updated_at');

        return DataTables::of($model)->toJson();
    }

    public function create()
    {
        $roles = Role::all();
        $modulos = Modulo::where('status', true)->get();

        return Inertia::render(
            'Security::Permissions/Create',
            [
                'roles' => $roles,
                'modulos' => $modulos
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255|unique:permissions,name'
        ]);

        $permission = Permission::create([
            'name'       => $request->get('name'),
            'guard_name' => 'web'
        ]);

        if (!empty($request->get('roles'))) {
            $permission->assignRole($request->get('roles'));
        }

        if (!empty($request->get('modulos'))) {
            foreach ($request->get('modulos') as $moduleId) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $moduleId,
                ]);
            }
        }

    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Permission $permission)
    {
        $roles = Role::all();
        $modulos = Modulo::where('status', true)->get();
        $roleHasPermissions = array_column(json_decode($permission->roles, true), 'name');

        // Get modules associated with the permission
        $moduleIds = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', Modulo::class)
            ->pluck('model_id')
            ->toArray();

        return Inertia::render(
            'Security::Permissions/Edit',
            [
                'roles' => $roles,
                'modulos' => $modulos,
                'permission' => $permission,
                'roleHasPermissions' => $roleHasPermissions,
                'moduleIds' => $moduleIds,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, Permission $permission)
    {
        $this->validate($request, [
            'name' => 'required|max:255'
        ]);

        $permission->update([
            'name' => $request->get('name'),
        ]);

        $roles = $request->get('permissions') ?? [];
        $permission->assignRole($request->get('roles'));

        // Handle modules
        DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', Modulo::class)
            ->delete();

        if (!empty($request->get('modules'))) {
            foreach ($request->get('modules') as $moduleId) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $moduleId,
                ]);
            }
        }

        return redirect()->route('permissions.edit', $permission->id)
            ->with('message', 'Permiso actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('message', __('Permission deleted successfully'));
    }
}
