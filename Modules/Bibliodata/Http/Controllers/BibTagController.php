<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibTag;

class BibTagController extends Controller
{
    use ValidatesRequests;

    public function index()
    {
        return Inertia::render('Bibliodata::Tag/List', [
            'tags' => BibTag::all()
        ]);
    }

    public function store(Request $request)
    {
        $id = $request->get('id');

        $this->validate($request, [
            'name' => 'required|string|max:100',
        ]);

        if ($id) {
            BibTag::find($id)->update(['name' => $request->name]);
        } else {
            BibTag::create(['name' => $request->name]);
        }

        return to_route('bib_tags');
    }

    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {
            $tag = BibTag::findOrFail($id);
            $tag->delete();

            $message = 'Tag eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
