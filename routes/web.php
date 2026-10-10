<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InternalJobTokenController;
use App\Http\Controllers\JobOffersController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\LocalSaleController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\ParametersController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\UserController;
use App\Models\District;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Base app / panel
|--------------------------------------------------------------------------
| Rutas internas de la aplicación: dashboard y el panel autenticado.
| El sitio web público (páginas corporativas, blog, carrito y consultas
| públicas) vive ahora en routes/aracode.php, montado igual que este archivo
| (middleware "web", sin prefijo), por lo que las URLs no cambian.
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf.token');
    Route::post('internal/job-token', [InternalJobTokenController::class, 'store'])->name('internal.job_token');
    Route::resource('clients', ClientController::class);

    // Gestión de usuarios (protegida por permisos)
    Route::middleware(['permission:usuarios'])
        ->get('users', [UserController::class, 'index'])->name('users.index');
    Route::middleware(['permission:usuarios_nuevo'])
        ->get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::middleware(['permission:usuarios_nuevo'])
        ->post('users', [UserController::class, 'store'])->name('users.store');
    Route::middleware(['permission:usuarios_editar'])
        ->get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::middleware(['permission:usuarios_editar'])
        ->put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::middleware(['permission:usuarios_eliminar'])
        ->delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::resource('establishments', LocalSaleController::class);
    Route::resource('modulos', ModuloController::class);
    Route::get('modulos/permissions/{id}/add', [ModuloController::class, 'permissions'])->name('modulos_permissions');
    Route::post('modulos/permissions/store', [ModuloController::class, 'storePermissions'])->name('modulos_permissions_store');
    Route::delete('establishments/destroies/{id}', [LocalSaleController::class, 'destroy'])->name('establishment_destroies');
    Route::post('establishments/updated', [LocalSaleController::class, 'update'])->name('establishment_updated');

    Route::get(
        'inventory/product/establishment',
        [KardexController::class, 'index']
    )->name('kardex_index');

    Route::post(
        'inventory/product/sizes',
        [KardexController::class, 'kardexDeailsSises']
    )->name('kardex_sizes');

    Route::post(
        'search/person/number',
        [PersonController::class, 'searchByNumberType']
    )->name('search_person_number');

    Route::post(
        'search/person/apies',
        [PersonController::class, 'searchByNumberTypeApies']
    )->name('search_person_apies');

    Route::post(
        'save/person/update/create',
        [PersonController::class, 'saveUpdateOrCreate']
    )->name('save_person_update_create');

    Route::post(
        'search/person/full_name/number',
        [PersonController::class, 'searchByNameOrNumber']
    )->name('search_person_fullname_number');

    Route::get(
        'general/stock',
        [KardexController::class, 'generalStock']
    )->name('generalstock');

    Route::get(
        'company/show',
        [CompanyController::class, 'show']
    )->name('company_show');

    Route::post(
        'company/update_create',
        [CompanyController::class, 'updateCreate']
    )->name('company_update_create');

    Route::get(
        'company/getdata',
        [CompanyController::class, 'getdata']
    )->middleware(['auth', 'verified'])->name('datosempresa');

    Route::post(
        'company/convert_upload_certificate',
        [CompanyController::class, 'convertUploadCertificate']
    )->name('company_convert_upload_certificate');

    Route::post(
        'company/sunat/credentials',
        [CompanyController::class, 'saveSunatCredentials']
    )->name('company_save_sunat_credentials');

    Route::post(
        'company/social/networks',
        [CompanyController::class, 'saveSocialNetworks']
    )->name('company_save_social_networks');

    Route::post(
        'company/upload/images',
        [CompanyController::class, 'uploadImages']
    )->name('company_upload_images');

    Route::post(
        'user/persom/info/store',
        [PersonController::class, 'updateInfoPersonByUser']
    )->name('user_persom_info_store');

        // Ofertas Laborales (iframe configurable desde el parametro PC00001)
        Route::get('ofertas-laborales', [JobOffersController::class, 'index'])->name('job_offers');

    Route::get('parameters/list', [ParametersController::class, 'index'])->name('parameters');
    Route::get('parameters/create', [ParametersController::class, 'create'])->name('parameters_create');
    Route::post('parameters/store', [ParametersController::class, 'store'])->name('parameters_store');
    Route::get('parameters/{id}/edit', [ParametersController::class, 'edit'])->name('parameters_edit');
    Route::put('parameters/update/{id}', [ParametersController::class, 'update'])->name('parameters_update');
    Route::get('parameters/{id}/{val}/default', [ParametersController::class, 'updateDefaultValue'])->name('parameters_update_default_value_get');
    Route::post('parameters/{id}/default', [ParametersController::class, 'updateDefaultValuePost'])->name('parameters_update_default_value');
    Route::get('parameters/{id}/{val}/default_legacy', [ParametersController::class, 'updateDefaultValue'])->name('parameters_update_default_value_legacy');

    // //////////////actualizar informacion de personas
    Route::get('person/update_information', function () {
        if (!Auth::user()->hasRole('Alumno')) {
            return back();
        }

        // Si el usuario no tiene una persona vinculada, no hay nada que mostrar/actualizar.
        // Se evita pasar un "person" nulo a la vista (causa el error 500 en estos casos).
        $person = Auth::user()->person_id ? Person::find(Auth::user()->person_id) : null;
        if (!$person) {
            return redirect()->route('dashboard');
        }

        $identityDocumentTypes = DB::table('identity_document_type')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name'
            )
            ->get();

        try {
            $countries = \App\Models\Country::where('status', true)->orderBy('description')->get();

            return Inertia::render('Person/UpdateInformation', [
                'person' => $person,
                'identityDocumentTypes' => $identityDocumentTypes,
                'ubigeo' => $ubigeo,
                'countries' => $countries
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
                'trace'     => collect($e->getTrace())->take(3) // Muestra las primeras 3 líneas del fallo
            ], 500);
        }
    })->name('user-update-profile');

    Route::post(
        'person/update_information/store',
        [PersonController::class, 'updateInformationPerson']
    )->name('user-update-profile-store');

    Route::post(
        'person/birthdays',
        [PersonController::class, 'getBirthdays']
    )->name('person-birthdays');

    Route::get('calendar/index', [CalendarController::class, 'index'])->name('calendar');

    // /////////////META FACEBOOK WHATSAPP/////////////////

    Route::post('meta/whatsapp/message/send', [MetaController::class, 'sendMessageWhatsapp'])->name('meta_whatsapp_message_send');

});

require __DIR__.'/auth.php';
require __DIR__.'/system.php';
