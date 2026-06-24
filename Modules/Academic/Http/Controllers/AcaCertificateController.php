<?php

namespace Modules\Academic\Http\Controllers;

use App\Helpers\Barios;
use App\Models\Parameter;
use App\Models\Person;
use App\Rules\AcaRegistrationExists;
use Carbon\Carbon;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Intervention\Image\Facades\Image;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaCertificateGradeConfig;
use Modules\Academic\Entities\AcaCertificateModuleConfig;
use Modules\Academic\Entities\AcaCertificateParameter;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentExam;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Operations\CertificateImage;

class AcaCertificateController extends Controller
{
    use ValidatesRequests;

    public $directory;

    public $P000016;

    public function __construct()
    {
        $this->directory = 'academic'.DIRECTORY_SEPARATOR.'certificates';
        $this->P000016 = Parameter::where('parameter_code', 'P000016')->value('value_default');
    }

    public function test()
    {
        $certificates = AcaCertificateParameter::with(['course'])
        ->where('id', 8)->get();

        return Inertia::render('Academic::Certificates/Test', [
            'certificates' => $certificates,
            'course' => $certificates->first()->course,
        ]);
    }
    public function test2()
    {
        $certificates = AcaCertificateParameter::with(['course'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Formatear la fecha antes de devolver los datos
        $certificates->getCollection()->transform(function ($certificate) {
            $certificate->formatted_date = Carbon::parse($certificate->created_at)->format('d/m/Y');

            return $certificate;
        });

        return Inertia::render('Academic::Certificates/Test2', [
            'certificates' => $certificates,
        ]);
    }
    public function test3()
    {
        $certificates = AcaCertificateParameter::with(['course'])
            ->where('id', 8)->get();

        // Formatear la fecha antes de devolver los datos
        //$certificates->getCollection()->transform(function ($certificate) {
          //  $certificate->formatted_date = Carbon::parse($certificate->created_at)->format('d/m/Y');



        return Inertia::render('Academic::Certificates/Test3', [
            'certificates' => $certificates,
        ]);
    }
    public function index()
    {
        $certificates = AcaCertificateParameter::with(['course'])
            ->leftJoin('aca_courses', 'aca_certificates_parameters.course_id', '=', 'aca_courses.id')
            ->select('aca_certificates_parameters.*')
            ->orderByRaw("COALESCE(aca_courses.description, 'General') asc")
            ->orderBy('aca_certificates_parameters.course_id')
            ->orderByDesc('aca_certificates_parameters.state')
            ->orderBy('aca_certificates_parameters.for_module')
            ->orderBy('aca_certificates_parameters.created_at', 'desc')
            ->paginate(10);

        // Formatear la fecha antes de devolver los datos
        $certificates->getCollection()->transform(function ($certificate) {
            $certificate->formatted_date = Carbon::parse($certificate->created_at)->format('d/m/Y');

            return $certificate;
        });

        return Inertia::render('Academic::Certificates/List', [
            'certificates' => $certificates,
        ]);
    }

    public function create()
    {
        $courses = AcaCourse::where('status', true)->get();

        return Inertia::render('Academic::Certificates/Create', [
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $hasReverse = $request->get('has_reverse') ? true : false;

        $this->validate(
            $request,
            [
                'name_certificate' => 'required',
                'certificate_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'back_certificate_img' => $hasReverse ? 'required|image|mimes:jpeg,png,jpg,gif|max:5120' : 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]
        );

        $destination = $this->directory;
        $file = $request->file('certificate_img');
        $path = null;
        $imgWidth = null;
        $imgHeight = null;

        if ($file) {
            $original_name = date('YmdHis');
            $extension = $file->getClientOriginalExtension();
            $file_name = $original_name.'.'.$extension;

            $img = Image::make($file);
            $maxSize = 1550;
            $img->resize($maxSize, $maxSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $fullPath = public_path('storage'.DIRECTORY_SEPARATOR.$destination.DIRECTORY_SEPARATOR.$file_name);
            $img->save($fullPath, 85);

            $path = $destination.'/'.$file_name;
            $imgWidth = $img->width();
            $imgHeight = $img->height();
        }

        $backPath = null;
        $backImgWidth = null;
        $backImgHeight = null;
        $backFile = $request->file('back_certificate_img');
        if ($backFile) {
            $original_name = date('YmdHis').'_back';
            $extension = $backFile->getClientOriginalExtension();
            $file_name = $original_name.'.'.$extension;

            $img = Image::make($backFile);
            $maxSize = 1550;
            $img->resize($maxSize, $maxSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $fullPath = public_path('storage'.DIRECTORY_SEPARATOR.$destination.DIRECTORY_SEPARATOR.$file_name);
            $img->save($fullPath, 85);

            $backPath = $destination.'/'.$file_name;
            $backImgWidth = $img->width();
            $backImgHeight = $img->height();
        }
        //dd($request->all());
        if ($request->get('course_id')) {
            $ce = AcaCertificateParameter::where('course_id', $request->get('course_id'))
                ->where('for_module', $request->get('for_module') ? true : false);

            if ($ce) {
                $ce->update([
                    'state' => false,
                ]);
            }
        } else {
            $ce = AcaCertificateParameter::whereNull('course_id')
                ->where('for_module', $request->get('for_module') ? true : false);
            if ($ce) {
                $ce->update([
                    'state' => false,
                ]);
            }
        }

        $layoutDefaults = $this->defaultCertificateLayout($imgWidth, $imgHeight, $backImgWidth, $backImgHeight);

        $certificate = AcaCertificateParameter::create(array_merge($layoutDefaults, [
            'course_id' => $request->get('course_id') ?? null,
            'certificate_img' => $path,
            'certificate_img_width' => $imgWidth,
            'certificate_img_height' => $imgHeight,
            'name_certificate' => $request->get('name_certificate'),
            'state' => true,
            'for_module' => $request->get('for_module') ? true : false,
            'back_certificate_img' => $backPath,
            'back_certificate_img_width' => $backImgWidth,
            'back_certificate_img_height' => $backImgHeight,
            'back_description' => $request->get('back_description'),
            'back_content_show_manual' => $request->get('back_content_show_manual') ? true : false,
            'back_content_show_course' => $request->get('back_content_show_course') ? true : false,
            'back_content_show_module' => $request->get('back_content_show_module') ? true : false,
            'back_content_type' => $request->get('back_content_type'),
            'back_content_type_module' => $request->get('back_content_type_module'),
            'has_reverse' => $request->get('has_reverse') ? true : false,
        ]));

        if ($certificate->for_module) {
            AcaCertificateModuleConfig::create([
                'certificate_id' => $certificate->id,
                'fontfamily_module_description' => 'Poppins-Light.ttf',
                'font_align_module_description' => 'center',
                'font_vertical_module_description' => 'top',
                'position_module_description_x' => (int) round(($imgWidth ?: 1550) / 2),
                'position_module_description_y' => (int) round(($imgHeight ?: 1096) * 0.56),
                'font_size_module_description' => 22,
                'max_width_module_description' => (int) round(($imgWidth ?: 1550) * 0.70),
                'text_align_module_description' => 'center',
                'color_module_description' => '#000000',
                'visible_module_description' => true,
            ]);
        }

        return redirect()->route('aca_certificate_edit', $certificate->id);
    }

    private function defaultCertificateLayout(?int $width, ?int $height, ?int $backWidth = null, ?int $backHeight = null): array
    {
        $width = $width ?: 1550;
        $height = $height ?: 1096;
        $backWidth = $backWidth ?: $width;
        $backHeight = $backHeight ?: $height;

        $centerX = (int) round($width / 2);
        $backCenterX = (int) round($backWidth / 2);
        $frontQrSize = 150;
        $backQrSize = 150;

        return [
            'fontfamily_date' => 'Poppins-Light.ttf',
            'font_align_date' => 'center',
            'font_vertical_align_date' => 'top',
            'position_date_x' => $centerX,
            'position_date_y' => (int) round($height * 0.18),
            'font_size_date' => 28,
            'color_date' => '#000000',
            'visible_date' => true,
            'fontfamily_names' => 'Poppins-Light.ttf',
            'font_align_names' => 'center',
            'font_vertical_align_names' => 'top',
            'position_names_x' => $centerX,
            'position_names_y' => (int) round($height * 0.30),
            'font_size_names' => 42,
            'color_names' => '#000000',
            'visible_names' => true,
            'fontfamily_title' => 'Poppins-Light.ttf',
            'font_align_title' => 'center',
            'font_vertical_align_title' => 'top',
            'position_title_x' => $centerX,
            'position_title_y' => (int) round($height * 0.42),
            'font_size_title' => 32,
            'max_width_title' => (int) round($width * 0.68),
            'color_title' => '#000000',
            'visible_title' => true,
            'position_qr_x' => 0,
            'position_qr_y' => (int) round($height * 0.72),
            'size_qr' => $frontQrSize,
            'font_align_qr' => 'top-center',
            'visible_image_qr' => true,
            'fontfamily_description' => 'Poppins-Light.ttf',
            'font_align_description' => 'center',
            'font_vertical_align_description' => 'top',
            'position_description_x' => $centerX,
            'position_description_y' => (int) round($height * 0.56),
            'font_size_description' => 22,
            'max_width_description' => (int) round($width * 0.70),
            'text_align_description' => 'center',
            'content_type' => 'description',
            'color_description' => '#000000',
            'visible_description' => true,
            'interspace_description' => 6,
            'back_fontfamily_date' => 'Poppins-Light.ttf',
            'back_font_align_date' => 'center',
            'back_font_vertical_align_date' => 'top',
            'back_position_date_x' => $backCenterX,
            'back_position_date_y' => (int) round($backHeight * 0.16),
            'back_font_size_date' => 24,
            'back_color_date' => '#000000',
            'back_visible_date' => true,
            'back_fontfamily_names' => 'Poppins-Light.ttf',
            'back_font_align_names' => 'center',
            'back_font_vertical_align_names' => 'top',
            'back_position_names_x' => $backCenterX,
            'back_position_names_y' => (int) round($backHeight * 0.28),
            'back_font_size_names' => 34,
            'back_color_names' => '#000000',
            'back_visible_names' => true,
            'back_fontfamily_title' => 'Poppins-Light.ttf',
            'back_font_align_title' => 'center',
            'back_font_vertical_align_title' => 'top',
            'back_position_title_x' => $backCenterX,
            'back_position_title_y' => (int) round($backHeight * 0.40),
            'back_font_size_title' => 28,
            'back_max_width_title' => (int) round($backWidth * 0.68),
            'back_color_title' => '#000000',
            'back_visible_title' => true,
            'back_fontfamily_description' => 'Poppins-Light.ttf',
            'back_font_align_description' => 'center',
            'back_font_vertical_align_description' => 'top',
            'back_position_description_x' => $backCenterX,
            'back_position_description_y' => (int) round($backHeight * 0.52),
            'back_font_size_description' => 20,
            'back_max_width_description' => (int) round($backWidth * 0.70),
            'back_text_align_description' => 'center',
            'back_color_description' => '#000000',
            'back_visible_description' => true,
            'back_position_qr_x' => (int) round(($backWidth - $backQrSize) / 2),
            'back_position_qr_y' => (int) round($backHeight * 0.72),
            'back_size_qr' => $backQrSize,
            'back_font_align_qr' => 'top-left',
            'back_visible_qr' => true,
        ];
    }

    public function edit($id)
    {
        $certificate = AcaCertificateParameter::with(['moduleConfig'])->find($id);

        $gradeConfig = AcaCertificateGradeConfig::where('certificate_id', $certificate->id)->first();

        return Inertia::render('Academic::Certificates/Edit', [
            'certificate' => $certificate,
            'gradeConfig' => $gradeConfig,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña es incorrecta.',
            ], 422);
        }

        $certificate = AcaCertificateParameter::findOrFail($id);

        foreach ([
            $certificate->certificate_img,
            $certificate->back_certificate_img,
            $certificate->certificate_img_finished,
            $certificate->back_certificate_img_finished,
        ] as $path) {
            if ($path) {
                Storage::disk('public')->delete(ltrim(str_replace('\\', '/', $path), '/'));
            }
        }

        $certificate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Certificado eliminado correctamente.',
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
        // dd($studentSubscribed);
        $courses = AcaCourse::with('registrations') // Para validar los cursos registrados
            ->when(! $studentSubscribed, function ($query) use ($student_id) {
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
            'student' => $student,
            'courses' => $courses,
            'certificates' => $certificates,
            'P000016' => $this->P000016,
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
                    'student_id' => ['required', new AcaRegistrationExists($course_id)],
                    'course_id' => 'required',
                ]
            );
        } else {
            $this->validate(
                $request,
                [
                    'student_id' => ['required', new AcaRegistrationExists($course_id)],
                    'course_id' => 'required',
                    'image' => 'required',
                ]
            );
        }

        $registration = AcaCapRegistration::where('student_id', $student_id)
            ->where('course_id', $course_id)
            ->first();

        $registration->update([
            'certificate_date' => Carbon::now()->format('Y-m-d'),
        ]);

        if ($this->P000016 == 1) {
            $certificate = AcaCertificate::firstOrCreate([
                'student_id' => $student_id,
                'registration_id' => $registration->id,
                'course_id' => $course_id,
                'content' => null,
                'image' => $request->get('image'),
            ]);
        } else {
            if ($certificate_auto) {
                $certificate = AcaCertificate::firstOrCreate([
                    'student_id' => $student_id,
                    'registration_id' => $registration->id,
                    'course_id' => $course_id,
                    'content' => null,
                    'image' => null,
                ]);
            } else {

                $certificate = AcaCertificate::firstOrCreate([
                    'student_id' => $student_id,
                    'registration_id' => $registration->id,
                    'course_id' => $course_id,
                    'content' => null,
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

                    $name = uniqid('', true).'.'.str_replace('image/', '', $mime);
                    $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

                    if ($file) {
                        // $original_name = strtolower(trim($file->getClientOriginalName()));
                        // $file_name = time() . rand(100, 999) . $original_name;
                        $original_name = strtolower(trim($file->getClientOriginalName()));
                        $original_name = str_replace(' ', '_', $original_name);
                        $extension = $file->getClientOriginalExtension();
                        $file_name = $student_id.'X'.$course_id.'.'.$extension;
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
            $barios = new Barios;
            $barios->deleteFile($item->image);
            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message = 'Certificado eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function updateInfo(Request $request)
    {
        $certificate = new CertificateImage;
        $id = $request->get('id');

        $acaCertificate = AcaCertificateParameter::find($id);

        if (! $acaCertificate) {
            return response()->json(['error' => 'Certificado no encontrado con ID: '.$id], 404);
        }

        // dd($request->all());
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
                $acaCertificate->text_align_description = $request->get('text_align_description');
                $acaCertificate->content_type = $request->get('content_type');
                $acaCertificate->interspace_description = $request->get('interspace_description') ?? null;
                $acaCertificate->color_description = $request->get('color_description');
                $acaCertificate->visible_description = $request->get('visible_description');
                break;
                // Cases para el reverso
            case 6:
                // Configuración de fecha del reverso
                $acaCertificate->back_fontfamily_date = $request->get('back_fontfamily_date');
                $acaCertificate->back_font_align_date = $request->get('back_font_align_date');
                $acaCertificate->back_font_vertical_align_date = $request->get('back_font_vertical_align_date');
                $acaCertificate->back_position_date_x = $request->get('back_position_date_x');
                $acaCertificate->back_position_date_y = $request->get('back_position_date_y');
                $acaCertificate->back_font_size_date = $request->get('back_font_size_date');
                $acaCertificate->back_color_date = $request->get('back_color_date');
                $acaCertificate->back_visible_date = $request->get('back_visible_date');
                break;
            case 7:
                // Configuración de nombres del reverso
                $acaCertificate->back_fontfamily_names = $request->get('back_fontfamily_names');
                $acaCertificate->back_font_align_names = $request->get('back_font_align_names');
                $acaCertificate->back_font_vertical_align_names = $request->get('back_font_vertical_align_names');
                $acaCertificate->back_position_names_x = $request->get('back_position_names_x');
                $acaCertificate->back_position_names_y = $request->get('back_position_names_y');
                $acaCertificate->back_font_size_names = $request->get('back_font_size_names');
                $acaCertificate->back_color_names = $request->get('back_color_names');
                $acaCertificate->back_visible_names = $request->get('back_visible_names');
                break;
            case 8:
                // Configuración de título del reverso
                $acaCertificate->back_fontfamily_title = $request->get('back_fontfamily_title');
                $acaCertificate->back_font_align_title = $request->get('back_font_align_title');
                $acaCertificate->back_font_vertical_align_title = $request->get('back_font_vertical_align_title');
                $acaCertificate->back_position_title_x = $request->get('back_position_title_x');
                $acaCertificate->back_position_title_y = $request->get('back_position_title_y');
                $acaCertificate->back_font_size_title = $request->get('back_font_size_title');
                $acaCertificate->back_max_width_title = $request->get('back_max_width_title');
                $acaCertificate->back_color_title = $request->get('back_color_title');
                $acaCertificate->back_visible_title = $request->get('back_visible_title');
                break;
            case 9:
                // Configuración de descripción del reverso
                $acaCertificate->back_description = $request->get('back_description');
                $acaCertificate->back_fontfamily_description = $request->get('back_fontfamily_description');
                $acaCertificate->back_font_align_description = $request->get('back_font_align_description');
                $acaCertificate->back_font_vertical_align_description = $request->get('back_font_vertical_align_description');
                $acaCertificate->back_position_description_x = $request->get('back_position_description_x');
                $acaCertificate->back_position_description_y = $request->get('back_position_description_y');
                $acaCertificate->back_font_size_description = $request->get('back_font_size_description');
                $acaCertificate->back_max_width_description = $request->get('back_max_width_description');
                $acaCertificate->back_text_align_description = $request->get('back_text_align_description');
                $acaCertificate->back_color_description = $request->get('back_color_description');
                $acaCertificate->back_visible_description = $request->get('back_visible_description');
                break;
            case 10:
                // Contenido del reverso y campos generales
                $acaCertificate->back_description = $request->get('back_description');
                $acaCertificate->back_content_show_manual = $request->get('back_content_show_manual') ? true : false;
                $acaCertificate->back_content_show_course = $request->get('back_content_show_course') ? true : false;
                $acaCertificate->back_content_show_module = $request->get('back_content_show_module') ? true : false;
                $acaCertificate->for_module = $request->get('for_module') ? true : false;
                break;
            case 11:
                // Configuración de descripción del módulo (solo para certificados de módulo - for_module = true)
                // Guardar en tabla separada: aca_certificates_module_config
                AcaCertificateModuleConfig::updateOrCreate(
                    ['certificate_id' => $id],
                    [
                        'certificate_id' => $id,
                        'fontfamily_module_description' => $request->get('fontfamily_module_description'),
                        'font_align_module_description' => $request->get('font_align_module_description'),
                        'font_vertical_module_description' => $request->get('font_vertical_module_description'),
                        'position_module_description_x' => $request->get('position_module_description_x'),
                        'position_module_description_y' => $request->get('position_module_description_y'),
                        'font_size_module_description' => $request->get('font_size_module_description'),
                        'max_width_module_description' => $request->get('max_width_module_description'),
                        'text_align_module_description' => $request->get('text_align_module_description'),
                        'color_module_description' => $request->get('color_module_description'),
                        'visible_module_description' => $request->get('visible_module_description') ? true : false,
                    ]
                );
                break;
            case 12:
                // Contenido del curso del reverso
                $acaCertificate->back_fontfamily_course = $request->get('back_fontfamily_course');
                $acaCertificate->back_font_align_course = $request->get('back_font_align_course');
                $acaCertificate->back_font_vertical_align_course = $request->get('back_font_vertical_align_course');
                $acaCertificate->back_position_course_x = $request->get('back_position_course_x');
                $acaCertificate->back_position_course_y = $request->get('back_position_course_y');
                $acaCertificate->back_font_size_course = $request->get('back_font_size_course');
                $acaCertificate->back_max_width_course = $request->get('back_max_width_course');
                $acaCertificate->back_color_course = $request->get('back_color_course');
                $acaCertificate->back_visible_course = $request->get('back_visible_course') ? true : false;
                $acaCertificate->back_content_type = $request->get('back_content_type');

                // Guardar opciones de contenido en grade_config
                $grade_id = $request->get('grade_id');
                $gradeConfig = AcaCertificateGradeConfig::updateOrCreate(
                    ['id' => $grade_id],
                    [
                        'certificate_id' => $id,
                        'back_show_exam_grade' => $request->get('back_show_exam_grade') ? true : false,
                        'back_show_themes' => $request->get('back_show_themes') ? true : false,
                        'back_exam_fontfamily' => $request->get('back_exam_fontfamily'),
                        'back_exam_font_size' => $request->get('back_exam_font_size'),
                        'back_exam_color' => $request->get('back_exam_color') ?? '#000000',
                    ]
                );
                break;
            case 13:
                // Contenido del módulo del reverso
                $acaCertificate->back_fontfamily_module = $request->get('back_fontfamily_module');
                $acaCertificate->back_font_align_module = $request->get('back_font_align_module');
                $acaCertificate->back_font_vertical_align_module = $request->get('back_font_vertical_align_module');
                $acaCertificate->back_position_module_x = $request->get('back_position_module_x');
                $acaCertificate->back_position_module_y = $request->get('back_position_module_y');
                $acaCertificate->back_font_size_module = $request->get('back_font_size_module');
                $acaCertificate->back_max_width_module = $request->get('back_max_width_module');
                $acaCertificate->back_color_module = $request->get('back_color_module');
                $acaCertificate->back_visible_module = $request->get('back_visible_module') ? true : false;
                $acaCertificate->back_content_type_module = $request->get('back_content_type_module');
                break;
            case 14:
                // QR del reverso
                // dd($request->get('back_size_qr'));
                $acaCertificate->back_size_qr = $request->get('back_size_qr');
                $acaCertificate->back_font_align_qr = $request->get('back_font_align_qr');
                $acaCertificate->back_position_qr_x = $request->get('back_position_qr_x');
                $acaCertificate->back_position_qr_y = $request->get('back_position_qr_y');
                $acaCertificate->back_visible_qr = $request->get('back_visible_qr') ? true : false;
                break;
            case 15:
                // Nota Final (PROMEDIO FINAL) - guardar en tabla relacionada
                $grade_id = $request->get('grade_id');
                $gradeConfig = AcaCertificateGradeConfig::updateOrCreate(
                    ['id' => $grade_id],
                    [
                        'certificate_id' => $id,
                        'back_fontfamily_grade' => $request->get('back_fontfamily_grade'),
                        'back_font_size_grade' => $request->get('back_font_size_grade'),
                        'back_color_grade' => $request->get('back_color_grade'),
                        'back_position_grade_x' => $request->get('back_position_grade_x'),
                        'back_position_grade_y' => $request->get('back_position_grade_y'),
                        'back_visible_grade' => $request->get('back_visible_grade') ? true : false,
                        'back_rectangle_width' => $request->get('back_rectangle_width') ?? 100,
                        'back_rectangle_height' => $request->get('back_rectangle_height') ?? 100,
                        'back_rectangle_color' => $request->get('back_rectangle_color') ?? '#000000',
                    ]
                );
                break;
            case 16:
                // Opciones de contenido del curso (exam grade y themes)
                $grade_id = $request->get('grade_id');
                $gradeConfig = AcaCertificateGradeConfig::updateOrCreate(
                    ['id' => $grade_id],
                    [
                        'certificate_id' => $id,
                        'back_show_exam_grade' => $request->get('back_show_exam_grade') ? true : false,
                        'back_show_themes' => $request->get('back_show_themes') ? true : false,
                    ]
                );
                break;
            case 17:
                // Configuración de fuente para nota de examen
                $grade_id = $request->get('grade_id');
                $gradeConfig = AcaCertificateGradeConfig::updateOrCreate(
                    ['id' => $grade_id],
                    [
                        'certificate_id' => $id,
                        'back_exam_fontfamily' => $request->get('back_exam_fontfamily'),
                        'back_exam_font_size' => $request->get('back_exam_font_size'),
                        'back_exam_color' => $request->get('back_exam_color') ?? '#000000',
                    ]
                );
                break;
            case 98:
                // Manejo de imagen del reverso
                if ($request->hasFile('back_certificate_img')) {
                    $destination = $this->directory;
                    $file = $request->file('back_certificate_img');
                    $original_name = date('YmdHis').'_back';
                    $extension = $file->getClientOriginalExtension();
                    $file_name = $original_name.'.'.$extension;
                    $backPath = $file->storeAs($destination, $file_name, 'public');
                    $acaCertificate->back_certificate_img = $backPath;
                }
                break;
            default:
                if ($request->get('state')) {
                    if ($acaCertificate->course_id) {
                        AcaCertificateParameter::where('course_id', $request->get('course_id'))->update([
                            'state' => false,
                        ]);
                    } else {
                        AcaCertificateParameter::whereNull('course_id')->update([
                            'state' => false,
                        ]);
                    }
                }
                $acaCertificate->state = $request->get('state') ? true : false;
                $acaCertificate->for_module = $request->get('for_module') ? true : false;
                $acaCertificate->has_reverse = $request->get('has_reverse') ? true : false;
                break;
        }

        $acaCertificate->save();

        $fullPath = null;

        if ($request->get('render_preview_client')) {
            return response()->json([
                'success' => true,
                'image' => null,
            ]);
        }

        // Generar imagen para anverso (cases 1-5, 11) y reverso (cases 6-9, 12, 13, 14, 15)
        $generateForFront = in_array($request->get('action_type'), [1, 2, 3, 4, 5, 11]);
        $generateForBack = in_array($request->get('action_type'), [6, 7, 8, 9, 12, 13, 14, 15]);

        if ($generateForFront) {
            $imagen = $certificate->generate($id, 'front');

            $carpeta = $this->directory.DIRECTORY_SEPARATOR.'parameters/forward';
            $barios = new Barios;
            $barios->deleteFilesSubfoldersPath($carpeta);
            $path = $carpeta.DIRECTORY_SEPARATOR.date('YmdHis').'.png';
            Storage::disk('public')->put($path, $imagen);

            $fullPath = Storage::disk('public')->url($path);
            $acaCertificate->certificate_img_finished = $path;
            $acaCertificate->save();
        } elseif ($generateForBack) {

            $imagen = $certificate->generate($id, 'back');

            $carpeta = $this->directory.DIRECTORY_SEPARATOR.'parameters/back';
            // NO eliminamos archivos existentes para no borrar la imagen del anverso
            $path = $carpeta.DIRECTORY_SEPARATOR.date('YmdHis').'_back.png';
            Storage::disk('public')->put($path, $imagen);

            $fullPath = Storage::disk('public')->url($path);
            $acaCertificate->back_certificate_img_finished = $path;
            $acaCertificate->save();
        }

        return response()->json([
            'success' => true,
            'image' => $fullPath,
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
                        'certificate_date' => Carbon::now()->format('Y-m-d'),
                    ]);

                    AcaCertificate::firstOrCreate(
                        [
                            'student_id' => $student_id,
                            'registration_id' => $registration->id,
                            'course_id' => $id,
                        ],
                        [
                            'content' => null,
                            'image' => null,
                        ]
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function generateCertificateStudent(Request $request, $id)
    {
        $xcer = AcaCertificate::find($id);

        if (! $xcer) {
            return response()->json(['message' => 'Certificado no encontrado.'], 404);
        }

        $student = AcaStudent::with('person')->where('id', $xcer->student_id)->first();

        if (! $student) {
            return response()->json(['message' => 'Estudiante no encontrado.'], 404);
        }

        $student_id = $student->id;
        $course_id = $xcer->course_id;

        $autoCertificate = new CertificateImage;

        $certificateParameter = $this->resolveCertificateParameter($course_id, false);

        $certificate_id = null;

        if ($certificateParameter) {
            $certificate_id = $certificateParameter->id;
        } else {
            return response()->json(['message' => 'Falta configuración del administrador.'], 404);
        }

        if ($request->boolean('preview')) {
            return response()->json($this->certificatePreviewPayload($xcer, $student, $certificateParameter));
        }

        if ($certificate_id) {
            // Generar certificado ANVERSO (front)
            $imagenFront = $autoCertificate->generate($certificate_id, 'front', $student_id, $course_id);

            // Generar certificado REVERSO (back) si has_reverse = true Y existe back_certificate_img
            $imagenBack = null;
            if ($certificateParameter && $certificateParameter->has_reverse && $certificateParameter->back_certificate_img) {
                $imagenBack = $autoCertificate->generate($certificate_id, 'back', $student_id, $course_id);
            }

            // Si existen ambos, descargar como ZIP
            if ($imagenFront && $imagenBack) {
                $zipFileName = "certificado_{$student_id}_{$course_id}.zip";
                $tempFile = tempnam(sys_get_temp_dir(), 'cert_');

                $zip = new \ZipArchive;
                if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    $zip->addFromString('certificado_frente.png', $imagenFront);
                    $zip->addFromString('certificado_reverso.png', $imagenBack);
                    $zip->close();
                }

                return response()->download($tempFile, $zipFileName, [
                    'Content-Type' => 'application/zip',
                ])->deleteFileAfterSend(true);
            }

            // Si solo existe anverso, descargar solo el anverso
            if ($imagenFront) {
                return response()->streamDownload(function () use ($imagenFront) {
                    echo $imagenFront;
                }, "certificado_{$student_id}_{$course_id}.png", [
                    'Content-Type' => 'image/png',
                ]);
            }
        }
    }

    /**
     * Descarga el certificado de un módulo específico
     *
     * @param  int  $module_id  ID del módulo
     * @return response Descarga la imagen del certificado
     */
    private function certificatePreviewPayload(AcaCertificate $certificate, AcaStudent $student, AcaCertificateParameter $parameter): array
    {
        $course = AcaCourse::with([
            'modules' => function ($query) {
                $query->orderBy('position');
            },
            'modules.themes' => function ($query) {
                $query->orderBy('position');
            },
        ])->find($certificate->course_id);

        $sides = [
            $this->certificateSidePayload($certificate, $student, $parameter, $course, 'front'),
        ];

        if ($parameter->has_reverse && $parameter->back_certificate_img) {
            $sides[] = $this->certificateSidePayload($certificate, $student, $parameter, $course, 'back');
        }

        return [
            'success' => true,
            'file_name' => 'certificado_'.$student->id.'_'.$certificate->course_id,
            'download_url' => route('aca_image_download', ['id' => $certificate->id]),
            'sides' => $sides,
        ];
    }

    private function resolveCertificateParameter(?int $courseId, bool $forModule = false): ?AcaCertificateParameter
    {
        $baseQuery = AcaCertificateParameter::where('for_module', $forModule);

        if ($courseId) {
            $certificate = (clone $baseQuery)
                ->where('course_id', $courseId)
                ->where('state', true)
                ->latest('id')
                ->first();

            if ($certificate) {
                return $certificate;
            }
        }

        $certificate = (clone $baseQuery)
            ->whereNull('course_id')
            ->where('state', true)
            ->latest('id')
            ->first();

        if ($certificate) {
            return $certificate;
        }

        return null;
    }

    private function certificateSidePayload(AcaCertificate $certificate, AcaStudent $student, AcaCertificateParameter $parameter, ?AcaCourse $course, string $side): array
    {
        $isBack = $side === 'back';
        $imagePath = $isBack ? $parameter->back_certificate_img : $parameter->certificate_img;
        $imageSize = $this->certificateImageSize($imagePath);
        $configuredWidth = (int) ($isBack ? $parameter->back_certificate_img_width : $parameter->certificate_img_width);
        $configuredHeight = (int) ($isBack ? $parameter->back_certificate_img_height : $parameter->certificate_img_height);
        $width = $configuredWidth > 0 ? $configuredWidth : (int) ($imageSize['width'] ?? 1550);
        $height = $configuredHeight > 0 ? $configuredHeight : (int) ($imageSize['height'] ?? 1096);

        return [
            'key' => $side,
            'label' => $isBack ? 'Reverso' : 'Anverso',
            'width' => $width,
            'height' => $height,
            'base_image' => $this->certificateStorageUrl($imagePath),
            'texts' => $this->certificateTextItems($certificate, $student, $parameter, $course, $side),
            'contents' => $this->certificateContentItems($parameter, $course, $side),
            'qr' => $this->certificateQrItem($certificate, $student, $parameter, $side),
        ];
    }

    private function certificateTextItems(AcaCertificate $certificate, AcaStudent $student, AcaCertificateParameter $parameter, ?AcaCourse $course, string $side): array
    {
        $texts = [];
        $studentName = $student->person->full_name ?? '';
        $courseTitle = $course->certificate_title ?? $course->description ?? 'Curso';

        $this->pushCertificateText($texts, $parameter, $side, 'date', 'Lima, '.$this->certificateDateText($certificate, $student));
        $this->pushCertificateText($texts, $parameter, $side, 'names', $studentName);
        $this->pushCertificateText($texts, $parameter, $side, 'title', $courseTitle, (int) ($this->certificateField($parameter, $side, 'max_width_title') ?? 800));

        if ($side === 'front') {
            if (($parameter->content_type ?? 'description') !== 'table') {
                $this->pushCertificateText($texts, $parameter, $side, 'description', $course->certificate_description ?? '', (int) ($parameter->max_width_description ?? 800), true);
            }
        } elseif ($parameter->back_content_show_manual) {
            $this->pushCertificateText($texts, $parameter, $side, 'description', $parameter->back_description ?? '', (int) ($parameter->back_max_width_description ?? 800), true);
        }

        return $texts;
    }

    private function pushCertificateText(array &$texts, AcaCertificateParameter $parameter, string $side, string $field, ?string $text, ?int $width = null, bool $multiline = false): void
    {
        if (! $text || ! $this->certificateField($parameter, $side, 'visible_'.$field)) {
            return;
        }

        $fontSize = (int) ($this->certificateField($parameter, $side, 'font_size_'.$field) ?? 18);

        $texts[] = [
            'id' => $side.'-'.$field,
            'text' => $text,
            'x' => (float) ($this->certificateField($parameter, $side, 'position_'.$field.'_x') ?? 0),
            'y' => (float) ($this->certificateField($parameter, $side, 'position_'.$field.'_y') ?? 0),
            'font_size' => $fontSize,
            'font_family' => $this->certificateFontFamily($this->certificateField($parameter, $side, 'fontfamily_'.$field)),
            'color' => $this->certificateField($parameter, $side, 'color_'.$field) ?? '#000000',
            'align' => $this->certificateField($parameter, $side, 'font_align_'.$field) ?? 'left',
            'vertical_align' => $this->certificateField($parameter, $side, 'font_vertical_align_'.$field) ?? 'top',
            'width' => $width,
            'line_height' => $multiline ? 1.25 : 1,
        ];
    }

    private function certificateContentItems(AcaCertificateParameter $parameter, ?AcaCourse $course, string $side): array
    {
        if (! $course) {
            return [];
        }

        $items = [];

        if ($side === 'front' && ($parameter->content_type ?? 'description') === 'table' && $parameter->visible_description) {
            $items[] = $this->courseTablePayload(
                'front-course-table',
                $course,
                (float) ($parameter->position_description_x ?? 0),
                (float) ($parameter->position_description_y ?? 0),
                (int) ($parameter->max_width_description ?? 800),
                (int) ($parameter->font_size_description ?? 14),
                $parameter->color_description ?? '#000000',
                $this->certificateFontFamily($parameter->fontfamily_description),
                'table'
            );
        }

        if ($side === 'back' && ! $parameter->for_module && $parameter->back_visible_course) {
            $items[] = $this->courseTablePayload(
                'back-course-content',
                $course,
                (float) ($parameter->back_position_course_x ?? 0),
                (float) ($parameter->back_position_course_y ?? 0),
                (int) ($parameter->back_max_width_course ?? 800),
                (int) ($parameter->back_font_size_course ?? 14),
                $parameter->back_color_course ?? '#000000',
                $this->certificateFontFamily($parameter->back_fontfamily_course),
                $parameter->back_content_type ?? 'list'
            );
        }

        return $items;
    }

    private function courseTablePayload(string $id, AcaCourse $course, float $x, float $y, int $width, int $fontSize, string $color, string $fontFamily, string $type): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'font_size' => $fontSize,
            'color' => $color,
            'font_family' => $fontFamily,
            'modules' => $course->modules->map(function ($module) {
                return [
                    'title' => $module->description ?? '',
                    'themes' => $module->themes->pluck('description')->filter()->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    private function certificateQrItem(AcaCertificate $certificate, AcaStudent $student, AcaCertificateParameter $parameter, string $side): ?array
    {
        $validationUrl = route('certificado_validar', [
            'dni' => $student?->person?->number ?: 0,
            'course_id' => $certificate->course_id ?: 0,
        ]);

        if ($side === 'front') {
            if (! $parameter->visible_image_qr || ! $parameter->size_qr) {
                return null;
            }

            return [
                'text' => $validationUrl,
                'x' => (float) ($parameter->position_qr_x ?? 0),
                'y' => (float) ($parameter->position_qr_y ?? 0),
                'size' => (float) ($parameter->size_qr ?? 120),
                'align' => $parameter->font_align_qr ?? 'top-left',
            ];
        }

        if (! $parameter->back_visible_qr || ! $parameter->back_size_qr) {
            return null;
        }

        return [
            'text' => $validationUrl,
            'x' => (float) ($parameter->back_position_qr_x ?? 0),
            'y' => (float) ($parameter->back_position_qr_y ?? 0),
            'size' => (float) ($parameter->back_size_qr ?? 120),
            'align' => 'top-left',
        ];
    }

    private function certificateImageSize(?string $path): array
    {
        if (! $path) {
            return [];
        }

        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $fullPath = public_path('storage'.DIRECTORY_SEPARATOR.$normalizedPath);

        if (! file_exists($fullPath)) {
            return [];
        }

        $size = getimagesize($fullPath);

        if (! $size) {
            return [];
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
        ];
    }

    private function certificateStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        return asset('storage/'.$normalizedPath);
    }

    private function certificateField(AcaCertificateParameter $parameter, string $side, string $field)
    {
        if ($side === 'back') {
            $backField = str_starts_with($field, 'back_') ? $field : 'back_'.$field;

            return $parameter->{$backField} ?? null;
        }

        return $parameter->{$field} ?? null;
    }

    private function certificateDateText(AcaCertificate $certificate, AcaStudent $student): string
    {
        $register = AcaCapRegistration::where('student_id', $student->id)
            ->where('course_id', $certificate->course_id)
            ->first();

        if ($register && $register->certificate_date) {
            return Carbon::parse($register->certificate_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        }

        return Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }

    private function certificateFontFamily(?string $font): string
    {
        if (! $font) {
            return 'Arial';
        }

        return str_replace(['-', '_'], ' ', str_replace(['.ttf', '.otf'], '', $font));
    }

    public function downloadModuleCertificate($module_id)
    {
        // 1. Obtener estudiante actual
        $user = Auth::user();
        if (! $user || ! $user->person_id) {
            return back()->with('error', 'No tienes acceso a esta funcionalidad');
        }

        $student = AcaStudent::where('person_id', $user->person_id)->first();

        // 2. Obtener el módulo con su curso
        $module = AcaModule::with('course')->find($module_id);

        if (! $module) {
            return back()->with('error', 'Módulo no encontrado');
        }

        // 2.1. Verificar que el módulo permite descarga de certificados
        if (! $module->allow_certificate_download) {
            return back()->with('error', 'Este módulo no permite la descarga de certificados');
        }

        // 3. Buscar examen del estudiante para este módulo
        $exam = AcaExam::where('module_id', $module_id)->first();

        if (! $exam) {
            return back()->with('error', 'No tienes examen para este módulo');
        }

        // 4. Verificar examen aprobado (nota >= 11 y estado completado)
        $studentExam = AcaStudentExam::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('punctuation', '>=', 11)
            ->whereIn('status', ['calificado', 'completado', 'revision_pendiente'])
            ->first();

        if (! $studentExam) {
            return back()->with('error', 'No tienes examen aprobado para descargar el certificado');
        }

        // 5. Buscar certificado: primero específico del curso, luego genérico
        $certificate = $this->findModuleCertificate($module->course_id);

        if (! $certificate) {
            return back()->with('error', 'No hay certificado configurado para módulos');
        }

        // 6. Generar certificados (anverso y reverso si está configurado)
        return $this->generateModuleCertificates($certificate, $student, $module);
    }

    /**
     * Busca el certificado para un módulo específico
     * Primero busca certificado específico del curso, luego genérico
     *
     * @param  int  $courseId  ID del curso
     * @return AcaCertificateParameter|null
     */
    private function findModuleCertificate($courseId)
    {
        // Primero: certificado específico del curso
        $certificate = AcaCertificateParameter::where('for_module', true)
            ->where('course_id', $courseId)
            ->where('state', true)
            ->first();

        // Si no existe, buscar genérico
        if (! $certificate) {
            $certificate = AcaCertificateParameter::where('for_module', true)
                ->whereNull('course_id')
                ->where('state', true)
                ->first();
        }

        return $certificate;
    }

    /**
     * Genera los certificados (anverso y reverso) para un módulo
     *
     * @param  AcaCertificateParameter  $certificate  Certificado encontrado
     * @param  mixed  $student  Datos del estudiante
     * @param  mixed  $module  Datos del módulo
     * @return response Descarga la imagen del certificado
     */
    private function generateModuleCertificates($certificate, $student, $module)
    {
        // Instanciar el generador de certificados
        $autoCertificate = new CertificateImage;

        // 1. Generar certificado ANVERSO (front)
        $imagenFront = $autoCertificate->generate(
            $certificate->id,     // certificate_id
            'front',              // tipo: anverso
            $student->id,         // student_id
            $module->course_id,   // course_id
            $module->id          // module_id (para datos reales del módulo)
        );

        // 2. Generar certificado REVERSO (back) si has_reverse = true Y existe back_certificate_img
        $imagenBack = null;
        if ($certificate->has_reverse && $certificate->back_certificate_img) {
            $imagenBack = $autoCertificate->generate(
                $certificate->id,
                'back',
                $student->id,
                $module->course_id,
                $module->id
            );
        }

        // Si existen ambos (has_reverse = true y back generado), descargar como ZIP
        if ($imagenFront && $imagenBack) {
            $zipFileName = "certificado_{$student->id}_{$module->id}.zip";
            $tempFile = tempnam(sys_get_temp_dir(), 'cert_');

            $zip = new \ZipArchive;
            if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $zip->addFromString('certificado_frente.png', $imagenFront);
                $zip->addFromString('certificado_reverso.png', $imagenBack);
                $zip->close();
            }

            return response()->download($tempFile, $zipFileName, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        }

        // Si solo existe anverso, descargar solo el anverso
        if ($imagenFront) {
            return response()->streamDownload(function () use ($imagenFront) {
                echo $imagenFront;
            }, "certificado_{$student->id}_{$module->id}.png", [
                'Content-Type' => 'image/png',
            ]);
        }

        return back()->with('error', 'Error al generar el certificado');
    }

    public function certificado_validar($dni = 0, $course_id = 0)
    {
        $person = '';
        $certificates = '';
        $course = '';
        if ($dni != 0) {
            $person = Person::where('number', $dni)->select('full_name', 'image', 'number')->first();
            if ($course_id == 0) {
                $certificates = DB::table('people')
                    ->join('aca_students', 'people.id', '=', 'aca_students.person_id')
                    ->join('aca_certificates', 'aca_students.id', '=', 'aca_certificates.student_id')
                    ->join('aca_courses', 'aca_certificates.course_id', '=', 'aca_courses.id')
                    ->where('people.number', $dni)
                    ->select('aca_certificates.created_at as fecha', 'aca_courses.description', 'aca_courses.id as course_id')
                    ->get();
            } else {
                $course = 1;
                $certificates = DB::table('people')
                    ->join('aca_students', 'people.id', '=', 'aca_students.person_id')
                    ->join('aca_certificates', 'aca_students.id', '=', 'aca_certificates.student_id')
                    ->join('aca_courses', 'aca_certificates.course_id', '=', 'aca_courses.id')
                    ->join('aca_brochures', 'aca_brochures.course_id', '=', 'aca_certificates.course_id')
                    ->where('people.number', $dni)->where('aca_certificates.course_id', $course_id)
                    ->select('aca_certificates.created_at as fecha', 'aca_courses.description', 'aca_brochures.curriculum_plan', 'aca_courses.id as course_id')
                    ->get();
            }

        }

        return view('academic::certificado_validar.certificado_validar', [
            'person' => $person,
            'certificates' => $certificates,
            'course' => $course,
        ]);
    }

    public function studentCertificates()
    {
        $studentId = AcaStudent::where('person_id', Auth::user()->person_id)->value('id');

        $courseId = request('course_id');
        $perPage = request('per_page', 10);

        $certificates = AcaCertificate::with(['course', 'module'])
            ->where('student_id', $studentId)
            ->when($courseId, function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $courses = AcaCourse::whereHas('certificates', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })
            ->get(['id', 'description']);

        return Inertia::render('Academic::Students/Certificates', [
            'certificates' => $certificates,
            'courses' => $courses,
            'filters' => request()->only(['course_id', 'per_page']),
        ]);
    }

    public function findStudentCertificate($courseId)
    {
        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();

        if (! $student) {
            return response()->json(['success' => false]);
        }

        $certificate = AcaCertificate::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->first();

        if ($certificate) {
            return response()->json([
                'success' => true,
                'certificate_id' => $certificate->id,
            ]);
        }

        return response()->json(['success' => false]);
    }
}
