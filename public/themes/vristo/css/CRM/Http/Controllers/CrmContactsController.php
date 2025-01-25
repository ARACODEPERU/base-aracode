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
use Modules\Academic\Entities\AcaStudent;
use Illuminate\Support\Facades\Mail;
use Modules\CRM\Emails\MailwithUserAccount;

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

    public function getContactsPagination(Request $request)
    {
        $search = $request->get('search');



        $model = Person::query();
        $selectFields = ['people.*']; // Siempre incluir 'people.*'

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $model = $model->join('aca_students', 'aca_students.person_id', 'people.id');

            if (is_array($search['type'])) {
                $ty = $search['type'][0];

                if ($ty == 'new') {
                    $model = $model->where('aca_students.new_student', true);
                } elseif ($ty == 'cur') {

                    $cu = $search['type'][1];

                    $model = $model->join('aca_cap_registrations', 'aca_students.id', 'aca_cap_registrations.student_id');

                    $model = $model->where('aca_cap_registrations.course_id', $cu);
                }
            }


            $selectFields[] = 'aca_students.new_student'; // Agregar 'new_student' dinámicamente
        }
        // Puedes agregar más condiciones similares aquí con otros joins y campos
        $model = $model->select($selectFields); // Aplicar el select con todos los campos acumulados
        $model = $model->where('people.id', '<>', 1);

        return $model->paginate(10);
    }

    public function sendMassMessage(Request $request)
    {
        $correo = $request->get('correo');

        $P000013 = Parameter::where('parameter_code', 'P000013')->value('value_default');

        $type = $correo['type'];

        $correosEnviados = 0;
        $correosFallidos = [];

        $data = [
            'from_mail' => $P000013 ?? env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME'),
            'title' => $correo['title'],
            'contact' => $correo['contact']
        ];

        //dd($correo);

        try {
            Mail::to($correo['contact']['email'])->send(new MailwithUserAccount($data));
            $correosEnviados = 1;
        } catch (\Exception $e) {

            $correosFallidos = [
                'email' => $correo['contact']['email'],
                'error' => $e->getMessage() // Guarda el mensaje de error
            ];
        }

        // Devuelve la respuesta con totales y detalles de errores
        return response()->json([
            'success' => count($correosFallidos) === 0,
            'enviados' => $correosEnviados,
            'fallidos' => $correosFallidos
        ]);
    }
}
