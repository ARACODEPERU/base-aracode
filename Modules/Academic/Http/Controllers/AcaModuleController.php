<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Illuminate\Support\Facades\Storage;
class AcaModuleController extends Controller
{
    use ValidatesRequests;

    public function index($id)
    {
        $course = AcaCourse::where('id', $id)
            ->with('teachers.teacher.person')
            ->with([
                'modules.themes.contents.exam.questions.answers',
                'modules.exam'
            ])
            ->first();

        return Inertia::render('Academic::Courses/Modules-Redesigned', [
            'course' => $course
        ]);
    }

    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'position' => 'required|max:4',
                'description' => 'required|max:200'
            ]
        );

        $module = AcaModule::create([
            'course_id'     => $request->get('course_id'),
            'position'      => $request->get('position'),
            'description'   => $request->get('description')
        ]);

        return response()->json([
            'module' => $module
        ]);
    }


    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'position' => 'required|max:4',
                'description' => 'required|max:200'
            ]
        );

        $module = AcaModule::find($id);

        $module->update([
            'position'      => $request->get('position'),
            'description'   => $request->get('description')
        ]);

        return response()->json([
            'module' => $module
        ]);
    }


    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {

            DB::beginTransaction();

            $item = AcaModule::findOrFail($id);

            $item->delete();

            DB::commit();

            $message =  'Modulo eliminada correctamente';
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

    public function getThemeByModelId($id)
    {
        $themes = AcaTheme::with('contents')->where('module_id', $id)->get();
        return response()->json(['themes' => $themes]);
    }

    public function updateTeacher(Request $request)
    {
        $module_id = $request->get('module_id');
        $teacher_id = $request->get('teacher_id');
        $module = AcaModule::findOrFail($module_id);
        if ($module) {
            $module->update([
                'teacher_id' => $teacher_id ?? null
            ]);
        }

        return response()->json(['success' => true]);
    }


    public function updateOrCreateExam(Request $request)
    {
        // 1. Validación de los campos recibidos
        $this->validate($request, [
            'course_id'        => 'required',
            'module_id'        => 'required',
            'description'      => 'required|string|max:255',
            'date_start'       => 'required|date',
            'date_end'         => 'required|date|after_or_equal:date_start',
            'duration_minutes' => 'required|numeric|min:1',
            'attempts'         => 'required|numeric|min:1',
            'status'           => 'required',
            'answer_key_pdf'   => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        // 2. Preparar los datos básicos para la persistencia
        $data = [
            'description'      => $request->get('description'),
            'date_start'       => $request->get('date_start'),
            'date_end'         => $request->get('date_end'),
            'duration_minutes' => $request->get('duration_minutes'),
            'attempts'         => $request->get('attempts'),
            'status'           => $request->get('status'),
            'status'           => $request->get('status'),
        ];

        // 3. Lógica de subida de archivo personalizada
        if ($request->hasFile('answer_key_pdf')) {
            $file = $request->file('answer_key_pdf');

            // Procesar nombre original según tu requerimiento
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);

            $extension = $file->getClientOriginalExtension();
            $file_name = time() . rand(100, 999) . '.' . $extension;

            $destination = 'uploads/courses/modules';

            // Guardar el archivo con el nombre generado
            $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);

            // Asignar a los campos correspondientes
            $data['file_resolved_name'] = $original_name;
            $data['file_resolved_path'] = $path;
        }

        // 4. Update or Create basado en curso y módulo
        AcaExam::updateOrCreate(
            [
                'course_id' => $request->course_id,
                'module_id' => $request->module_id,
            ],
            $data
        );


    }
}
