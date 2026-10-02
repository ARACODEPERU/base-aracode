<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;
use Modules\Academic\Entities\AcaExcelStudentsExportJob;
use Modules\Academic\Http\Controllers\AcaAttendanceController;
use Modules\Academic\Http\Controllers\AcaAuthController;
use Modules\Academic\Http\Controllers\AcaCapRegistrationController;
use Modules\Academic\Http\Controllers\AcaCertificateController;
use Modules\Academic\Http\Controllers\AcaContentController;
use Modules\Academic\Http\Controllers\AcaCourseController;
use Modules\Academic\Http\Controllers\AcaCourseLandingController;
use Modules\Academic\Http\Controllers\AcaCourseOptionsController;
use Modules\Academic\Http\Controllers\AcademicController;
use Modules\Academic\Http\Controllers\AcaExamAnswerController;
use Modules\Academic\Http\Controllers\AcaExamController;
use Modules\Academic\Http\Controllers\AcaExamQuestionController;
use Modules\Academic\Http\Controllers\AcaGradeManagementController;
use Modules\Academic\Http\Controllers\AcaListVideoController;
use Modules\Academic\Http\Controllers\AcaModuleController;
use Modules\Academic\Http\Controllers\AcaReportsController;
use Modules\Academic\Http\Controllers\AcaSaleDocumentController;
use Modules\Academic\Http\Controllers\AcaSchoolController;
use Modules\Academic\Http\Controllers\AcaSchoolEnrollmentController;
use Modules\Academic\Http\Controllers\AcaSchoolStudentController;
use Modules\Academic\Http\Controllers\AcaSchoolStructureController;
use Modules\Academic\Http\Controllers\AcaSchoolTeacherController;
use Modules\Academic\Http\Controllers\AcaSchoolYearController;
use Modules\Academic\Http\Controllers\AcaSalesController;
use Modules\Academic\Http\Controllers\AcaShortVideoController;
use Modules\Academic\Http\Controllers\AcaStudentController;
use Modules\Academic\Http\Controllers\AcaStudentTestimonyController;
use Modules\Academic\Http\Controllers\AcaThemeCommentController;
use Modules\Academic\Http\Controllers\MercadopagoController;
use Modules\Academic\Jobs\ExportStudentsExcel;
use App\Http\Controllers\WebPageController;

