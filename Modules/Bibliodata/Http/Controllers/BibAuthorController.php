<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibAuthor;

class BibAuthorController extends Controller
{
    use ValidatesRequests;

    public function index()
    {
        $authors = BibAuthor::with('person')
            ->when(request()->search, function ($query) {
                $query->whereHas('person', function ($q) {
                    $q->where('full_name', 'like', '%' . request()->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return Inertia::render('Bibliodata::Author/List', [
            'authors' => $authors,
            'filters' => request()->all('search')
        ]);
    }

    public function create()
    {
        $identityDocumentTypes = DB::table('identity_document_type')->get();
        $countries = Country::orderBy('description')->get();

        return Inertia::render('Bibliodata::Author/Create', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'countries' => $countries,
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'document_type_id' => 'required|integer',
            'number' => 'required|string|max:20',
            'names' => 'required|string|max:100',
            'father_lastname' => 'required|string|max:100',
            'mother_lastname' => 'nullable|string|max:100',
            'gender' => 'nullable|in:M,F',
            'birthdate' => 'nullable|date',
            'country_id' => 'nullable|integer',
            'biography' => 'nullable|string',
            'specialty' => 'nullable|string|max:200',
            'webpage' => 'nullable|url|max:500',
        ]);

        DB::beginTransaction();
        try {
            $person = Person::updateOrCreate(
                [
                    'document_type_id' => $request->document_type_id,
                    'number' => $request->number,
                ],
                [
                    'names' => $request->names,
                    'father_lastname' => $request->father_lastname,
                    'mother_lastname' => $request->mother_lastname ?? '',
                    'full_name' => trim($request->names . ' ' . $request->father_lastname . ' ' . ($request->mother_lastname ?? '')),
                    'gender' => $request->gender,
                    'birthdate' => $request->birthdate,
                    'country_id' => $request->country_id,
                ]
            );

            BibAuthor::updateOrCreate(
                ['person_id' => $person->id],
                [
                    'biography' => $request->biography,
                    'specialty' => $request->specialty,
                    'webpage' => $request->webpage,
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al guardar el autor: ' . $e->getMessage()]);
        }

        return redirect()->route('bib_authors')->with('success', 'Autor registrado correctamente');
    }

    public function edit($id)
    {
        $author = BibAuthor::with('person')->findOrFail($id);
        $identityDocumentTypes = DB::table('identity_document_type')->get();
        $countries = Country::orderBy('description')->get();

        return Inertia::render('Bibliodata::Author/Edit', [
            'author' => $author,
            'identityDocumentTypes' => $identityDocumentTypes,
            'countries' => $countries,
        ]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'person_id' => 'required|integer',
            'document_type_id' => 'required|integer',
            'number' => 'required|string|max:20',
            'names' => 'required|string|max:100',
            'father_lastname' => 'required|string|max:100',
            'mother_lastname' => 'nullable|string|max:100',
            'gender' => 'nullable|in:M,F',
            'birthdate' => 'nullable|date',
            'country_id' => 'nullable|integer',
            'biography' => 'nullable|string',
            'specialty' => 'nullable|string|max:200',
            'webpage' => 'nullable|url|max:500',
        ]);

        DB::beginTransaction();
        try {
            $person = Person::findOrFail($request->person_id);
            $person->update([
                'document_type_id' => $request->document_type_id,
                'number' => $request->number,
                'names' => $request->names,
                'father_lastname' => $request->father_lastname,
                'mother_lastname' => $request->mother_lastname ?? '',
                'full_name' => trim($request->names . ' ' . $request->father_lastname . ' ' . ($request->mother_lastname ?? '')),
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'country_id' => $request->country_id,
            ]);

            $author = BibAuthor::findOrFail($request->id);
            $author->update([
                'biography' => $request->biography,
                'specialty' => $request->specialty,
                'webpage' => $request->webpage,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el autor: ' . $e->getMessage()]);
        }

        return redirect()->route('bib_authors')->with('success', 'Autor actualizado correctamente');
    }

    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {
            DB::beginTransaction();

            $author = BibAuthor::findOrFail($id);
            $person_id = $author->person_id;
            $person = Person::findOrFail($person_id);
            $author->delete();
            $person->delete();

            DB::commit();

            $message = 'Autor eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
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
