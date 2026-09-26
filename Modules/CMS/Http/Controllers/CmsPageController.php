<?php

namespace Modules\CMS\Http\Controllers;

use App\Models\Country;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\CMS\Entities\CmsPage;
use DataTables;

class CmsPageController extends Controller
{
    use ValidatesRequests;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return Inertia::render('CMS::Pages/List');
    }

    /**
     * Data para la tabla DataTables del listado.
     */
    public function getData()
    {
        $model = CmsPage::query();
        $model = $model->select('id', 'icon', 'description', 'route', 'main', 'status');

        return DataTables::of($model)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $countries = Country::where('status', true)->get();

        return Inertia::render('CMS::Pages/Create', [
            'countries' => $countries
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'description' => 'required|string',
                'route' => 'required',
            ],
            [
                'description.required' => 'el campo descripción es obligatorio',
                'description.string' => 'el campo descripción solo acepta letras',
                'route.required' => 'el campo ruta es obligatorio',
            ]
        );

        CmsPage::create([
            'description'   => $request->get('description'),
            'icon'          => $request->get('icon'),
            'route'         => $request->get('route'),
            'main'          => $request->get('main'),
            'status'        => $request->get('status'),
            'user_id'       => Auth::id(),
            'country_id'    => $request->get('country_id')
        ]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('cms::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $countries = Country::where('status', true)->get();
        $hey =  CmsPage::with('country')->where('id', $id)->first();

        return Inertia::render('CMS::Pages/Edit', [
            'hey' => $hey,
            'countries' => $countries
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'description' => 'required|string',
                'route' => 'required',
            ],
            [
                'description.required' => 'el campo descripción es obligatorio',
                'description.string' => 'el campo descripción solo acepta letras',
                'route.required' => 'el campo ruta es obligatorio',
            ]
        );

        CmsPage::find($id)->update([
            'description'   => $request->get('description'),
            'icon'          => $request->get('icon'),
            'route'         => $request->get('route'),
            'main'          => $request->get('main'),
            'status'        => $request->get('status'),
            'user_id'       => Auth::id(),
            'country_id'    => $request->get('country_id')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $page = CmsPage::findOrFail($id);

            // Verificamos si hay detalles asociados
            // if ($page->detalles()->exists()) {
            //     $message =  'No se puede eliminar la pagina porque tiene detalles asociados.';
            //     $success = false;
            // }

            // Si no hay detalles asociados, eliminamos.
            $page->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Pagina eliminada correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