Route::middleware(['auth', 'verified', 'invalid_updated_information', 'user_activity_log'])->prefix('academic')->group(function () {

    Route::middleware(['middleware' => 'permission:aca_dashboard'])
        ->get('dashboard', [AcademicController::class, 'index'])
        ->name('aca_dashboard');

    Route::middleware(['middleware' => 'permission:aca_institucion_listado'])
        ->get('institutions', 'AcaInstitutionController@index')
        ->name('aca_institutions_list');

    Route::middleware(['middleware' => 'permission:aca_institucion_nuevo'])
        ->get('institutions/create', 'AcaInstitutionController@create')
        ->name('aca_institutions_create');

    Route::post('institutions/store', 'AcaInstitutionController@store')->name('aca_institutions_store');
    Route::middleware(['middleware' => 'permission:aca_institucion_editar'])
        ->get('institutions/edit/{id}', 'AcaInstitutionController@edit')
        ->name('aca_institutions_edit');

    Route::middleware(['middleware' => 'permission:aca_institucion_eliminar'])
        ->delete('institutions/destroy/{id}', 'AcaInstitutionController@destroy')
        ->name('aca_institutions_destroy');

    Route::post('institutions/update', 'AcaInstitutionController@update')->name('aca_institutions_update');
    Route::middleware(['middleware' => 'permission:aca_docente_listado'])
        ->get('teachers', 'AcaTeacherController@index')
        ->name('aca_teachers_list');

    Route::middleware(['middleware' => 'permission:aca_docente_nuevo'])
        ->get('teachers/create', 'AcaTeacherController@create')
        ->name('aca_teachers_create');

    Route::middleware(['middleware' => 'permission:aca_docente_editar'])
        ->get('teachers/edit/{id}', 'AcaTeacherController@edit')
        ->name('aca_teachers_edit');

    Route::middleware(['permission:aca_docente_nuevo'])
        ->post('teachers/store', 'AcaTeacherController@store')->name('aca_teachers_store');
    Route::post('teachers/update', 'AcaTeacherController@update')->name('aca_teachers_update');
    Route::middleware(['middleware' => 'permission:aca_docente_eliminar'])
        ->delete('teachers/destroy/{id}', 'AcaTeacherController@destroy')
        ->name('aca_teachers_destroy');

    Route::get('teachers/resume/{id}', 'AcaTeacherController@resume')->name('aca_teachers_resume');
    Route::post('teachers/resume/work_experience/store', 'AcaTeacherController@workExperienceStore')->name('aca_teachers_work_experience_store');
    Route::delete('teachers/resume/work_experience/destroy/{id}', 'AcaTeacherController@workExperienceDestroy')->name('aca_teachers_work_experience_destroy');

    Route::middleware(['middleware' => 'permission:aca_estudiante_listado'])
        ->get('students', 'AcaStudentController@index')
        ->name('aca_students_list');

    Route::middleware(['middleware' => 'permission:aca_estudiante_nuevo'])
        ->get('students/create', 'AcaStudentController@create')
        ->name('aca_students_create');

    Route::middleware(['middleware' => 'permission:aca_estudiante_eliminar'])
        ->delete('students/destroy/{id}', [AcaStudentController::class, 'destroy'])
        ->name('aca_students_destroy');

    Route::middleware(['permission:aca_estudiante_certificados_crear'])
        ->get('students/certificates/{id}', 'AcaCertificateController@studentCreate')
        ->name('aca_students_certificates_create');

    Route::post('students/certificates_store', [AcaCertificateController::class, 'studentStore'])
        ->name('aca_students_certificates_store');

    Route::post('students/history_store', [AcaStudentController::class, 'historyStore'])
        ->name('aca_students_history_store');

    Route::delete('students/certificates_destroy/{id}', 'AcaCertificateController@studentDestroy')
        ->name('aca_students_certificates_destroy');

    Route::middleware(['permission:aca_estudiante_matricular'])
        ->get('students/registrations/{id}', 'AcaCapRegistrationController@create')
        ->name('aca_students_registrations_create');

    Route::middleware(['permission:aca_estudiante_matricular'])
        ->post('students/registrations_store', 'AcaCapRegistrationController@store')
        ->name('aca_students_registrations_store');

    Route::middleware(['permission:aca_estudiante_matricular'])
        ->post('students/subscriptions_store', [AcaCapRegistrationController::class, 'subscriptionStore'])
        ->name('aca_students_subscriptions_store');

    Route::middleware(['permission:aca_estudiante_matricular'])
        ->delete('students/subscriptions_destroy/{student_id}/{subscription_id}', [AcaCapRegistrationController::class, 'subscriptionDestroy'])
        ->name('aca_students_subscriptions_destroy');

    Route::middleware(['permission:aca_estudiante_matricular'])
        ->delete('students/registrations_destroy/{id}', 'AcaCapRegistrationController@destroy')
        ->name('aca_students_registrations_destroy');

    Route::middleware(['permission:aca_estudiante_matricular'])
        ->put('students/registrations_update/{id}', [AcaCapRegistrationController::class, 'update'])
        ->name('aca_students_registrations_update');

    Route::middleware(['permission:aca_estudiante_nuevo'])
        ->post('students/store', 'AcaStudentController@store')
        ->name('aca_students_store');
    Route::middleware(['auth', 'permission:aca_estudiante_enviar_correo_acceso'])
        ->get('students/send/accessmail/{personId}', [AcaStudentController::class, 'sendAccessMail'])
        ->name('aca_students_send_access_mail');

    Route::middleware(['auth', 'permission:aca_estudiante_nuevo'])
        ->get('students/send/password-recovery/{personId}', [AcaStudentController::class, 'sendPasswordRecoveryMail'])
        ->name('aca_students_send_password_recovery_mail');

    Route::middleware(['auth', 'permission:aca_estudiante_nuevo'])
        ->post('students/set-password/{personId}', [AcaStudentController::class, 'setStudentPassword'])
        ->name('aca_students_set_password');

    Route::middleware(['middleware' => 'permission:aca_estudiante_editar'])
        ->get('students/edit/{id}', 'AcaStudentController@edit')
        ->name('aca_students_edit');

    Route::post('students/update', 'AcaStudentController@update')->name('aca_students_update');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->get('courses', 'AcaCourseController@index')
        ->name('aca_courses_list');

    Route::middleware(['middleware' => 'permission:aca_cursos_nuevo'])
        ->get('courses/create', 'AcaCourseController@create')
        ->name('aca_courses_create');

    Route::post('courses/store', 'AcaCourseController@store')->name('aca_courses_store');

    /*
     * Mantenedor de Categorias / Tipo / Sector / Modalidad de cursos. Solo
     * admin y Administrador tienen el permiso (migracion
     * 2026_09_25_000001_add_aca_category_sector_type_modality_permission).
     */
    Route::middleware(['middleware' => 'permission:aca_category_sector_type_modality'])
        ->group(function () {
            Route::get('course-options', 'AcaCourseOptionsController@index')
                ->name('aca_course_options');

            Route::post('course-options/category', 'AcaCourseOptionsController@storeCategory')
                ->name('aca_course_options_category_store');
            Route::put('course-options/category/{id}', 'AcaCourseOptionsController@updateCategory')
                ->name('aca_course_options_category_update');
            Route::delete('course-options/category/{id}', 'AcaCourseOptionsController@destroyCategory')
                ->name('aca_course_options_category_destroy');

            Route::post('course-options/modality', 'AcaCourseOptionsController@storeModality')
                ->name('aca_course_options_modality_store');
            Route::put('course-options/modality/{id}', 'AcaCourseOptionsController@updateModality')
                ->name('aca_course_options_modality_update');
            Route::delete('course-options/modality/{id}', 'AcaCourseOptionsController@destroyModality')
                ->name('aca_course_options_modality_destroy');

            Route::post('course-options/enum', 'AcaCourseOptionsController@storeEnum')
                ->name('aca_course_options_enum_store');
            Route::put('course-options/enum', 'AcaCourseOptionsController@updateEnum')
                ->name('aca_course_options_enum_update');
            Route::delete('course-options/enum', 'AcaCourseOptionsController@destroyEnum')
                ->name('aca_course_options_enum_destroy');

            Route::put('course-options/assign', 'AcaCourseOptionsController@assign')
                ->name('aca_course_options_assign');
        });

    Route::middleware(['middleware' => 'permission:aca_cursos_editar'])
        ->get('courses/edit/{id}', 'AcaCourseController@edit')
        ->name('aca_courses_edit');

    Route::get('courses/{courseId}/landing', 'AcaCourseLandingController@edit')
        ->name('aca_courses_landing_edit');

    Route::put('courses/{courseId}/landing/general', 'AcaCourseLandingController@updateGeneral')
        ->name('aca_courses_landing_update_general');

    Route::put('courses/{courseId}/landing/banner', 'AcaCourseLandingController@updateBanner')
        ->name('aca_courses_landing_update_banner');

    Route::put('courses/{courseId}/landing/professional', 'AcaCourseLandingController@updateProfessional')
        ->name('aca_courses_landing_update_professional');

    Route::put('courses/{courseId}/landing/staff', 'AcaCourseLandingController@updateStaff')
        ->name('aca_courses_landing_update_staff');

    Route::put('courses/{courseId}/landing/results', 'AcaCourseLandingController@updateResults')
        ->name('aca_courses_landing_update_results');

    Route::post('courses/landing/testimonials/store', [AcaCourseLandingController::class, 'updateTestimonials'])
        ->name('aca_courses_landing_update_testimonials');

    Route::post('courses/landing/study_plan/store', [AcaCourseLandingController::class, 'updateStudyPlan'])
        ->name('aca_courses_landing_update_study_plan');

    Route::put('courses/{courseId}/landing/problem', 'AcaCourseLandingController@updateProblem')
        ->name('aca_courses_landing_update_problem');

    Route::put('courses/{courseId}/landing/investment', 'AcaCourseLandingController@updateInvestment')
        ->name('aca_courses_landing_update_investment');

    Route::put('courses/{courseId}/landing/faq', 'AcaCourseLandingController@updateFaq')
        ->name('aca_courses_landing_update_faq');

    Route::get('courses/landing/with-landing/{excludeCourseId}', [AcaCourseLandingController::class, 'getCoursesWithLanding'])
        ->name('aca_courses_landing_with_landing');

    Route::get('courses/{courseId}/landing/section/{section}', [AcaCourseLandingController::class, 'getSectionData'])
        ->name('aca_courses_landing_get_section');

    Route::put('courses/{courseId}/landing/utm-config', 'AcaCourseLandingController@updateUtmConfig')
        ->name('aca_courses_landing_update_utm_config');

    Route::get('courses/{courseId}/landing/utm-stats', [AcaCourseLandingController::class, 'getUtmStats'])
        ->name('aca_courses_landing_utm_stats');

    Route::put('courses/{courseId}/landing', 'AcaCourseLandingController@update')
        ->name('aca_courses_landing_update');

    Route::post('courses/update', 'AcaCourseController@update')->name('aca_courses_update');

    Route::middleware(['middleware' => 'permission:aca_cursos_eliminar'])
        ->delete('courses/destroy/{id}', 'AcaCourseController@destroy')
        ->name('aca_courses_destroy');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->get('courses/information/{id}', 'AcaCourseController@information')
        ->name('aca_courses_information');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->get('agreement/list/{id}', 'AcaAgreementController@index')
        ->name('aca_agreements_list');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->delete('agreement/destroy/{id}', 'AcaAgreementController@destroy')
        ->name('aca_agreements_destroy');

    Route::get('courses/modules/{id}/panel', [AcaModuleController::class, 'index'])->name('aca_courses_module_panel');

    Route::post('courses/modules/store', 'AcaModuleController@store')->name('aca_courses_module_store');
    Route::put('courses/modules/update/{id}', 'AcaModuleController@update')->name('aca_courses_module_update');
    Route::post('courses/modules/teacher/update', 'AcaModuleController@updateTeacher')->name('aca_courses_module_teacher_update');
    Route::delete('courses/modules/destroy/{id}', 'AcaModuleController@destroy')->name('aca_courses_module_destroy');
    Route::get('courses/modules/themes/list/{id}', 'AcaModuleController@getThemeByModelId')->name('aca_courses_module_themes_list');
    Route::post('courses/modules/themes/store', 'AcaThemeController@store')->name('aca_courses_module_themes_store');
    Route::put('courses/modules/themes/update/{id}', 'AcaThemeController@update')->name('aca_courses_module_themes_update');
    Route::delete('courses/modules/themes/destroy/{id}', 'AcaThemeController@destroy')->name('aca_courses_module_themes_destroy');
    Route::put('courses/modules/themes/content/update/{id}', 'AcaContentController@update')->name('aca_courses_module_themes_content_update');
    Route::post('courses/modules/themes/content/store', [AcaContentController::class, 'store'])->name('aca_courses_module_themes_content_store');
    Route::delete('courses/modules/themes/content/destroy/{id}', 'AcaContentController@destroy')->name('aca_courses_module_themes_content_destroy');

    Route::post('agreement/store', 'AcaAgreementController@store')->name('aca_agreements_store');
    Route::post('brochure/store', 'AcaBrochureController@store')->name('aca_brochure_store');
    Route::post('aca-upload-image', 'AcaBrochureController@uploadImage')->name('aca_upload_image_tiny');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->get('mycourses/student', 'AcaStudentController@myCourses')
        ->name('aca_mycourses');

    // Apartado "Testimonios" del alumno: solo cursos culminados y dentro de la ventana de edicion.
    Route::middleware(['middleware' => 'permission:aca_testimonios'])
        ->get('marcar-testimonio', [AcaStudentTestimonyController::class, 'index'])
        ->name('aca_student_testimonials');

    Route::middleware(['middleware' => 'permission:aca_testimonios'])
        ->post('marcar-testimonio/store', [AcaStudentTestimonyController::class, 'store'])
        ->name('aca_student_testimonials_store');

    Route::middleware(['middleware' => 'permission:aca_testimonios'])
        ->put('marcar-testimonio/update/{id}', [AcaStudentTestimonyController::class, 'update'])
        ->name('aca_student_testimonials_update');

    Route::middleware(['middleware' => 'permission:aca_testimonios'])
        ->delete('marcar-testimonio/destroy/{id}', [AcaStudentTestimonyController::class, 'destroy'])
        ->name('aca_student_testimonials_destroy');

    Route::get('courses_teacher_null', 'AcaCourseController@getCoursesTeacherNull')
        ->name('courses_teacher_null');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->get('course/student/{id}/modules', 'AcaStudentController@courseLessons')
        ->name('aca_mycourses_lessons');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->get('course/student/{id}/module/themes', 'AcaStudentController@courseLessonThemes')
        ->name('aca_mycourses_lesson_themes');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->get('course/comments/theme/list/{id}', 'AcaThemeCommentController@list')
        ->name('aca_lesson_comments');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->post('course/comments/theme/store', 'AcaThemeCommentController@store')
        ->name('aca_lesson_comments_store');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->put('course/comments/theme/update/{id}', 'AcaThemeCommentController@update')
        ->name('aca_lesson_comments_update');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->delete('course/comments/theme/destroy/{id}', 'AcaThemeCommentController@destroy')
        ->name('aca_lesson_comments_destroy');

    // Ruta para obtener comentarios de un estudiante en un tema
    Route::post('course/comments/by-student', [AcaThemeCommentController::class, 'commentsByStudent'])
        ->name('aca_theme_comments_by_student');

    Route::middleware(['middleware' => 'permission:aca_estudiante_cobrar'])
        ->get('student/invoice/create/{id}/{installments?}', [AcaStudentController::class, 'invoice'])
        ->name('aca_student_invoice');

    Route::middleware(['middleware' => 'permission:aca_estudiante_cobrar'])
        ->post('student/sale/store', [AcaSalesController::class, 'store'])
        ->name('aca_student_invoice_store');

    Route::middleware(['middleware' => 'permission:aca_estudiante_listar_comprobantes'])
        ->get('student/sale/list/{id}', 'AcaSalesController@listDocumentByStudent')
        ->name('aca_student_invoice_list');

    Route::get('student/sale/listtable/{id}', [AcaSalesController::class, 'tableDocumentStudent'])
        ->name('aca_student_invoice_list_table');

    Route::middleware(['middleware' => 'permission:aca_estudiante_listar_cuotas_espaciales'])
        ->get('student/sale/spacesales/{id}/list', [AcaSalesController::class, 'spaceSalesList'])
        ->name('aca_student_space_sales_list');

    Route::get('student/sale/spacesales/{id}/listtable', [AcaSalesController::class, 'tableSpaceSalesList'])
        ->name('aca_student_space_sales_list_table');

    Route::middleware(['middleware' => 'permission:aca_estudiante_listar_cuotas_espaciales'])
        ->get('student/sale/spacesales/{id}/create', [AcaSalesController::class, 'spaceSalesCreate'])
        ->name('aca_student_space_sales_create');

    Route::middleware(['middleware' => 'permission:aca_estudiante_listar_cuotas_espaciales'])
        ->put('student/sale/spacesales/{id}/store', [AcaSalesController::class, 'storeSpacePayments'])
        ->name('aca_student_space_sales_store');

    Route::post('student/send/mail/student/ticket', [AcaSaleDocumentController::class, 'sendEmailBoleta'])
        ->name('aca_send_email_student_document');

    Route::middleware(['middleware' => 'permission:aca_miscursos'])
        ->post('student/dashboard/courses', 'AcaStudentController@getCourses')
        ->name('aca_student_dashboard_courses');

    Route::middleware(['middleware' => 'permission:aca_estudiante_importar_excel'])
        ->post('student/import/excel', 'AcaStudentController@import')
        ->name('aca_student_import_file_excel');

    Route::middleware(['middleware' => 'permission:aca_estudiante_importar_excel'])
        ->get('student/import/{importKey}/progress', 'AcaStudentController@getProgress')
        ->name('aca_student_import_progress');

    Route::middleware(['middleware' => 'permission:aca_dashboard'])
        ->get('dashboard/total/registration/student', 'AcademicController@studentsEnrolledMonth')
        ->name('aca_student_registration_total');

    Route::middleware(['middleware' => 'permission:aca_dashboard'])
        ->get('dashboard/courses/registration/student/genero', 'AcademicController@getStudentsCourses')
        ->name('aca_student_registration_courses');

    Route::post('update_tour_user', [AcademicController::class, 'updateTourUser'])
        ->name('update_tour_user');

    // //subscriptions/////
    Route::middleware(['middleware' => 'permission:aca_suscripciones'])
        ->get('subscriptions/list', 'AcaSubscriptionTypeController@index')
        ->name('aca_subscriptions_list');

    Route::middleware(['middleware' => 'permission:aca_suscripciones_nuevo'])
        ->get('subscriptions/create', 'AcaSubscriptionTypeController@create')
        ->name('aca_subscriptions_create');

    Route::middleware(['middleware' => 'permission:aca_suscripciones_nuevo'])
        ->post('subscriptions/store', 'AcaSubscriptionTypeController@store')
        ->name('aca_subscriptions_store');

    Route::middleware(['middleware' => 'permission:aca_suscripciones_editar'])
        ->get('subscriptions/edit/{id}', 'AcaSubscriptionTypeController@edit')
        ->name('aca_subscriptions_edit');

    Route::middleware(['middleware' => 'permission:aca_suscripciones_editar'])
        ->put('subscriptions/update/{id}', 'AcaSubscriptionTypeController@update')
        ->name('aca_subscriptions_update');

    Route::middleware(['middleware' => 'permission:aca_suscripciones_eliminar'])
        ->delete('subscriptions/destroy/{id}', 'AcaSubscriptionTypeController@destroy')
        ->name('aca_subscriptions_destroy');

    Route::post('subscriptions/free/user', [AcaStudentController::class, 'startStudentFree'])
        ->name('aca_subscriptions_free_user');

    Route::post('subscriptions/student/expired/expiring', [AcaStudentController::class, 'getSubscriptionStatuses'])
        ->name('aca_subscriptions_expired_expiring');

    // ///////prueba de imagen en vuejs certificado

    Route::get('test', [AcaCertificateController::class, 'test'])
        ->name('test');
    Route::get('test2', [AcaCertificateController::class, 'test2'])
        ->name('test2');
    Route::get('test3', [AcaCertificateController::class, 'test3'])
        ->name('test3');

    // ////////////fin de suscripciones

    Route::get('certificate/list', [AcaCertificateController::class, 'index'])
        ->name('aca_certificate_list');

    Route::get('certificate/create', [AcaCertificateController::class, 'create'])
        ->name('aca_certificate_create');

    Route::post('certificate/store', [AcaCertificateController::class, 'store'])
        ->name('aca_certificate_store');

    Route::get('certificate/{id}/edit', [AcaCertificateController::class, 'edit'])
        ->name('aca_certificate_edit');

    Route::delete('certificate/{id}/destroy', [AcaCertificateController::class, 'destroy'])
        ->name('aca_certificate_destroy');

    Route::post('certificate/update/info', [AcaCertificateController::class, 'updateInfo'])
        ->name('aca_certificate_update_info');

    Route::get('certificate/{id}/test-preview', [AcaCertificateController::class, 'testCertificatePreview'])
        ->name('aca_certificate_test_preview');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado_estudiantes'])
        ->get('courses/enrolledstudents/{id}/registered', [AcaCourseController::class, 'enrolledStudents'])
        ->name('aca_enrolledstudents_list');

    Route::put('certificate/massive/{id}/store', [AcaCertificateController::class, 'storeMassive'])
        ->name('aca_certificate_massive_store');

    Route::post('student/certificates/menu', [AcaStudentController::class, 'getCertificates'])
        ->name('aca_certificate_by_student');

    Route::middleware(['middleware' => 'permission:aca_tutoriales_lista'])->get('tutorials/playlist', [AcaListVideoController::class, 'index'])
        ->name('aca_tutorials_playlist');

    Route::middleware(['middleware' => 'permission:aca_tutoriales_lista_nuevo'])->post('tutorials/playlist/store', [AcaListVideoController::class, 'storeOrUpdate'])
        ->name('aca_tutorials_playlist_store');

    Route::middleware(['middleware' => 'permission:aca_tutoriales_videos_nuevo'])->post('tutorials/video/store', [AcaShortVideoController::class, 'store'])
        ->name('aca_tutorials_video_store');

    Route::post('tutorials/video/todos', [AcaShortVideoController::class, 'studentVideos'])
        ->name('aca_tutorials_video_todos_estudiante');

    Route::middleware(['middleware' => 'permission:aca_tutoriales_videos_eliminar'])
        ->post('tutorials/video/destroy', [AcaShortVideoController::class, 'destroyOrUpdate'])
        ->name('aca_tutorials_video_eliminar_actualizar');

    Route::middleware(['middleware' => 'permission:aca_tutoriales_lista_eliminar'])
        ->delete('tutorials/playlist/destroy/{id}', [AcaListVideoController::class, 'destroy'])
        ->name('aca_tutorials_playlist_eliminar');

    Route::middleware(['middleware' => 'permission:aca_tutoriales_videos'])
        ->get('tutorials/video/list', [AcaShortVideoController::class, 'index'])
        ->name('aca_tutorials_videos_list');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->post('course/exam/store', [AcaExamController::class, 'store'])
        ->name('aca_course_exam_store');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->delete('course/module/exam/{id}/destroy', [AcaExamController::class, 'destroy'])
        ->name('aca_course_exam_destroy');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->put('course/module/exam/{id}/activate', [AcaExamController::class, 'activate'])
        ->name('aca_course_exam_activate');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->post('course/exam/question/store', [AcaExamQuestionController::class, 'store'])
        ->name('aca_course_exam_question_store');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->delete('course/exam/question/{id}/destroy', [AcaExamQuestionController::class, 'destroy'])
        ->name('aca_course_exam_question_destroy');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->post('course/exam/answer/store', [AcaExamAnswerController::class, 'store'])
        ->name('aca_course_exam_answer_store');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->delete('course/exam/answer/{id}/destroy', [AcaExamAnswerController::class, 'destroy'])
        ->name('aca_course_exam_answer_destroy');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_ver'])
        ->get('student/exam/{id}/solve', [AcaExamController::class, 'solve'])
        ->name('aca_student_exam_solve');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_ver'])
        ->post('student/exam/solve/store', [AcaExamController::class, 'storeStudent'])
        ->name('aca_student_exam_solve_store');

    Route::middleware(['middleware' => 'permission:aca_cursos_revisar_examenes'])
        ->get('student/review/exams', [AcaExamController::class, 'reviewExams'])
        ->name('aca_student_exam_review_exams');

    Route::get('student/review/exams/table', [AcaExamController::class, 'getAlumnsExam'])->name('aca_student_exam_review_exams_table');
    Route::post('student/grade/exam/response/store', [AcaExamAnswerController::class, 'gradeExamResponse'])->name('aca_student_grade_exam_response_store');
    // //////////////verificar datos///////////////////////////
    Route::post('buy/course/mercadopago', [MercadopagoController::class, 'createPreference'])->name('academic_create_preference_course');
    Route::post('buy/course/items/mercadopago', [MercadopagoController::class, 'createItemsPreference'])->name('academic_create_items_preference_course');
    Route::post('buy/course/processpayment/mercadopago', [MercadopagoController::class, 'processPaymentCourses'])->name('academic_processpayment_courses_mercadopago');

    Route::middleware(['middleware' => 'permission:aca_estudiante_exportar_excel'])
        ->post('/export/students-excel', function (Request $request) {

            // Crea un registro en la base de datos para el estado del job
            $excelExportJob = AcaExcelStudentsExportJob::create([
                'user_id' => auth()->id(),
                'status' => 'pending',
            ]);

            // Despacha el Job a la cola, pasándole el ID del registro de estado
            ExportStudentsExcel::dispatch(auth()->id(), $excelExportJob->id);

            return response()->json([
                'message' => 'La exportación de Excel ha sido iniciada. Por favor, espere un momento.',
                'job_id' => $excelExportJob->id, // Envía el ID del job al frontend
            ], 202);

        })->name('aca_export_students_excel');

    Route::middleware(['middleware' => 'permission:aca_estudiante_exportar_excel'])
        ->get('/export/students-excel/status/{jobId}', function ($jobId) {
            if (! auth()->check()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            // Busca el job por ID y verifica que pertenezca al usuario
            $excelExportJob = AcaExcelStudentsExportJob::where('id', $jobId)
                ->where('user_id', auth()->id())
                ->first();

            if (! $excelExportJob) {
                return response()->json(['message' => 'Estado de exportación no encontrado o no autorizado.'], 404);
            }

            return response()->json($excelExportJob);
        })->name('aca_export_students_excel_status');

    // //////reportes Academico/////////////////
    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/index', [AcaReportsController::class, 'index'])
        ->name('aca_reports_dashboard');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/student/payment/bank', [AcaReportsController::class, 'studentPaymentBank'])
        ->name('aca_student_payment_report_bank');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->post('reports/student/payment/bank/table', [AcaReportsController::class, 'studentPaymentBankTable'])
        ->name('aca_student_payment_report_bank_table');

    Route::post('reports/student/payment/bank/export', [AcaReportsController::class, 'exportStudentPaymentBankSales'])
        ->name('aca_student_payment_report_bank_export');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/student/payment/bank/export/status/{id}', [AcaReportsController::class, 'exportStatus'])
        ->name('aca_export_status');

    Route::middleware(['middleware' => 'permission:aca_reportes_estado_susc_estudiantes'])
        ->get('reports/student/subscriptions/expired', [AcaReportsController::class, 'expiredSubscriptions'])
        ->name('aca_subscriptions_expired_student');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/student/performance', [AcaReportsController::class, 'studentPerformanceReport'])
        ->name('aca_student_performance_report');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->post('reports/student/performance/table', [AcaReportsController::class, 'studentPerformanceTable'])
        ->name('aca_student_performance_report_table');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->post('reports/student/performance/export', [AcaReportsController::class, 'exportStudentPerformance'])
        ->name('aca_student_performance_export');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/student/performance/export/status/{jobId}', [AcaReportsController::class, 'exportStudentPerformanceStatus'])
        ->name('aca_student_performance_export_status');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/student/enrollment/documents', [AcaReportsController::class, 'enrollmentDocumentsReport'])
        ->name('aca_enrollment_documents_report');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->post('reports/student/enrollment/documents/table', [AcaReportsController::class, 'enrollmentDocumentsTable'])
        ->name('aca_enrollment_documents_report_table');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->post('reports/student/enrollment/documents/export', [AcaReportsController::class, 'exportEnrollmentDocuments'])
        ->name('aca_enrollment_documents_export');

    Route::middleware(['middleware' => 'permission:aca_reportes'])
        ->get('reports/student/enrollment/documents/export/status/{jobId}', [AcaReportsController::class, 'exportEnrollmentDocumentsStatus'])
        ->name('aca_enrollment_documents_export_status');

    Route::middleware(['middleware' => 'permission:aca_suscripcion_estudiante_editar'])
        ->post('reports/student/subscription/update', [AcaCapRegistrationController::class, 'updateSubscriptionStudent'])
        ->name('aca_subscriptions_update_student');

    Route::middleware(['middleware' => 'permission:aca_cursos_modulos_examen'])
        ->post('courses/modules/exmen/updateorcreate', [AcaModuleController::class, 'updateOrCreateExam'])
        ->name('aca_course_module_exam_update_create');

    Route::middleware(['middleware' => 'permission:aca_cursos_modulos_examen'])
        ->get('courses/{cId}/modules/{mId}/exmen/{eId}/panel', [AcaExamController::class, 'questionAnswerPanelModule'])
        ->name('aca_course_module_exam_view_details');

    // Examen final del curso
    Route::middleware(['middleware' => 'permission:aca_cursos_modulos_examen'])
        ->post('courses/exam/updateorcreate', [AcaCourseController::class, 'updateOrCreateCourseExam'])
        ->name('aca_course_exam_update_create');

    Route::middleware(['middleware' => 'permission:aca_cursos_modulos_examen'])
        ->get('courses/{courseId}/exam/{examId}/panel', [AcaExamController::class, 'questionAnswerPanelCourse'])
        ->name('aca_course_exam_view_details');

    // Participaciones de estudiantes
    Route::middleware(['middleware' => 'permission:aca_gestion_de_participaciones'])
        ->get('courses/participations/students', [AcaCourseController::class, 'participations'])
        ->name('aca_students_course_participations');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->put('courses/participations/search/{courseId}', [AcaCourseController::class, 'searchParticipations'])
        ->name('aca_course_participation_search');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->post('courses/participations/store', [AcaCourseController::class, 'storeParticipation'])
        ->name('aca_course_participation_store');

    Route::middleware(['middleware' => 'permission:aca_cursos_listado'])
        ->post('courses/participations/store/all', [AcaCourseController::class, 'storeAllParticipations'])
        ->name('aca_course_participation_store_all');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->post('course/exam/question/form/store', [AcaExamQuestionController::class, 'storeQuestion'])
        ->name('aca_course_exam_question_form_store');
    Route::middleware(['middleware' => 'permission:aca_cursos_examen_configuracion'])
        ->post('course/exam/answer/form/store', [AcaExamAnswerController::class, 'storeAnswer'])
        ->name('aca_course_exam_answer_form_store');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_resolver'])
        ->get('student/module/exam/{id}/solve', [AcaExamController::class, 'moduleExamSolve'])
        ->name('aca_student_module_exam_solve');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_resolver'])
        ->post('student/module/exam/solve/store', [AcaExamController::class, 'moduleStoreAnswer'])
        ->name('aca_student_module_exam_answer_save');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_resolver'])
        ->post('student/module/exam/solve/finish', [AcaExamController::class, 'moduleStoreFinish'])
        ->name('aca_student_exam_module_finish');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_resolver'])
        ->post('student/module/exam/solve/start/{id}', [AcaExamController::class, 'moduleStartExam'])
        ->name('aca_student_module_exam_start');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_resolver'])
        ->post('student/module/exam/solve/retry/{id}', [AcaExamController::class, 'retryExam'])
        ->name('aca_student_module_exam_retry');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_resolver'])
        ->get('student/module/exam/{id}/solve/download', [AcaExamController::class, 'downloadPdf'])
        ->name('aca_student_exam_download_pdf');

    Route::middleware(['middleware' => 'permission:aca_alumno_examenes'])
        ->get('student/exams/all', [AcaExamController::class, 'studentExams'])
        ->name('aca_student_exam_search');

    Route::middleware(['middleware' => 'permission:aca_cursos_examen_eliminar'])
        ->delete('student/exam/{id}/destroy', [AcaExamController::class, 'destroyStudentExam'])
        ->name('aca_student_exam_destroy');

    Route::middleware(['middleware' => 'permission:aca_alumno_examenes'])
        ->get('student/attendances', [AcaStudentController::class, 'studentAttendances'])
        ->name('aca_student_attendances');

    Route::middleware(['middleware' => 'permission:aca_alumno_examenes'])
        ->get('student/attendances/search', [AcaStudentController::class, 'searchStudentAttendances'])
        ->name('aca_student_attendances_search');

    Route::get('student/certificates/all', [AcaCertificateController::class, 'studentCertificates'])
        ->name('aca_student_certificates_all');

    Route::middleware(['middleware' => 'permission:aca_asistencia_crear_link'])
        ->post('attendance/link/store', [AcaAttendanceController::class, 'storeLink'])
        ->name('aca_attendance_link_store');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->get('attendance/administration', [AcaAttendanceController::class, 'administrationPanel'])
        ->name('aca_attendance_administration');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->get('attendance/modules/{course}', [AcaAttendanceController::class, 'getModulesByCourse'])
        ->name('aca_attendance_modules');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->get('attendance/themes/{module}', [AcaAttendanceController::class, 'getThemesByModule'])
        ->name('aca_attendance_themes');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->get('attendance/contents/{theme}', [AcaAttendanceController::class, 'getContentsByTheme'])
        ->name('aca_attendance_contents');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->post('attendance/students/query', [AcaAttendanceController::class, 'getStudentsAttendance'])
        ->name('aca_attendance_students');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->post('attendance/update', [AcaAttendanceController::class, 'updateAttendance'])
        ->name('aca_attendance_update');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->post('attendance/observation', [AcaAttendanceController::class, 'updateObservation'])
        ->name('aca_attendance_observation');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->post('attendance/export', [AcaAttendanceController::class, 'exportAttendanceExcel'])
        ->name('aca_attendance_export');

    Route::middleware(['middleware' => 'permission:aca_asistencia_administrador'])
        ->get('attendance/export/status/{jobId}', [AcaAttendanceController::class, 'exportAttendanceStatus'])
        ->name('aca_attendance_export_status');

    Route::middleware(['middleware' => 'permission:aca_gestion_de_calificaciones'])
        ->get('grade/management', [AcaGradeManagementController::class, 'index'])
        ->name('aca_grade_management_panel');

    Route::middleware(['middleware' => 'permission:aca_gestion_de_calificaciones'])
        ->post('grade/management/search', [AcaGradeManagementController::class, 'search'])
        ->name('aca_grade_management_search');

    Route::middleware(['middleware' => 'permission:aca_gestion_de_calificaciones'])
        ->post('grade/management/store', [AcaGradeManagementController::class, 'store'])
        ->name('aca_grade_management_store');

    /*
     * ------------------------------------------------------------------
     * Modulo escolar (colegios): anios escolares, estructura
     * nivel/grado/seccion, alumnos escolares y matriculas.
     * El colegio activo lo resuelve SchoolContextService (parametro
     * PTM0005 = modo multi-colegio).
     * ------------------------------------------------------------------
     */

    // Colegios (mantenedor principal, sobre todo en modo multi-colegio)
    Route::middleware(['middleware' => 'permission:aca_school_listado'])
        ->get('schools', [AcaSchoolController::class, 'index'])->name('aca_schools_list');
    Route::middleware(['middleware' => 'permission:aca_school_nuevo'])
        ->get('schools/create', [AcaSchoolController::class, 'create'])->name('aca_schools_create');
    Route::middleware(['middleware' => 'permission:aca_school_nuevo'])
        ->post('schools/store', [AcaSchoolController::class, 'store'])->name('aca_schools_store');
    Route::middleware(['middleware' => 'permission:aca_school_editar'])
        ->get('schools/edit/{id}', [AcaSchoolController::class, 'edit'])->name('aca_schools_edit');
    Route::middleware(['middleware' => 'permission:aca_school_editar'])
        ->post('schools/update', [AcaSchoolController::class, 'update'])->name('aca_schools_update');
    Route::middleware(['middleware' => 'permission:aca_school_eliminar'])
        ->delete('schools/destroy/{id}', [AcaSchoolController::class, 'destroy'])->name('aca_schools_destroy');

    // Años escolares
    Route::middleware(['middleware' => 'permission:aca_school_year_listado'])
        ->get('school/years', [AcaSchoolYearController::class, 'index'])->name('aca_school_years_list');
    Route::middleware(['middleware' => 'permission:aca_school_year_nuevo'])
        ->post('school/years/store', [AcaSchoolYearController::class, 'store'])->name('aca_school_years_store');
    Route::middleware(['middleware' => 'permission:aca_school_year_editar'])
        ->put('school/years/update', [AcaSchoolYearController::class, 'update'])->name('aca_school_years_update');
    Route::middleware(['middleware' => 'permission:aca_school_year_editar'])
        ->put('school/years/activate/{id}', [AcaSchoolYearController::class, 'activate'])->name('aca_school_years_activate');
    Route::middleware(['middleware' => 'permission:aca_school_year_editar'])
        ->put('school/years/close/{id}', [AcaSchoolYearController::class, 'close'])->name('aca_school_years_close');

    // Estructura academica: niveles, grados y secciones
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->get('school/structure', [AcaSchoolStructureController::class, 'index'])->name('aca_school_structure');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->post('school/structure/level/store', [AcaSchoolStructureController::class, 'storeLevel'])->name('aca_school_structure_level_store');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->put('school/structure/level/{id}/update', [AcaSchoolStructureController::class, 'updateLevel'])->name('aca_school_structure_level_update');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->delete('school/structure/level/{id}/destroy', [AcaSchoolStructureController::class, 'destroyLevel'])->name('aca_school_structure_level_destroy');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->post('school/structure/grade/store', [AcaSchoolStructureController::class, 'storeGrade'])->name('aca_school_structure_grade_store');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->put('school/structure/grade/{id}/update', [AcaSchoolStructureController::class, 'updateGrade'])->name('aca_school_structure_grade_update');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->delete('school/structure/grade/{id}/destroy', [AcaSchoolStructureController::class, 'destroyGrade'])->name('aca_school_structure_grade_destroy');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->post('school/structure/section/store', [AcaSchoolStructureController::class, 'storeSection'])->name('aca_school_structure_section_store');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->put('school/structure/section/{id}/update', [AcaSchoolStructureController::class, 'updateSection'])->name('aca_school_structure_section_update');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->delete('school/structure/section/{id}/destroy', [AcaSchoolStructureController::class, 'destroySection'])->name('aca_school_structure_section_destroy');
    Route::middleware(['middleware' => 'permission:aca_school_estructura'])
        ->post('school/structure/search-teachers', [AcaSchoolStructureController::class, 'searchTeachers'])->name('aca_school_structure_search_teachers');

    // Alumnos escolares
    Route::middleware(['middleware' => 'permission:aca_school_alumno_listado'])
        ->get('school/students', [AcaSchoolStudentController::class, 'index'])->name('aca_school_students_list');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_nuevo'])
        ->get('school/students/create', [AcaSchoolStudentController::class, 'create'])->name('aca_school_students_create');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_nuevo'])
        ->post('school/students/store', [AcaSchoolStudentController::class, 'store'])->name('aca_school_students_store');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_editar'])
        ->get('school/students/edit/{id}', [AcaSchoolStudentController::class, 'edit'])->name('aca_school_students_edit');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_editar'])
        ->post('school/students/update', [AcaSchoolStudentController::class, 'update'])->name('aca_school_students_update');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_eliminar'])
        ->delete('school/students/destroy/{id}', [AcaSchoolStudentController::class, 'destroy'])->name('aca_school_students_destroy');

    // Apoderados de alumnos escolares
    Route::middleware(['middleware' => 'permission:aca_school_alumno_editar'])
        ->get('school/students/{studentId}/guardians', [AcaSchoolStudentController::class, 'listGuardians'])->name('aca_school_students_guardians_list');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_editar'])
        ->post('school/students/{studentId}/guardians/store', [AcaSchoolStudentController::class, 'storeGuardian'])->name('aca_school_students_guardians_store');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_editar'])
        ->put('school/students/guardians/{guardianId}/update', [AcaSchoolStudentController::class, 'updateGuardian'])->name('aca_school_students_guardians_update');
    Route::middleware(['middleware' => 'permission:aca_school_alumno_editar'])
        ->delete('school/students/guardians/{guardianId}/destroy', [AcaSchoolStudentController::class, 'destroyGuardian'])->name('aca_school_students_guardians_destroy');

    // Matriculas escolares
    Route::middleware(['middleware' => 'permission:aca_school_matricula_listado'])
        ->get('school/enrollments', [AcaSchoolEnrollmentController::class, 'index'])->name('aca_school_enrollments_list');
    Route::middleware(['middleware' => 'permission:aca_school_matricula_nueva'])
        ->get('school/enrollments/create', [AcaSchoolEnrollmentController::class, 'create'])->name('aca_school_enrollments_create');
    Route::middleware(['middleware' => 'permission:aca_school_matricula_nueva'])
        ->post('school/enrollments/store', [AcaSchoolEnrollmentController::class, 'store'])->name('aca_school_enrollments_store');
    Route::middleware(['middleware' => 'permission:aca_school_matricula_editar'])
        ->put('school/enrollments/{id}/update', [AcaSchoolEnrollmentController::class, 'update'])->name('aca_school_enrollments_update');
    Route::middleware(['middleware' => 'permission:aca_school_matricula_editar'])
        ->put('school/enrollments/{id}/status', [AcaSchoolEnrollmentController::class, 'changeStatus'])->name('aca_school_enrollments_status');

    // Docentes del colegio (clon del mantenedor de docentes de capacitaciones
    // con vistas/rutas/permisos propios para el grupo Colegio del menu)
    Route::middleware(['middleware' => 'permission:aca_school_docente_listado'])
        ->get('school/teachers', [AcaSchoolTeacherController::class, 'index'])->name('aca_school_teachers_list');
    Route::middleware(['middleware' => 'permission:aca_school_docente_nuevo'])
        ->get('school/teachers/create', [AcaSchoolTeacherController::class, 'create'])->name('aca_school_teachers_create');
    Route::middleware(['middleware' => 'permission:aca_school_docente_nuevo'])
        ->post('school/teachers/store', [AcaSchoolTeacherController::class, 'store'])->name('aca_school_teachers_store');
    Route::middleware(['middleware' => 'permission:aca_school_docente_editar'])
        ->get('school/teachers/edit/{id}', [AcaSchoolTeacherController::class, 'edit'])->name('aca_school_teachers_edit');
    Route::middleware(['middleware' => 'permission:aca_school_docente_editar'])
        ->post('school/teachers/update', [AcaSchoolTeacherController::class, 'update'])->name('aca_school_teachers_update');
    Route::middleware(['middleware' => 'permission:aca_school_docente_eliminar'])
        ->delete('school/teachers/destroy/{id}', [AcaSchoolTeacherController::class, 'destroy'])->name('aca_school_teachers_destroy');
    Route::middleware(['middleware' => 'permission:aca_school_docente_editar'])
        ->get('school/teachers/resume/{id}', [AcaSchoolTeacherController::class, 'resume'])->name('aca_school_teachers_resume');
    Route::post('school/teachers/resume/work_experience/store', [AcaSchoolTeacherController::class, 'workExperienceStore'])->name('aca_school_teachers_work_experience_store');
    Route::delete('school/teachers/resume/work_experience/destroy/{id}', [AcaSchoolTeacherController::class, 'workExperienceDestroy'])->name('aca_school_teachers_work_experience_destroy');

    // Cascadas y busquedas del formulario de matricula (axios JSON)
    Route::post('school/enrollments/grades-by-level', [AcaSchoolEnrollmentController::class, 'gradesByLevel'])->name('aca_school_enrollments_grades');
    Route::post('school/enrollments/sections-by-grade', [AcaSchoolEnrollmentController::class, 'sectionsByGrade'])->name('aca_school_enrollments_sections');
    Route::post('school/enrollments/search-students', [AcaSchoolEnrollmentController::class, 'searchStudents'])->name('aca_school_enrollments_search_students');
    Route::post('school/enrollments/search-guardians', [AcaSchoolEnrollmentController::class, 'searchGuardians'])->name('aca_school_enrollments_search_guardians');
});

Route::middleware(['auth', 'role:Administrador|webAdmin|admin|Docente'])
    ->get('/landing_preview/{id}', [WebPageController::class, 'course_landing_preview'])
    ->name('landing_preview');


Route::get('asistencia/registrar/clase', [AcaAttendanceController::class, 'registerAttendance']);
Route::post('asistencia/registrar/clase/store', [AcaAttendanceController::class, 'storeAttendance'])->name('aca_asistencia_store');
Route::get('asistencia/exitosa', [AcaAttendanceController::class, 'success'])->name('aca_attendance_success');

// ///////no nesesita aver iniciado session//////////
Route::get('academic/certificate/image/{id}/download', [AcaCertificateController::class, 'generateCertificateStudent'])
    ->name('aca_image_download');

Route::get('academic/certificate/module/{module_id}/download', [AcaCertificateController::class, 'downloadModuleCertificate'])
    ->name('aca_module_certificate_download');

Route::get('create/payment/{id}/account', [LandingController::class, 'academiCreatePayment'])->name('academic_step_account');

Route::put('create/payment/{id}/login', [AcaAuthController::class, 'login'])
    ->name('academic_step_account_login');
Route::put('create/payment/{id}/create', [AcaAuthController::class, 'create'])
    ->name('academic_step_account_create');

Route::middleware(['auth'])->get('create/payment/{id}/Verification', [AcaAuthController::class, 'userVerification'])
    ->name('academic_step_verification');

Route::middleware(['auth'])->put('create/payment/{id}/pay', [MercadopagoController::class, 'formPay'])
    ->name('academic_step_pay');

Route::middleware(['auth'])->put('mercadopago/{id}/academic', [MercadopagoController::class, 'processPayment'])
    ->name('aca_mercadopago_processpayment');

Route::middleware(['auth'])->get('thank/purchasing/{id}', [MercadopagoController::class, 'thankYou'])->name('web_gracias_por_comprar');
Route::get('/certificado-validar/{dni?}/{course_id?}/{module_id?}', [AcaCertificateController::class, 'certificado_validar'])->name('certificado_validar');

Route::get('academic/student/password-recovery/{personId}', [AcaStudentController::class, 'passwordRecoveryForm'])
    ->middleware(['guest', 'signed'])
    ->name('aca_students_password_recovery_form');

Route::post('academic/student/password-recovery/{personId}', [AcaStudentController::class, 'updateRecoveredPassword'])
    ->middleware(['guest', 'signed'])
    ->name('aca_students_password_recovery_update');
