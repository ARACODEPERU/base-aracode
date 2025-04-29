<?php

namespace Modules\Academic\Http\Controllers;

use App\Helpers\Barios;
use App\Models\Parameter;
use App\Rules\AcaRegistrationExists;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaCertificateParameter;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Operations\CertificateImage;

class AcaCertificateController extends Controller
{
    use ValidatesRequests;

    public $directory;
    public $P000016;

    public function __construct()
    {
        $this->directory = 'academic' . DIRECTORY_SEPARATOR . 'certificates';
        $this->P000016 = Parameter::where('parameter_code', 'P000016')->value('value_default');
    }

    public function index()
    {
        $certificates = AcaCertificateParameter::paginate(10);

        // Formatear la fecha antes de devolver los datos
        $certificates->getCollection()->transform(function ($certificate) {
            $certificate->formatted_date = Carbon::parse($certificate->created_at)->format('d/m/Y');
            return $certificate;
        });

        return Inertia::render('Academic::Certificates/List', [
            'certificates' => $certificates
        ]);
    }

    public function create()
    {
        $courses = AcaCourse::where('status', true)->get();
        return Inertia::render('Academic::Certificates/Create', [
            'courses' => $courses
        ]);
    }

    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'name_certificate' => 'required',
                'certificate_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]
        );

        $destination = $this->directory;
        $file = $request->file('certificate_img');
        $path = null;

        if ($file) {
            $original_name = date('YmdHis');
            $extension = $file->getClientOriginalExtension();
            $file_name = $original_name . '.' . $extension;
            $path = $file->storeAs($destination, $file_name, 'public');
        }

        if ($request->get('course_id')) {
            $ce = AcaCertificateParameter::where('course_id', $request->get('course_id'));
            if ($ce) {
                $ce->update([
                    'state' => false
                ]);
            }
        } else {
            $ce = AcaCertificateParameter::whereNull('course_id');
            if ($ce) {
                $ce->update([
                    'state' => false
                ]);
            }
        }

        $certificate = AcaCertificateParameter::create([
            'course_id' => $request->get('course_id') ?? null,
            'certificate_img' => $path,
            'name_certificate' => $request->get('name_certificate'),
            'state' => true
        ]);

        return redirect()->route('aca_certificate_edit', $certificate->id);
    }

    public function edit($id)
    {
        $certificate = AcaCertificateParameter::find($id);
        return Inertia::render('Academic::Certificates/Edit', [
            'certificate' => $certificate
        ]);
    }

    public function studentCreate($id)
    {
        $student = AcaStudent::with('person')->where('id', $id)->first();
        $student_id = $student->id;

        // $studentSubscribed = AcaStudentSubscription::where('student_id', $student_id)
        //     ->where('status', true)
        //     ->first();

        // $courses = AcaCourse::with('registrations') // Para validar los cursos registrados
        //     ->get()
        //     ->map(function ($course) use ($studentSubscribed, $student_id) {
        //         // Verificar si el curso es gratuito
        //         $isFree = is_null($course->price) || $course->price == 0;

        //         // Verificar si el alumno está registrado en este curso
        //         $isRegistered = $course->registrations->contains('student_id', $student_id);

        //         // Verificar si el alumno tiene una suscripción activa
        //         $hasActiveSubscription = $studentSubscribed !== null;

        //         // Lógica para determinar si puede ver el curso
        //         if ($hasActiveSubscription) {
        //             // Si tiene suscripción activa, puede ver todos los cursos
        //             $course->can_view = true;
        //         } else {
        //             // Si no tiene suscripción activa, solo puede ver cursos gratuitos o en los que está matriculado
        //             $course->can_view = $isFree || $isRegistered;
        //         }

        //         return $course;
        //     });

        // // Filtrar los cursos para mostrar solo los que puede ver
        // $filteredCourses = $courses->filter(function ($course) {
        //     return $course->can_view;
        // });
        $studentSubscribed = AcaStudentSubscription::where('student_id', $student_id)
            ->where('status', true)
            ->first();
        //dd($studentSubscribed);
        $courses = AcaCourse::with('registrations') // Para validar los cursos registrados
            ->when(!$studentSubscribed, function ($query) use ($student_id) {
                // Si no tiene suscripción activa, filtrar cursos gratuitos o en los que está matriculado
                $query->where(function ($q) use ($student_id) {
                    $q->whereNull('price') // Cursos gratuitos (precio null)
                        ->orWhere('price', 0) // Cursos gratuitos (precio 0)
                        ->orWhereHas('registrations', function ($q2) use ($student_id) {
                            // Cursos en los que el alumno está matriculado
                            $q2->where('student_id', $student_id);
                        });
                });
            })->get();


        $certificates = AcaCertificate::with('course')
            ->where('student_id', $id)->get();


        return Inertia::render('Academic::Certificates/StudentCreate', [
            'student'   => $student,
            'courses'   => $courses,
            'certificates' => $certificates,
            'P000016' => $this->P000016
        ]);
    }


    public function studentStore(Request $request)
    {
        $student_id = $request->get('student_id');
        $course_id = $request->get('course_id');
        $certificate_auto = $request->get('certificate');
        if ($certificate_auto) {
            $this->validate(
                $request,
                [
                    'student_id'  => ['required', new AcaRegistrationExists($course_id)],
                    'course_id'   => 'required',
                ]
            );
        } else {
            $this->validate(
                $request,
                [
                    'student_id'  => ['required', new AcaRegistrationExists($course_id)],
                    'course_id'   => 'required',
                    'image'       => 'required'
                ]
            );
        }

        $registration = AcaCapRegistration::where('student_id', $student_id)
            ->where('course_id', $course_id)
            ->first();

        $registration->update([
            'certificate_date' => Carbon::now()->format('Y-m-d')
        ]);

        if ($this->P000016 == 1) {
            $certificate = AcaCertificate::firstOrCreate([
                'student_id'        => $student_id,
                'registration_id'   => $registration->id,
                'course_id'         => $course_id,
                'content'           => null,
                'image'             => $request->get('image')
            ]);
        } else {
            if ($certificate_auto) {
                $certificate = AcaCertificate::firstOrCreate([
                    'student_id'        => $student_id,
                    'registration_id'   => $registration->id,
                    'course_id'         => $course_id,
                    'content'           => null,
                    'image'             => null
                ]);
            } else {

                $certificate = AcaCertificate::firstOrCreate([
                    'student_id'        => $student_id,
                    'registration_id'   => $registration->id,
                    'course_id'         => $course_id,
                    'content'           => null
                ]);

                $destination = 'uploads/certificate';
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
                        $file_name = $student_id . 'X' . $course_id . '.' . $extension;
                        $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                        $certificate->image = $path;
                        $certificate->save();
                    }
                }
            }
        }



        return redirect()->route('aca_students_certificates_create', $student_id);
    }


    public function studentDestroy($id)
    {
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = AcaCertificate::findOrFail($id);
            $barios = new Barios();
            $barios->deleteFile($item->image);
            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Certificado eliminado correctamente';
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

    public function updateInfo(Request $request)
    {
        $certificate = new CertificateImage();
        $id = $request->get('id');

        $acaCertificate = AcaCertificateParameter::find($id);

        switch ($request->get('action_type')) {
            case 1:
                $acaCertificate->fontfamily_date = $request->get('fontfamily_date');
                $acaCertificate->font_size_date = $request->get('font_size_date');
                $acaCertificate->font_align_date = $request->get('font_align_date');
                $acaCertificate->font_vertical_align_date = $request->get('font_vertical_align_date');
                $acaCertificate->position_date_x = $request->get('position_date_x');
                $acaCertificate->position_date_y = $request->get('position_date_y');
                $acaCertificate->color_date = $request->get('color_date');
                $acaCertificate->visible_date = $request->get('visible_date');
                break;
            case 2:
                $acaCertificate->fontfamily_names = $request->get('fontfamily_names');
                $acaCertificate->font_align_names = $request->get('font_align_names');
                $acaCertificate->font_vertical_align_names = $request->get('font_vertical_align_names');
                $acaCertificate->position_names_x = $request->get('position_names_x');
                $acaCertificate->position_names_y = $request->get('position_names_y');
                $acaCertificate->font_size_names = $request->get('font_size_names');
                $acaCertificate->color_names = $request->get('color_names');
                $acaCertificate->visible_names = $request->get('visible_names');
                break;
            case 3:
                $acaCertificate->fontfamily_title = $request->get('fontfamily_title');
                $acaCertificate->font_align_title = $request->get('font_align_title');
                $acaCertificate->font_vertical_align_title = $request->get('font_vertical_align_title');
                $acaCertificate->position_title_x = $request->get('position_title_x');
                $acaCertificate->position_title_y = $request->get('position_title_y');
                $acaCertificate->font_size_title = $request->get('font_size_title');
                $acaCertificate->max_width_title = $request->get('max_width_title');
                $acaCertificate->color_title = $request->get('color_title');
                $acaCertificate->visible_title = $request->get('visible_title');
                break;
            case 4:
                $acaCertificate->position_qr_x = $request->get('position_qr_x');
                $acaCertificate->position_qr_y = $request->get('position_qr_y');
                $acaCertificate->size_qr = $request->get('size_qr');
                $acaCertificate->font_align_qr = $request->get('font_align_qr');
                $acaCertificate->visible_image_qr = $request->get('visible_image_qr');
                break;
            case 5:
                $acaCertificate->fontfamily_description = $request->get('fontfamily_description');
                $acaCertificate->font_align_description = $request->get('font_align_description');
                $acaCertificate->font_vertical_align_description = $request->get('font_vertical_align_description');
                $acaCertificate->position_description_x = $request->get('position_description_x');
                $acaCertificate->position_description_y = $request->get('position_description_y');
                $acaCertificate->font_size_description = $request->get('font_size_description');
                $acaCertificate->max_width_description = $request->get('max_width_description');
                $acaCertificate->interspace_description = $request->get('interspace_description') ?? null;
                $acaCertificate->color_description = $request->get('color_description');
                $acaCertificate->visible_description = $request->get('visible_description');
                break;
            default:
                if ($request->get('state')) {
                    if ($acaCertificate->course_id) {
                        AcaCertificateParameter::where('course_id', $request->get('course_id'))->update([
                            'state' => false
                        ]);
                    } else {
                        AcaCertificateParameter::whereNull('course_id')->update([
                            'state' => false
                        ]);
                    }
                }
                $acaCertificate->state = $request->get('state') ? true : false;
                break;
        }

        $acaCertificate->save();
        $fullPath = null;

        if ($request->get('action_type') <> 99) {
            $imagen = $certificate->generate($id);

            $carpeta = $this->directory . DIRECTORY_SEPARATOR . 'parameters';
            //dd(public_path($carpeta));
            $barios = new Barios();
            $barios->deleteFilesSubfoldersPath($carpeta);
            // Guardar la imagen en el sistema de archivos
            $path = $carpeta .  DIRECTORY_SEPARATOR . date('YmdHis') . '.png'; // Ruta relativa dentro del disco 'public'
            Storage::disk('public')->put($path, $imagen);

            // Obtener la ruta completa del archivo guardado
            $fullPath = Storage::disk('public')->url($path);
            $acaCertificate->certificate_img_finished = $path;
            $acaCertificate->save();
        }
        return response()->json([
            'success' => true,
            'image' => $fullPath
        ]);
    }

    public function storeMassive(Request $request, $id)
    {
        $students = $request->get('students');

        foreach ($students as $student) {
            $student_id = $student['student']['id'];
            if ($student['checkbox']) {

                $registration = AcaCapRegistration::where('student_id', $student_id)
                    ->where('course_id', $id)
                    ->whereNull('certificate_date')
                    ->first();

                if ($registration) {
                    $registration->update([
                        'certificate_date' => Carbon::now()->format('Y-m-d')
                    ]);

                    AcaCertificate::firstOrCreate(
                        [
                            'student_id'        => $student_id,
                            'registration_id'   => $registration->id,
                            'course_id'         => $id,
                        ],
                        [
                            'content'           => null,
                            'image'             => null
                        ]
                    );
                }
            }
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function generateCertificateStudent($id)
    {
        $xcer = AcaCertificate::find($id);

        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();

        $student_id = $student->id;
        $course_id = $xcer->course_id;

        $autoCertificate = new CertificateImage();

        $certificateParameter = AcaCertificateParameter::where('course_id', $course_id)
            ->where('state', true)
            ->first();

        $certificate_id = null;

        if ($certificateParameter) {
            $certificate_id = $certificateParameter->id;
        } else {
            $certificateParameter = AcaCertificateParameter::whereNull('course_id')
                ->where('state', true)
                ->first();

            $certificate_id = $certificateParameter->id;
        }
        if ($certificate_id) {
            $imagen = $autoCertificate->generate($certificate_id, $student_id, $course_id);
            if ($imagen) {
                // Configura la respuesta para descargar la imagen sin guardarla
                return response()->streamDownload(function () use ($imagen) {
                    echo $imagen; // Devuelve el contenido de la imagen directamente
                }, "certificado_{$student_id}_{$course_id}.png", [
                    'Content-Type' => 'image/png',
                ]);
            }
        }
    }
}
