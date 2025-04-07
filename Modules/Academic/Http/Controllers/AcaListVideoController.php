<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaListVideo;

class AcaListVideoController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $playLists = AcaListVideo::with('videos')->orderBy('title')->get();

        return Inertia::render('Academic::Tutorials/List', [
            'playLists' => $playLists
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('academic::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeOrUpdate(Request $request)
    {
        $this->validate(
            $request,
            [
                'title' => 'required|max:200|string',
            ]
        );

        $id = $request->get('id');

        $playList = AcaListVideo::where('id', $id)->first();

        if ($playList) {
            $playList->update([
                'title' => $request->get('title'),
                'description' => $request->get('description'),
                'status' => $request->get('status') ?? false
            ]);
        } else {
            AcaListVideo::create([
                'title' => trim($request->get('title')),
                'description' => $request->get('description') ?? null,
                'course_id' => $request->get('course_id') ?? null,
                'user_id' => Auth::id(),
                'total_duration' => 0,
                'author_id' => Auth::user()->person_id,
                'status'  => $request->get('status') ?? false,
                'total_views' => 0,
                'total_videos' => 0,
                'total_stars' => 0,
                'keywords'  => $request->get('keywords') ?? null,
            ]);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('academic::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('academic::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = AcaListVideo::findOrFail($id);

            $item->delete();
            $message =  'Se eliminado correctamente';

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();


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
