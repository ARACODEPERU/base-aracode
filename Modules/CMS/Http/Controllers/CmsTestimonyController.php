<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Services\OpenAiAssistantService;
use Modules\CMS\Entities\CmsTestimony;
use Modules\Onlineshop\Entities\OnliItem;
use Illuminate\Http\UploadedFile;

class CmsTestimonyController extends Controller
{
    protected $P000009;
    protected $reg;
    protected $P000010; ///token Tiny

    public function __construct()
    {
        $vallue = Parameter::where('parameter_code', 'P000009')->value('value_default');
        $this->P000009 = $vallue ?? 1;
        $this->reg = env('RECORDS_PAGE_TABLE');
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    /**
     * Listado de testimonios con pestanas:
     *  - editorial  : los que administra el CMS
     *  - students   : los enviados por alumnos (y los creados por el admin como si fuesen de alumnos)
     */
    public function index()
    {
        $source = request()->query('source') === CmsTestimony::SOURCE_STUDENT
            ? CmsTestimony::SOURCE_STUDENT
            : CmsTestimony::SOURCE_CMS;

        $approvalStatus = request()->query('approval_status');
        if (!in_array($approvalStatus, [
            CmsTestimony::STATUS_PENDING,
            CmsTestimony::STATUS_APPROVED,
            CmsTestimony::STATUS_REJECTED,
        ], true)) {
            $approvalStatus = null;
        }

        $testimonies = (new CmsTestimony())->newQuery()
            ->with(['product', 'course.category', 'student.person']);

        if ($source === CmsTestimony::SOURCE_STUDENT) {
            $testimonies->where('source', CmsTestimony::SOURCE_STUDENT);
        } else {
            $testimonies->where(function ($query) {
                $query->where('source', CmsTestimony::SOURCE_CMS)
                    ->orWhereNull('source');
            });
        }

        if ($approvalStatus) {
            $testimonies->where('approval_status', $approvalStatus);
        }

        if (request()->has('search')) {
            $testimonies->where('title', 'like', '%' . request()->input('search') . '%');
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $testimonies->orderBy($attribute, $sort_order);
        } elseif ($source === CmsTestimony::SOURCE_STUDENT) {
            // Los pendientes primero para facilitar la moderacion.
            $testimonies->orderByRaw("CASE WHEN approval_status = 'pending' THEN 0 ELSE 1 END")
                ->latest();
        } else {
            $testimonies->latest();
        }

        $testimonies = $testimonies->paginate($this->reg)->onEachSide(2)->withQueryString();

        $counters = [
            'all' => CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)->count(),
            'pending' => CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)
                ->where('approval_status', CmsTestimony::STATUS_PENDING)->count(),
            'approved' => CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)
                ->where('approval_status', CmsTestimony::STATUS_APPROVED)->count(),
            'rejected' => CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)
                ->where('approval_status', CmsTestimony::STATUS_REJECTED)->count(),
        ];

        return Inertia::render('CMS::Testimonies/List', [
            'testimonies' => $testimonies,
            'filters' => request()->all('search', 'source', 'approval_status'),
            'type'  => $this->P000009,
            'source' => $source,
            'approvalStatus' => $approvalStatus,
            'counters' => $counters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $items = [];

        if ($this->P000009 == 2) {
            $items = OnliItem::select(
                'id AS value',
                'name'
            )
                ->where('entitie', 'App-Models-Product')
                ->get();
        }

        return Inertia::render('CMS::Testimonies/Create', [
            'venture'  => $this->P000009,
            'items' => $items,
            'tiny_api_key' => $this->P000010
        ]);
    }

    /**
     * Formulario para que el admin cree un testimonio "como si fuese de un alumno",
     * sin vincularlo a una persona real, pero asociado a cualquier curso existente.
     */
    public function createStudent()
    {
        $courses = AcaCourse::with('category')
            ->where('status', true)
            ->orderBy('description')
            ->get()
            ->map(function (AcaCourse $course) {
                return [
                    'id' => $course->id,
                    'description' => $course->description,
                    'category' => $course->category?->description,
                    'type_description' => $course->type_description,
                ];
            });

        return Inertia::render('CMS::Testimonies/CreateStudent', [
            'courses' => $courses,
        ]);
    }

    /**
     * Guarda el testimonio creado por el admin (sin persona real).
     */
    public function storeStudent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:aca_courses,id',
            'author_name' => 'required|string|max:255',
            'author_role' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:20|max:2000',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'publish' => 'nullable|boolean',
        ], [
            'course_id.required' => 'Debes elegir el curso del testimonio.',
            'course_id.exists' => 'El curso seleccionado no existe.',
            'author_name.required' => 'Debes indicar el nombre del autor del testimonio.',
            'rating.required' => 'Selecciona la calificación en estrellas.',
            'comment.required' => 'El comentario es obligatorio.',
            'comment.min' => 'El comentario debe tener al menos 20 caracteres.',
            'photo.image' => 'La imagen no es válida.',
            'photo.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La imagen no debe pesar más de 2 MB.',
        ]);

        $course = AcaCourse::find($validated['course_id']);

        $path = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('uploads/testimonies', 'public');
        }

        $publish = $request->boolean('publish');

        CmsTestimony::create([
            'item_id' => OnliItem::where('entitie', 'Modules-Academic-Entities-AcaCourse')
                ->where('item_id', $course->id)
                ->value('id'),
            'entitie' => AcaCourse::class,
            'student_id' => null,
            'course_id' => $course->id,
            'title' => $course->description,
            'description' => $validated['comment'],
            'rating' => (int) $validated['rating'],
            'source' => CmsTestimony::SOURCE_STUDENT,
            'approval_status' => $publish ? CmsTestimony::STATUS_APPROVED : CmsTestimony::STATUS_PENDING,
            'approved_at' => $publish ? now() : null,
            'approved_by' => $publish ? Auth::id() : null,
            'author_name' => $validated['author_name'],
            'author_role' => $validated['author_role'] ?? null,
            'consent_accepted' => null,
            'consent_accepted_at' => null,
            'consent_version' => null,
            'image' => $path,
            'video' => null,
            'status' => true,
        ]);

        return redirect()
            ->route('cms_testimonies_list', ['source' => CmsTestimony::SOURCE_STUDENT])
            ->with('message', 'Testimonio registrado correctamente.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // Nada es obligatorio en un testimonio editorial: el admin puede crear
        // el registro y completarlo (o corregirlo con IA) despues.
        $this->validate(
            $request,
            [
                'author_name' => 'nullable|string|max:255',
                'author_role' => 'nullable|string|max:255',
                'item_id' => 'nullable',
                'item_label' => 'nullable|string|max:255',
                'title' => 'nullable|max:255',
                'description' => 'nullable',
                'image' => 'nullable',
                'video' => 'nullable'
            ],
            [
                'title.max' => 'el campo titulo solo acepta 255 caracteres',
                'item_label.max' => 'el campo producto o servicio solo acepta 255 caracteres',
                'author_name.max' => 'el nombre solo acepta 255 caracteres',
                'author_role.max' => 'el cargo o profesion solo acepta 255 caracteres',
            ]
        );

        // El producto o servicio se escribe libremente. Si coincide con un item
        // de tienda se enlaza; si no, se guarda solo como texto.
        $itemLabel = trim((string) $request->get('item_label'));
        $itemId = $request->get('item_id');

        if (!$itemId && $itemLabel !== '') {
            $itemId = OnliItem::where('name', $itemLabel)->value('id');
        }

        $testimony = CmsTestimony::create([
            'author_name'       => $request->get('author_name') ?: null,
            'author_role'       => $request->get('author_role') ?: null,
            'item_id'           => $itemId ?: null,
            'item_label'        => $itemLabel !== '' ? $itemLabel : null,
            'entitie'           => $itemId ? OnliItem::class : null,
            'title'             => $request->get('title'),
            'description'       => $request->get('description'),
            'video'             => $request->get('video'),
            'source'            => CmsTestimony::SOURCE_CMS,
            'approval_status'   => CmsTestimony::STATUS_APPROVED,
            'status'            => $request->get('status') ? true : false
        ]);

        $destination = 'uploads/cms/testimonies';
        $base64Image = $request->get('image');
        $path = null;

        if ($base64Image) {
            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if (PHP_OS == 'WINNT') {
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
            } else {
                $tempFile = tempnam('/var/www/html/img_temp', 'img');
            }
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile);

            $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
            $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);


            if ($file) {
                // $original_name = strtolower(trim($file->getClientOriginalName()));
                // $file_name = time() . rand(100, 999) . $original_name;
                $original_name = strtolower(trim($file->getClientOriginalName()));
                $original_name = str_replace(" ", "_", $original_name);
                $extension = $file->getClientOriginalExtension();
                $file_name = $testimony->id . '.' . $extension;
                $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                $testimony->image = $path;
                $testimony->save();
            }
        }
    }

    public function edit($id)
    {
        $items = [];

        if ($this->P000009 == 2) {
            $items = OnliItem::select(
                'id AS value',
                'name'
            )
                ->where('entitie', 'App-Models-Product')
                ->get();
        }

        return Inertia::render('CMS::Testimonies/Edit', [
            'venture'  => $this->P000009,
            'testimony' => CmsTestimony::with('product')->find($id),
            'items' => $items,
            'tiny_api_key' => $this->P000010
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // Igual que al crear: ningun campo es obligatorio.
        $this->validate(
            $request,
            [
                'author_name' => 'nullable|string|max:255',
                'author_role' => 'nullable|string|max:255',
                'item_id' => 'nullable',
                'item_label' => 'nullable|string|max:255',
                'title' => 'nullable|max:255',
                'description' => 'nullable',
                'video' => 'nullable'
            ],
            [
                'title.max' => 'el campo titulo solo acepta 255 caracteres',
                'item_label.max' => 'el campo producto o servicio solo acepta 255 caracteres',
                'author_name.max' => 'el nombre solo acepta 255 caracteres',
                'author_role.max' => 'el cargo o profesion solo acepta 255 caracteres',
            ]
        );

        // El producto o servicio es texto libre; si coincide con un item de
        // tienda se enlaza, si no se guarda solo el texto.
        $itemLabel = trim((string) $request->get('item_label'));
        $itemId = $request->get('item_id');

        if ($itemLabel !== '') {
            $itemId = OnliItem::where('name', $itemLabel)->value('id') ?: $itemId;
        }

        $testimony = CmsTestimony::find($request->get('id'));
        $testimony->author_name = $request->get('author_name') ?: null;
        $testimony->author_role = $request->get('author_role') ?: null;
        $testimony->item_id = $itemId ?: null;
        $testimony->item_label = $itemLabel !== '' ? $itemLabel : null;
        $testimony->entitie = $testimony->item_id ? OnliItem::class : null;
        $testimony->title = $request->get('title');
        $testimony->description = $request->get('description');
        $testimony->video = $request->get('video');
        $testimony->status = $request->get('status') ? true : false;

        $destination = 'uploads/cms/testimonies';
        $base64Image = $request->get('image');
        $path = null;

        if ($base64Image) {
            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if (PHP_OS == 'WINNT') {
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
            } else {
                $tempFile = tempnam('/var/www/html/img_temp', 'img');
            }
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile);

            $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
            $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

            if ($file) {
                $original_name = strtolower(trim($file->getClientOriginalName()));
                $original_name = str_replace(" ", "_", $original_name);
                $extension = $file->getClientOriginalExtension();
                $file_name = $testimony->id . '.' . $extension;
                $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                $testimony->image = $path;
            }
        }

        $testimony->save();
    }

    /**
     * Corrige la redaccion y ortografia (o solo la ortografia) del testimonio
     * usando la conexion de IA del sistema. No guarda: devuelve el texto para
     * que el admin lo revise en el modal de modificacion.
     */
    public function correctWithIa(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer|exists:cms_testimonies,id',
            'text' => 'nullable|string|max:5000',
            'mode' => 'required|in:writing,spelling',
        ], [
            'mode.required' => 'Indica el tipo de correccion a realizar.',
            'mode.in' => 'El tipo de correccion no es válido.',
        ]);

        $text = $request->filled('text')
            ? (string) $request->input('text')
            : (string) optional(CmsTestimony::find($request->input('id')))->description;

        $text = trim($text);

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'No hay texto para corregir.',
            ]);
        }

        try {
            $corrected = app(OpenAiAssistantService::class)->correctText(
                Auth::id() ?? 'cms',
                $text,
                $request->input('mode') === 'spelling'
            );

            return response()->json([
                'success' => true,
                'original' => $text,
                'corrected' => trim($corrected),
                'mode' => $request->input('mode'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo corregir con IA: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Modificacion completa desde el panel: autor, estrellas, comentario,
     * imagen (reemplazar o quitar), video de editoriales y visibilidad/estado.
     */
    public function adminUpdate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:cms_testimonies,id',
            'author_name' => 'nullable|string|max:255',
            'author_role' => 'nullable|string|max:255',
            'rating' => 'nullable|integer|min:1|max:5',
            'description' => 'required|string|min:1|max:2000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'video' => 'nullable|string|max:20000',
            'remove_video' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'approval_status' => 'required|in:approved,pending',
            'ia_correction_mode' => 'nullable|in:writing,spelling',
        ], [
            'description.required' => 'El comentario es obligatorio.',
            'description.max' => 'El comentario no debe superar los 2000 caracteres.',
            'image.image' => 'La imagen no es válida.',
            'image.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'image.max' => 'La imagen no debe pesar más de 2 MB.',
            'rating.min' => 'La calificación mínima es 1 estrella.',
            'rating.max' => 'La calificación máxima es 5 estrellas.',
            'approval_status.required' => 'Indica el estado del testimonio.',
        ]);

        $testimony = CmsTestimony::findOrFail($validated['id']);

        if ($request->filled('author_name')) {
            $testimony->author_name = $validated['author_name'];
        }

        if ($request->has('author_role')) {
            $testimony->author_role = $request->get('author_role') ?: null;
        }

        if ($request->filled('rating')) {
            $testimony->rating = (int) $validated['rating'];
        }

        $testimony->description = $validated['description'];

        // Imagen: reemplazar o quitar.
        if ($request->hasFile('image')) {
            $testimony->image = $request->file('image')->store('uploads/testimonies', 'public');
        } elseif ($request->boolean('remove_image')) {
            $testimony->image = null;
        }

        // El video iframe solo aplica a los testimonios editoriales.
        if ($testimony->source === CmsTestimony::SOURCE_CMS) {
            if ($request->boolean('remove_video')) {
                $testimony->video = null;
            } elseif ($request->has('video')) {
                $testimony->video = $validated['video'] ?? null;
            }
        }

        // Auditoria: si el comentario guardado vino de una correccion con IA.
        if ($request->filled('ia_correction_mode')) {
            $testimony->ia_corrected_at = now();
            $testimony->ia_correction_mode = $validated['ia_correction_mode'];
        }

        // Visibilidad y estado de aprobacion (permite despublicar sin rechazar).
        $testimony->status = $request->boolean('status');
        $testimony->approval_status = $validated['approval_status'];

        if ($testimony->approval_status === CmsTestimony::STATUS_APPROVED) {
            $testimony->approved_at = $testimony->approved_at ?: now();
            $testimony->approved_by = $testimony->approved_by ?: Auth::id();
        } else {
            $testimony->approved_at = null;
            $testimony->approved_by = Auth::id();
        }

        $testimony->save();

        return back()->with('message', 'Testimonio actualizado correctamente.');
    }

    /**
     * Aprueba un testimonio para que se vea en la pagina publica.
     */
    public function approve($id)
    {
        try {
            $testimony = CmsTestimony::findOrFail($id);

            $testimony->update([
                'approval_status' => CmsTestimony::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'status' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonio aprobado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rechaza un testimonio: deja de mostrarse y el alumno no ve el estado.
     */
    public function reject($id)
    {
        try {
            $testimony = CmsTestimony::findOrFail($id);

            $testimony->update([
                'approval_status' => CmsTestimony::STATUS_REJECTED,
                'approved_at' => null,
                'approved_by' => Auth::id(),
                'status' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonio rechazado.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
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
            $testimony = CmsTestimony::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $testimony->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Testimonio eliminado correctamente';
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
