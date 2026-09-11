<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CrmContactsController;
use Modules\CRM\Http\Controllers\CrmMessagesController;

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

// Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
//     Route::get('crm', fn (Request $request) => $request->user())->name('crm');
// });

////////apis sin hacer login para el servidor nodejs
Route::post('contacts/mass/mailing/post', [CrmContactsController::class, 'sendMassMessage'])
    ->name('crm_contacts_send_mail_post');

Route::post('chat/frequently/questions/store', [CrmMessagesController::class, 'frequentlyQuestionsStore'])
    ->name('crm_chat_frequently_questions_store');
