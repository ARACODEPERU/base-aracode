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
use Modules\Academic\Http\Controllers\AcaAuthController;
use Modules\Academic\Http\Controllers\AcaCertificateController;
use Modules\Academic\Http\Controllers\AcaContentController;
use Modules\Academic\Http\Controllers\AcaModuleController;
use Modules\Academic\Http\Controllers\AcaStudentController;
use Modules\Academic\Http\Controllers\MercadopagoController;

Route::middleware(['auth', 'verified', 'invalid_updated_information'])->prefix('academic')->group(function () {
    Route::middleware(['middleware' => 'permission:aca_dashboard'])
        ->get('dashboard', 'AcademicController@index')
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

    Route::post('teachers/store', 'AcaTeacherController@store')->name('aca_teachers_store');
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

    Route::middleware(['permission:aca_estudiante_certificados_crear'])
        ->get('students/certificates/{id}', 'AcaCertificateController@studentCreate')
        ->name('aca_students_certificates_create');

    Route::post('students/certificates_store', [AcaCertificateController::class, 'studentStore'])
        ->name('aca_students_certificates_store');

    Route::delete('students/certificates_destroy/{id}', 'AcaCertificateController@studentDestroy')
        ->name('aca_students_certificates_destroy');

    Route::middleware(['permission:aca_estudiante_certificados_crear'])
        ->get('students/registrations/{id}', 'AcaCapRegistrationController@create')
        ->name('aca_students_registrations_create');

    Route::post('students/registrations_store', 'AcaCapRegistrationController@store')
        ->name('aca_students_registrations_store');

    Route::post('students/subscriptions_store', 'AcaCapRegistrationController@subscriptionStore')
        ->name('aca_students_subscriptions_store');

    Route::delete('students/subscriptions_destroy/{student_id}/{subscription_id}', 'AcaCapRegistrationController@subscriptionDestroy')
        ->name('aca_students_subscriptions_destroy');

    Route::delete('students/registrations_destroy/{id}', 'AcaCapRegistrationController@destroy')
        ->name('aca_students_registrations_destroy');

    Route::post('students/store', 'AcaStudentController@store')
        ->name('aca_students_store');

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

    Route::middleware(['middleware' => 'permission:aca_cursos_editar'])
        ->get('courses/edit/{id}', 'AcaCourseController@edit')
        ->name('aca_courses_edit');

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

    Route::middleware(['middleware' => 'permission:aca_estudiante_cobrar'])
        ->get('student/invoice/create/{id}', 'AcaStudentController@invoice')
        ->name('aca_student_invoice');

    Route::middleware(['middleware' => 'permission:aca_estudiante_cobrar'])
        ->post('student/sale/store', 'AcaSalesController@store')
        ->name('aca_student_invoice_store');

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

    ////subscriptions/////
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

    Route::get('certificate/list', [AcaCertificateController::class, 'index'])
        ->name('aca_certificate_list');

    Route::get('certificate/create', [AcaCertificateController::class, 'create'])
        ->name('aca_certificate_create');

    Route::post('certificate/store', [AcaCertificateController::class, 'store'])
        ->name('aca_certificate_store');

    Route::get('certificate/{id}/edit', [AcaCertificateController::class, 'edit'])
        ->name('aca_certificate_edit');

    Route::post('certificate/update/info', [AcaCertificateController::class, 'updateInfo'])
        ->name('aca_certificate_update_info');


    ////////////////verificar datos///////////////////////////

});

/////////no nesesita aver iniciado session//////////
Route::get('create/payment/{id}/account', [LandingController::class, 'academiCreatePayment'])->name('academic_step_account');

Route::put('create/payment/{id}/login', [AcaAuthController::class, 'login'])
    ->name('academic_step_account_login');
Route::put('create/payment/{id}/create', [AcaAuthController::class, 'create'])
    ->name('academic_step_account_create');

Route::middleware(['auth'])->get('create/payment/{id}/Verification', [AcaAuthController::class, 'userVerification'])
    ->name('academic_step_verification');

Route::middleware(['auth'])->get('create/payment/{id}/pay', [MercadopagoController::class, 'formPay'])
    ->name('academic_step_pay');

Route::middleware(['auth'])->put('mercadopago/{id}/academic', [MercadopagoController::class, 'processPayment'])
    ->name('aca_mercadopago_processpayment');


Route::middleware(['auth'])->get('thank/purchasing/{id}', [MercadopagoController::class, 'thankYou'])->name('web_gracias_por_comprar');
