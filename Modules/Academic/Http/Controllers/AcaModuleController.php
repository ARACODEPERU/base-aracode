<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;

class AcaModuleController extends Controller
{
    use ValidatesRequests;

    public function index($id)
    {
        $course = AcaCourse::where('id', $id)
            ->with('teachers.teacher.person')
            ->with('modules.themes.contents.exam.questions.answers')
            ->first();

        return Inertia::render('Academic::Courses/Modules', [
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
}
