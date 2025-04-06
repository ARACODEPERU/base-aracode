<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Entities\AcaShortVideo;

class AcaShortVideoController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('academic::index');
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
            'duration'      => 'required'
        ]);

        $listId = $request->get('list_id');
        $link = true;
        if ($request->get('link') === true || $request->get('link') === "true") {
            $link = true;
        } else {
            $link = false;
        }
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
        //
    }
}
