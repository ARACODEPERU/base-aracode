<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\AcaSaleDocumentController;
use Modules\Academic\Http\Controllers\AcaStudentController;

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
