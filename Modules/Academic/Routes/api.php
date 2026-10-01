<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\AcaSaleDocumentController;
use Modules\Academic\Http\Controllers\AcaStudentController;
use Modules\Academic\Http\Controllers\Api\SmsgateWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/academic', function (Request $request) {
    return $request->user();
});

Route::post('tickets/generate/student', [AcaSaleDocumentController::class, 'generateBoleta'])->name('aca_create_students_tickets');
Route::post('tickets/send/mail/student', [AcaSaleDocumentController::class, 'sendEmailBoleta'])->name('aca_send_email_student_boleta');
Route::post('students/import/excel/create', [AcaStudentController::class, 'importByCourse'])->name('aca_import_student_bycourse');
Route::post('course/invoice/send/email', [AcaSaleDocumentController::class, 'generateAndSendInvoices'])->name('academic_generate_and_send_invoices');

/*
    Webhook publico del canal SMSGate (modelo pull).

    Es publico a proposito: la aplicacion SMSGate no envia sesion ni token de
    sanctum, asi que se autoriza con el bearer del parametro del sistema
    SC-00003 (middleware smsgate.bearer). La URL que se pega en la app sale del
    parametro SC-00004 (SmsgateService::webhookUrl()).
*/
Route::middleware('smsgate.bearer')
    ->prefix('academic/smsgate')
    ->name('academic.smsgate.')
    ->group(function () {
        Route::get('webhook', [SmsgateWebhookController::class, 'pending'])
            ->name('webhook_pending');

        Route::post('webhook', [SmsgateWebhookController::class, 'handle'])
            ->name('webhook');
    });
