<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibAuthor;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibBookSection;
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
        $authors = BibAuthor::with('person')->orderBy('id')->get();
        $categories = BibCategory::all();
        $tags = BibTag::all();

        return Inertia::render('Bibliodata::Book/Create', [
            'authors' => $authors,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
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
            'sections' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $book = BibBook::create([
                'title' => $request->title,
                'code_name' => $request->code_name,
                'description' => $request->description,
                'author_id' => $request->author_id,
                'category_id' => $request->category_id,
                'isbn' => $request->isbn,
                'pages' => $request->pages,
                'status' => $request->status,
            ]);

            if ($request->tag_ids) {
                $book->tags()->sync($request->tag_ids);
            }

            if ($request->sections) {
                $this->saveSections($book->id, null, $request->sections);
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
            return redirect()->back()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }

        return redirect()->route('bib_books')->with('success', 'Libro creado correctamente');
    }

    public function edit($id)
    {
        $book = BibBook::with([
            'author.person',
            'category',
            'tags',
            'sections' => function ($q) { $q->whereNull('parent_id')->orderBy('order'); },
            'sections.pages' => function ($q) { $q->orderBy('page_number'); },
            'sections.children' => function ($q) { $q->orderBy('order'); },
            'sections.children.pages' => function ($q) { $q->orderBy('page_number'); },
        ])->findOrFail($id);

        $authors = BibAuthor::with('person')->orderBy('id')->get();
        $categories = BibCategory::all();
        $tags = BibTag::all();
        //dd( $book );
        return Inertia::render('Bibliodata::Book/Edit', [
            'book' => $book,
            'authors' => $authors,
            'categories' => $categories,
            'tags' => $tags,
        ]);

    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer|exists:bib_books,id',
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
            'sections' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $book = BibBook::findOrFail($request->id);
            $book->update([
                'title' => $request->title,
                'code_name' => $request->code_name,
                'description' => $request->description,
                'author_id' => $request->author_id,
                'category_id' => $request->category_id,
                'isbn' => $request->isbn,
                'pages' => $request->pages,
                'status' => $request->status,
            ]);

            if ($request->tag_ids !== null) {
                $book->tags()->sync($request->tag_ids);
            }

            // Reemplazar secciones: eliminar existentes y crear nuevas
            BibBookSection::where('book_id', $book->id)->delete();

            if ($request->sections) {
                $this->saveSections($book->id, null, $request->sections);
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
            return redirect()->back()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }

        return redirect()->route('bib_books')->with('success', 'Libro actualizado correctamente');
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
            $success = false;
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
        $url = asset('storage/' . $path);

        return response()->json(['url' => $url]);
    }

    private function saveSections($bookId, $parentId, array $sections)
    {
        foreach ($sections as $order => $section) {
            $sec = BibBookSection::create([
                'book_id' => $bookId,
                'parent_id' => $parentId,
                'title' => $section['title'],
                'order' => $order,
            ]);

            if (!empty($section['pages'])) {
                foreach ($section['pages'] as $pageNum => $page) {
                    \Modules\Bibliodata\Entities\BibBookPage::create([
                        'section_id' => $sec->id,
                        'page_number' => $pageNum + 1,
                        'content' => $page['content'] ?? '',
                    ]);
                }
            }

            if (!empty($section['children'])) {
                $this->saveSections($bookId, $sec->id, $section['children']);
            }
        }
    }
}
