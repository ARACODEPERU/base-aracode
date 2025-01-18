<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DataTables;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;

class CrmContactsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $P000009;

    public function __construct()
    {
        $this->P000009 = Parameter::where('parameter_code', 'P000009')->value('value_default');
        // $this->P000009 si es 1 = cursos y capacitaciones
        // $this->P000009 si es 99 = todos
    }


    public function index()
    {

        return Inertia::render('CRM::Contacts/List', [
            'P000009' => $this->P000009
        ]);
    }

    public function getData()
    {
        $model = Person::query();
        $selectFields = ['people.*']; // Siempre incluir 'people.*'

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $model = $model->join('aca_students', 'aca_students.person_id', 'people.id');
            $selectFields[] = 'aca_students.new_student'; // Agregar 'new_student' dinámicamente
        }
        // Puedes agregar más condiciones similares aquí con otros joins y campos
        $model = $model->select($selectFields); // Aplicar el select con todos los campos acumulados
        $model = $model->where('people.id', '<>', 1);

        return DataTables::of($model)->toJson();
    }

    public function massMailing()
    {
        $courses = [];

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $courses = AcaCourse::where('status', true)->get();
        }

        return Inertia::render('CRM::Contacts/MassMailing', [
            'P000009' => $this->P000009,
            'courses' => $courses
        ]);
    }
}
