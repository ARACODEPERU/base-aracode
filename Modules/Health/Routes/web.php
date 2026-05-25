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

    Route::get('procedures-charges', [HealProcedureChargeController::class, 'index'])->name('heal_procedure_charges_list');
    Route::get('procedures-charges/patients/{patient}/attentions', [HealProcedureChargeController::class, 'patientAttentions'])->name('heal_procedure_charges_patient_attentions');
    Route::post('procedures/store', [HealProcedureChargeController::class, 'storeProcedure'])->name('heal_procedures_store');
    Route::put('procedures/{procedure}/update', [HealProcedureChargeController::class, 'updateProcedure'])->name('heal_procedures_update');
    Route::post('charges/store', [HealProcedureChargeController::class, 'storeCharge'])->name('heal_patient_charges_store');
    Route::post('charges/store-many', [HealProcedureChargeController::class, 'storeManyCharges'])->name('heal_patient_charges_store_many');
    Route::put('charges/{charge}/status', [HealProcedureChargeController::class, 'updateChargeStatus'])->name('heal_patient_charges_status');
    Route::post('charges/prepare-sales-review', [HealProcedureChargeController::class, 'prepareSalesReview'])->name('heal_patient_charges_prepare_sales');
});
