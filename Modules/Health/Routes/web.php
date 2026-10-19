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

use Modules\Health\Http\Controllers\HealHistoryController;
use Modules\Health\Http\Controllers\HealAttentionController;
use Modules\Health\Http\Controllers\HealAgendaController;
use Modules\Health\Http\Controllers\HealProcedureChargeController;
use Modules\Health\Http\Controllers\HealActivityController;
use Modules\Health\Http\Controllers\HealSettingController;
use Modules\Health\Http\Controllers\HealTestDataController;
use Modules\Health\Http\Controllers\HealAttentionPdfController;
use Modules\Health\Http\Controllers\HealAppointmentNoticeController;
use Modules\Health\Http\Controllers\HealGoogleCalendarController;
use Modules\Health\Http\Controllers\HealDoctorGoogleCalendarController;
use Modules\Health\Http\Controllers\HealParametersController;
use Modules\Health\Http\Controllers\Odontology\HealOdoAppointmentController;

Route::middleware(['auth', 'verified'])->prefix('health')->group(function () {
    Route::get('dashboard', 'HealthController@index')->name('health_dashboard');
    Route::get('patients', 'HealPatientController@index')->name('heal_patients_list');
    Route::get('patients/create', 'HealPatientController@create')->name('heal_patients_create');
    Route::post('patients/store', 'HealPatientController@store')->name('heal_patients_store');
    Route::get('patients/edit/{id}', 'HealPatientController@edit')->name('heal_patients_edit');
    Route::post('patients/update', 'HealPatientController@update')->name('heal_patients_update');
    Route::delete('patients/destroy/{id}', 'HealPatientController@destroy')->name('heal_patients_destroy');
    Route::get('patients/panel/{id}', 'HealPatientController@patientPanel')->name('heal_patients_panel');

    Route::get('doctor', 'DoctorController@index')->name('heal_doctors_list');
    Route::get('doctor/create', 'DoctorController@create')->name('heal_doctors_create');
    Route::post('doctor/store', 'DoctorController@store')->name('heal_doctors_store');
    Route::get('doctor/edit/{id}', 'DoctorController@edit')->name('heal_doctors_edit');
    Route::post('doctor/update', 'DoctorController@update')->name('heal_doctors_update');
    Route::delete('doctor/destroy/{id}', 'DoctorController@destroy')->name('heal_doctors_destroy');
    Route::get('patients/appointments/{id}/todos', 'HealPatientController@appointments')->name('heal_patients_appointments');
    Route::get('patients/medical/{id}/record', 'HealHistoryController@patientStory')->name('heal_patients_story');
    Route::get('clinical-records', [HealHistoryController::class, 'clinicalRecords'])->name('heal_clinical_records_list');
    Route::get('clinical-records/{patient}', [HealHistoryController::class, 'clinicalRecordShow'])->name('heal_clinical_records_show');
    Route::get('agendas', [HealAgendaController::class, 'index'])->name('heal_agendas_list');
    Route::get('agendas/day', [HealAgendaController::class, 'day'])->name('heal_agendas_day');
    Route::get('agendas/availability', [HealAgendaController::class, 'availability'])->name('heal_agendas_availability');
    Route::post('agendas/appointments/store', [HealAgendaController::class, 'storeAppointment'])->name('heal_agendas_appointments_store');
    Route::post('agendas/appointments/move', [HealAgendaController::class, 'moveAppointment'])->name('heal_agendas_appointments_move');

    // Parámetros de Salud: enlace de Google Calendar por doctor (cada doctor ve el suyo; el admin, todos).
    Route::get('parametros', [HealParametersController::class, 'index'])->name('heal_parameters');

    // Avisos de citas (recordatorios a pacientes por SMS).
    Route::middleware(['permission:heal_avisos'])->group(function () {
        Route::get('avisos', [HealAppointmentNoticeController::class, 'index'])->name('heal_appointment_notices');
        Route::post('avisos/update', [HealAppointmentNoticeController::class, 'update'])->name('heal_appointment_notices_update');
        Route::post('avisos/preview', [HealAppointmentNoticeController::class, 'preview'])->name('heal_appointment_notices_preview');
        Route::post('avisos/test', [HealAppointmentNoticeController::class, 'test'])->name('heal_appointment_notices_test');
        Route::post('avisos/deliveries/{delivery}/retry', [HealAppointmentNoticeController::class, 'retry'])->name('heal_appointment_notices_retry');
    });

    // Google Calendar (sincronizacion bidireccional de la Agenda).
    Route::middleware(['permission:heal_google_calendar'])->prefix('google-calendar')->group(function () {
        Route::get('/', [HealGoogleCalendarController::class, 'index'])->name('heal_google_calendar');
        Route::get('connect', [HealGoogleCalendarController::class, 'connect'])->name('heal_google_calendar_connect');
        Route::post('disconnect', [HealGoogleCalendarController::class, 'disconnect'])->name('heal_google_calendar_disconnect');
        Route::post('sync', [HealGoogleCalendarController::class, 'syncNow'])->name('heal_google_calendar_sync');
        Route::post('test', [HealGoogleCalendarController::class, 'test'])->name('heal_google_calendar_test');
        Route::post('templates', [HealGoogleCalendarController::class, 'saveTemplates'])->name('heal_google_calendar_templates');
        Route::post('templates/preview', [HealGoogleCalendarController::class, 'previewTemplates'])->name('heal_google_calendar_templates_preview');
        Route::post('channel', [HealGoogleCalendarController::class, 'refreshChannel'])->name('heal_google_calendar_channel');
        Route::post('review/{mapping}', [HealGoogleCalendarController::class, 'review'])->name('heal_google_calendar_review');
        Route::post('review/{mapping}/discard', [HealGoogleCalendarController::class, 'discard'])->name('heal_google_calendar_review_discard');
    });

    // Callback de Google compartido por la agenda principal y por la de cada doctor.
    // Sin permiso de modulo: el doctor lo necesita para volver de su consentimiento.
    // HealGoogleCalendarController::callback valida la sesion y el permiso del flujo.
    Route::get('google-calendar/callback', [HealGoogleCalendarController::class, 'callback'])->name('heal_google_calendar_callback');

    // Enlace de la agenda de Google de cada doctor (confirmación con contraseña + conectar/desconectar).
    Route::prefix('agendas/google')->group(function () {
        Route::post('confirm', [HealDoctorGoogleCalendarController::class, 'confirm'])
            ->middleware('throttle:6,1')
            ->name('heal_doctor_google_confirm');
        Route::get('connect/{doctor}', [HealDoctorGoogleCalendarController::class, 'connect'])->name('heal_doctor_google_connect');
        Route::post('disconnect/{doctor}', [HealDoctorGoogleCalendarController::class, 'disconnect'])->name('heal_doctor_google_disconnect');
    });

    Route::post('patients/search', 'HealPatientController@searchPatient')->name('heal_patients_search');

    Route::post('allergies/store', 'HealAllergyPatientController@store')->name('heal_allergies_store');
    Route::delete('allergies/{id}/destroy', 'HealAllergyPatientController@destroy')->name('heal_allergies_destroy');
    Route::get('patients/vitals/last/{id}', [HealHistoryController::class, 'lastVitalSigns'])->name('heal_patients_vitals_last');

    Route::get('attentions', [HealAttentionController::class, 'index'])->name('heal_attentions_list');
    Route::get('attentions/create', [HealAttentionController::class, 'create'])->name('heal_attentions_create');
    Route::get('attentions/patients/{patient}/summary', [HealAttentionController::class, 'patientSummary'])->name('heal_attentions_patient_summary');
    Route::get('attentions/cie10/search', [HealAttentionController::class, 'searchCie10'])->name('heal_attentions_cie10_search');
    Route::post('attentions/{id}/sign', [HealAttentionController::class, 'sign'])->name('heal_attentions_sign');
    Route::post('doctor/signature-pin', [HealAttentionController::class, 'updateDoctorPin'])->name('heal_doctors_pin_update');
    Route::post('doctor/{doctor}/access-reset', [HealAttentionController::class, 'sendDoctorAccessReset'])->name('heal_doctors_access_reset');
    Route::post('attentions/store', [HealAttentionController::class, 'store'])->name('heal_attentions_store');
    Route::get('attentions/{id}/edit', [HealAttentionController::class, 'edit'])->name('heal_attentions_edit');
    Route::put('attentions/{id}/update', [HealAttentionController::class, 'update'])->name('heal_attentions_update');
    Route::delete('attentions/{id}/destroy', [HealAttentionController::class, 'destroy'])->name('heal_attentions_destroy');

    // Cobros y catálogo de procedimientos. El permiso es el mismo que exige el
    // menú de la pantalla (heal_atenciones_listado): antes la ruta quedaba solo
    // con auth+verified y cualquier cuenta entraba escribiendo la URL.
    Route::middleware(['permission:heal_atenciones_listado'])->group(function () {
        Route::get('procedures-charges', [HealProcedureChargeController::class, 'index'])->name('heal_procedure_charges_list');
        Route::get('procedures-charges/patients/{patient}/attentions', [HealProcedureChargeController::class, 'patientAttentions'])->name('heal_procedure_charges_patient_attentions');
        Route::post('charges/store', [HealProcedureChargeController::class, 'storeCharge'])->name('heal_patient_charges_store');
        Route::post('charges/store-many', [HealProcedureChargeController::class, 'storeManyCharges'])->name('heal_patient_charges_store_many');
        Route::put('charges/{charge}/status', [HealProcedureChargeController::class, 'updateChargeStatus'])->name('heal_patient_charges_status');
        Route::put('charges/{charge}/release', [HealProcedureChargeController::class, 'releaseCharge'])->name('heal_patient_charges_release');
        Route::post('charges/prepare-sales-review', [HealProcedureChargeController::class, 'prepareSalesReview'])->name('heal_patient_charges_prepare_sales');

        // El catálogo cambia precios: solo quien administra la configuración del
        // módulo, no cualquier usuario con acceso a la lista de cobros.
        Route::middleware(['permission:heal_configuracion'])->group(function () {
            Route::post('procedures/store', [HealProcedureChargeController::class, 'storeProcedure'])->name('heal_procedures_store');
            Route::put('procedures/{procedure}/update', [HealProcedureChargeController::class, 'updateProcedure'])->name('heal_procedures_update');
        });
    });

    Route::middleware(['role:admin|Administrador|webAdmin'])->prefix('admin')->group(function () {
        Route::get('activities', [HealActivityController::class, 'index'])->name('heal_activities_list');
        Route::get('activities/{id}', [HealActivityController::class, 'show'])->name('heal_activities_show');

        // Settings / Configuration
        Route::get('settings', [HealSettingController::class, 'index'])->name('heal_settings');
        Route::post('settings/update', [HealSettingController::class, 'update'])->name('heal_settings_update');
        Route::post('settings/upload-logo', [HealSettingController::class, 'uploadLogo'])->name('heal_settings_upload_logo');
        Route::post('settings/delete-logo', [HealSettingController::class, 'deleteLogo'])->name('heal_settings_delete_logo');
        Route::post('parametros/google-calendar', [HealParametersController::class, 'updateGoogleOptions'])->name('heal_parameters_google_options');
        Route::get('test-data/status', [HealTestDataController::class, 'status'])->name('heal_test_data_status');
        Route::post('test-data/generate', [HealTestDataController::class, 'store'])->name('heal_test_data_generate');
        Route::delete('test-data/destroy', [HealTestDataController::class, 'destroy'])->name('heal_test_data_destroy');

    });

    // PDF Reports (outside admin middleware — accessible to doctors)
    Route::get('attentions/{id}/pdf', [HealAttentionPdfController::class, 'download'])->name('heal_attentions_pdf');
    Route::get('attentions/{id}/prescription', [HealAttentionPdfController::class, 'prescription'])->name('heal_attentions_prescription');
});
