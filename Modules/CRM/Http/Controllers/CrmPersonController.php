<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentHistory;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Entities\AcaThemeComment;

class CrmPersonController extends Controller
{
    protected $P000009;

    public function __construct()
    {
        $this->P000009 = Parameter::where('parameter_code', 'P000009')->value('value_default');
        // $this->P000009 si es 1 = cursos y capacitaciones
        // $this->P000009 si es 99 = todos
    }

    public function searchPerson(Request $request)
    {
        $search = $request->get('search');

        $model = Person::query();
        $selectFields = ['people.*']; // Siempre incluir 'people.*'

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $model = $model->join('aca_students', 'aca_students.person_id', 'people.id');

            $selectFields[] = 'aca_students.new_student'; // Agregar 'new_student' dinámicamente
        }
        // Puedes agregar más condiciones similares aquí con otros joins y campos
        $model = $model->select($selectFields); // Aplicar el select con todos los campos acumulados
        $model = $model->where('people.id', '<>', 1);
        $model = $model->where('people.is_client', true);
        $model = $model->where(function (Builder $query) use ($search) {
            $query->where('people.number', '=', $search)
                ->orWhere('people.full_name', 'like', '%' . $search . '%');
        });

        return response()->json([
            'persons' => $model->get()
        ]);
    }

    public function store(Request $request)
    {
        $person_id = $request->get('person_id');
        $company_id = $request->get('company_id');

        Person::find($person_id)->update([
            'company_person_id' => $company_id
        ]);
    }

    public function personDetails($company_id, $person_id)
    {
        $employee = Person::find($person_id);
        $empresa = Person::find($company_id);
        $student = AcaStudent::where('person_id', $person_id)->first();
        $user = User::where('person_id', $person_id)->first();
        $userId = $user->id;

        Carbon::setLocale('es'); // Mejor hacerlo una sola vez, fuera del map()

        $courses = AcaCapRegistration::with('course')
            ->where('student_id', $student->id)
            ->get()
            ->map(function ($item) use ($person_id, $userId) {

                // Formatear la fecha en español con la primera letra en mayúscula
                $item->formatted_date = $item->created_at
                    ? ucfirst(Carbon::parse($item->created_at)->translatedFormat('d M Y'))
                    : null;

                // Inicializar valores por defecto
                $item->total_contents = 0;
                $item->total_activity = 0;

                // Verificar si el curso existe antes de acceder a sus propiedades
                if ($item->course && isset($item->course->id)) {
                    $courseId = $item->course->id;

                    // Total de contenidos
                    $item->total_contents = AcaContent::whereHas('theme.module.course', function ($query) use ($courseId) {
                        $query->where('id', $courseId);
                    })->count();

                    // Total de actividades del estudiante
                    $item->total_activity = AcaStudentHistory::where('person_id', $person_id)
                        ->where('course_id', $courseId)
                        ->count('content_id');
                    $coments = AcaThemeComment::where('course_id', $courseId)
                        ->where('user_id', $userId)
                        ->count();

                    $item->total_coments = $coments;
                }

                return $item;
            });


        return Inertia::render('CRM::Companies/EmployeeDetails', [
            'employee' => $employee,
            'empresa' => $empresa,
            'courses' => $courses
        ]);
    }
}
