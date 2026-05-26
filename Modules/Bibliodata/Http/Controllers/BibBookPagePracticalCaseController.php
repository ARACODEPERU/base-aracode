<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookPagePracticalCase;

class BibBookPagePracticalCaseController extends Controller
{
    public function index($pageId)
    {
        $page = BibBookPage::findOrFail($pageId);

        $cases = $page->practicalCases()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (BibBookPagePracticalCase $case) => $this->formatCase($case));

        return response()->json([
            'success' => true,
            'cases' => $cases,
            'count' => $cases->count(),
        ]);
    }

    public function store(Request $request, $pageId)
    {
        $page = BibBookPage::findOrFail($pageId);
        $data = $this->validateCase($request, true);

        $practicalCase = new BibBookPagePracticalCase();
        $practicalCase->page_id = $page->id;

        $this->fillCase($practicalCase, $data, $request, true);

        return response()->json([
            'success' => true,
            'case' => $this->formatCase($practicalCase->fresh()),
            'message' => 'Caso práctico registrado correctamente.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $practicalCase = BibBookPagePracticalCase::findOrFail($id);
        $data = $this->validateCase($request, false, $practicalCase);

        $this->fillCase($practicalCase, $data, $request, false);

        return response()->json([
            'success' => true,
            'case' => $this->formatCase($practicalCase->fresh()),
            'message' => 'Caso práctico actualizado correctamente.',
        ]);
    }

    public function destroy($id)
    {
        $practicalCase = BibBookPagePracticalCase::findOrFail($id);

        $this->deleteCaseFile($practicalCase);
        $practicalCase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Caso práctico eliminado correctamente.',
        ]);
    }

    private function validateCase(Request $request, bool $isCreate, ?BibBookPagePracticalCase $practicalCase = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['editor', 'image', 'document'])],
            'content_html' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        if ($data['type'] === 'editor') {
            if ($isCreate && blank(trim(strip_tags($data['content_html'] ?? '')))) {
                throw new HttpResponseException(response()->json([
                    'message' => 'El contenido del caso práctico es obligatorio.',
                ], 422));
            }

            return $data;
        }

        $file = $request->file('file');
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedDocumentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

        if ($data['type'] === 'image') {
            if (! $file && $isCreate) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Debe subir una imagen para registrar el caso práctico.',
                ], 422));
            }

            if ($file && ! in_array(strtolower($file->getClientOriginalExtension()), $allowedImageExtensions, true)) {
                throw new HttpResponseException(response()->json([
                    'message' => 'La imagen debe ser jpg, jpeg, png, gif o webp.',
                ], 422));
            }
        }

        if ($data['type'] === 'document') {
            if (! $file && $isCreate) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Debe subir un documento para registrar el caso práctico.',
                ], 422));
            }

            if ($file && ! in_array(strtolower($file->getClientOriginalExtension()), $allowedDocumentExtensions, true)) {
                throw new HttpResponseException(response()->json([
                    'message' => 'El documento debe ser pdf, doc, docx, xls o xlsx.',
                ], 422));
            }
        }

        if (
            ! $isCreate
            && ! $file
            && $practicalCase
            && $data['type'] !== 'editor'
            && (
                blank($practicalCase->file_path)
                || $practicalCase->type !== $data['type']
            )
        ) {
            throw new HttpResponseException(response()->json([
                'message' => 'Debe subir un archivo para este tipo de caso práctico.',
            ], 422));
        }

        return $data;
    }

    private function fillCase(BibBookPagePracticalCase $practicalCase, array $data, Request $request, bool $isCreate): void
    {
        $previousType = $practicalCase->type;
        $hadFileBefore = ! blank($practicalCase->file_path);
        $newType = $data['type'];
        $file = $request->file('file');

        $practicalCase->fill([
            'title' => $data['title'],
            'type' => $newType,
            'sort_order' => $data['sort_order'] ?? ($practicalCase->exists ? $practicalCase->sort_order : $this->resolveNextSortOrder($practicalCase->page_id)),
            'status' => array_key_exists('status', $data) ? (bool) $data['status'] : true,
            'content_html' => $newType === 'editor' ? ($data['content_html'] ?? '') : null,
        ]);

        if ($newType === 'editor' && $hadFileBefore) {
            $this->deleteCaseFile($practicalCase);
            $practicalCase->file_path = null;
            $practicalCase->file_name = null;
            $practicalCase->file_mime = null;
        }

        if ($newType !== 'editor' && $file) {
            if ($hadFileBefore) {
                $this->deleteCaseFile($practicalCase);
            }

            $storedPath = $file->store('uploads/bibliodata/practical-cases', 'public');
            $practicalCase->file_path = $storedPath;
            $practicalCase->file_name = $file->getClientOriginalName();
            $practicalCase->file_mime = $file->getMimeType();
        }

        if (! $isCreate && $previousType === 'editor' && $newType !== 'editor') {
            $practicalCase->content_html = null;
        }

        $practicalCase->save();
    }

    private function resolveNextSortOrder(int $pageId): int
    {
        return ((int) BibBookPagePracticalCase::where('page_id', $pageId)->max('sort_order')) + 1;
    }

    private function deleteCaseFile(BibBookPagePracticalCase $practicalCase): void
    {
        if ($practicalCase->file_path && Storage::disk('public')->exists($practicalCase->file_path)) {
            Storage::disk('public')->delete($practicalCase->file_path);
        }
    }

    private function formatCase(BibBookPagePracticalCase $practicalCase): array
    {
        return [
            'id' => $practicalCase->id,
            'page_id' => $practicalCase->page_id,
            'title' => $practicalCase->title,
            'type' => $practicalCase->type,
            'content_html' => $practicalCase->content_html ?? '',
            'file_path' => $practicalCase->file_path,
            'file_name' => $practicalCase->file_name,
            'file_mime' => $practicalCase->file_mime,
            'file_url' => $practicalCase->file_path ? asset('storage/' . $practicalCase->file_path) : null,
            'sort_order' => (int) $practicalCase->sort_order,
            'status' => (bool) $practicalCase->status,
            'updated_at' => optional($practicalCase->updated_at)->toISOString(),
        ];
    }
}
