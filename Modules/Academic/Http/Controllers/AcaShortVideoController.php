<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaShortVideo;

class AcaShortVideoController extends Controller
{
    use ValidatesRequests;

    protected $RPTABLE;

    public function __construct()
    {
        $this->RPTABLE = env('RECORDS_PAGE_TABLE') ?? 10;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $videos = (new AcaShortVideo())->newQuery();
        if (request()->has('search')) {
            $videos->where('title', 'like', '%' . request()->input('search') . '%');
        }
        $videos->orderBy('id', 'DESC');
        $videos = $videos->paginate($this->RPTABLE);

        return Inertia::render('Academic::Tutorials/ListVideos', [
            'videos' => $videos,
            'filters' => request()->all('search')
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
    public function store(Request $request)
    {
        $this->validate($request, [
            'title'         => 'required|max:255',
            'video'         => 'required',
            'duration'      => 'required|regex:/^\d{1,3}:[0-5][0-9](?::[0-5][0-9])?$/'
        ]);

        $listId = $request->get('list_id');

        $link = true;

        if ($request->get('link') === true || $request->get('link') === "true") {
            $link = true;
        } else {
            $link = false;
        }

        $id = $request->get('id');
        $video = [];

        if ($id) {
            $video = AcaShortVideo::findOrFail($id);

            $video->update([
                'title' => $request->get('title'),
                'video' => $request->get('video'),
                'link' => $link,
                'duration' => $request->get('duration'),
                'author_id' => Auth::user()->person_id,
                'user_id' => Auth::id(),
                'keywords' => json_encode($request->get('keywords')),
                'status' => $request->get('status') ? true : false,
            ]);
        } else {
            $video = AcaShortVideo::create([
                'list_id' => $listId ?? null,
                'title' => $request->get('title'),
                'video' => $request->get('video'),
                'link' => $link,
                'duration' => $request->get('duration'),
                'author_id' => Auth::user()->person_id,
                'user_id' => Auth::id(),
                'keywords' => json_encode($request->get('keywords')),
                'status' => $request->get('status') ? true : false
            ]);
        }


        return response()->json(['video' => $video]);
    }

    /**
     * Show the specified resource.
     */
    public function studentVideos()
    {
        $videos = AcaShortVideo::where('status', true)->get();

        return response()->json([
            'videos' => $videos
        ]);
    }

    public function destroyOrUpdate(Request $request)
    {
        $message = null;
        $success = false;

        $id = $request->get('id');
        $destroy = $request->get('destroy');

        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = AcaShortVideo::findOrFail($id);

            if ($destroy) {
                // Si no hay detalles asociados, eliminamos.
                $item->delete();
                $message =  'Video eliminado correctamente';
            } else {
                $item->list_id = null;
                $item->save();
                $message =  'Video quitado de la lista correctamente';
            }



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
