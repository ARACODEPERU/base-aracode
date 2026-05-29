<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibAuthor;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibCategory;
use Modules\Bibliodata\Entities\BibTag;

class BibBookController extends Controller
{
    use ValidatesRequests;

    public function index()
    {
        $books = BibBook::with('author.person', 'category', 'tags')
            ->when(request()->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', '%' . request()->search . '%')
                        ->orWhereHas('author.person', function ($q) {
                            $q->where('full_name', 'like', '%' . request()->search . '%');
                        });
                });
            })
            ->when(request()->category_id, function ($q) {
                $q->where('category_id', request()->category_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $categories = BibCategory::all();

        return Inertia::render('Bibliodata::Book/List', [
            'books' => $books,
            'categories' => $categories,
            'filters' => request()->all('search', 'category_id'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Bibliodata::Book/Create', [
            'authors' => BibAuthor::with('person')->orderBy('id')->get(),
            'categories' => BibCategory::all(),
            'tags' => BibTag::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, $this->metadataRules(true));

        $book = $this->persistMetadata($request, new BibBook());

        return redirect()
            ->route('bib_books_content', $book->id)
            ->with('success', 'Libro creado. Ahora puedes agregar el contenido.');
    }

    public function edit($id)
    {
        $book = BibBook::with(['author.person', 'category', 'tags'])->findOrFail($id);

        return Inertia::render('Bibliodata::Book/Edit', [
            'book' => $book,
            'authors' => BibAuthor::with('person')->orderBy('id')->get(),
            'categories' => BibCategory::all(),
            'tags' => BibTag::all(),
        ]);
    }

    public function update(Request $request)
    {
        $this->validate($request, array_merge(
            ['id' => 'required|integer|exists:bib_books,id'],
            $this->metadataRules(false)
        ));

        $book = BibBook::findOrFail($request->id);
        $this->persistMetadata($request, $book);

        return redirect()
            ->route('bib_books')
            ->with('success', 'Libro actualizado correctamente');
    }

    public function content($id)
    {
        $book = BibBook::with(['author.person', 'category'])->findOrFail($id);

        return Inertia::render('Bibliodata::Book/ContentWorkspace', [
            'book' => $book,
            'can_change_structure' => $book->canChangeContentStructure(),
        ]);
    }

    public function updateContentStructure(Request $request, $id)
    {
        $book = BibBook::findOrFail($id);

        $data = $request->validate([
            'content_structure' => 'required|in:chapter_subchapter,level_content',
        ]);

        if (! $book->canChangeContentStructure()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el formato: existen sub-capítulos. Elimínelos para continuar.',
            ], 422);
        }

        $book->update(['content_structure' => $data['content_structure']]);
        $book = $book->fresh(['author.person', 'category']);

        return response()->json([
            'success' => true,
            'book' => $book,
            'can_change_structure' => $book->canChangeContentStructure(),
        ]);
    }

    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {
            DB::beginTransaction();
            $book = BibBook::findOrFail($id);
            $book->tags()->detach();
            $book->delete();
            DB::commit();
            $message = 'Libro eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $fileName = 'editor_' . time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $path = Storage::disk('public')->putFileAs('uploads/bibliodata/editor', $file, $fileName);

        return response()->json(['url' => asset('storage/' . $path)]);
    }

    private function metadataRules(bool $isCreate = false): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'author_id' => 'required|integer|exists:bib_authors,id',
            'category_id' => 'required|integer|exists:bib_categories,id',
            'isbn' => 'nullable|string|max:20',
            'code_name' => 'nullable|string|max:100',
            'pages' => 'nullable|integer|min:0',
            'status' => 'required|in:available,restricted,archived',
            'description' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:bib_tags,id',
        ];

        if ($isCreate) {
            $rules['content_structure'] = 'required|in:chapter_subchapter,level_content';
        }

        return $rules;
    }

    private function persistMetadata(Request $request, BibBook $book): BibBook
    {
        DB::beginTransaction();
        try {
            $fill = [
                'title' => $request->title,
                'code_name' => $request->code_name,
                'description' => $request->description,
                'author_id' => $request->author_id,
                'category_id' => $request->category_id,
                'isbn' => $request->isbn,
                'pages' => $request->pages,
                'status' => $request->status,
            ];

            if (! $book->exists && $request->filled('content_structure')) {
                $fill['content_structure'] = $request->content_structure;
            }

            $book->fill($fill);
            $book->save();

            if ($request->has('tag_ids')) {
                $book->tags()->sync($request->tag_ids ?? []);
            }

            if ($request->hasFile('cover_image')) {
                $path = $request->file('cover_image')->store('uploads/bibliodata/books', 'public');
                $book->update(['cover_image' => $path]);
            }

            if ($request->hasFile('file_path')) {
                $path = $request->file('file_path')->store('uploads/bibliodata/files', 'public');
                $book->update(['file_path' => $path]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $book;
    }
}
