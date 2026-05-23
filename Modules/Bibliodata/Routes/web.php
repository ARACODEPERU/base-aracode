<?php

use Illuminate\Support\Facades\Route;
use Modules\Bibliodata\Http\Controllers\BibAuthController;
use Modules\Bibliodata\Http\Controllers\BibAuthorController;
use Modules\Bibliodata\Http\Controllers\BibBookController;
use Modules\Bibliodata\Http\Controllers\BibBookPageController;
use Modules\Bibliodata\Http\Controllers\BibBookSectionController;
use Modules\Bibliodata\Http\Controllers\BibCategoryController;
use Modules\Bibliodata\Http\Controllers\BibReaderController;
use Modules\Bibliodata\Http\Controllers\BibOrganizationController;
use Modules\Bibliodata\Http\Controllers\BibOrganizationMemberController;
use Modules\Bibliodata\Http\Controllers\BibOrganizationSearchController;
use Modules\Bibliodata\Http\Controllers\BibSubscriptionController;
use Modules\Bibliodata\Http\Controllers\BibSubscriptionPlanController;
use Modules\Bibliodata\Http\Controllers\BibTagController;
use Modules\Bibliodata\Http\Controllers\BibliodataController;

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

Route::middleware('guest')->prefix('biblioteca')->group(function () {
    Route::get('login', [BibAuthController::class, 'create'])->name('bib_login');
    Route::post('login', [BibAuthController::class, 'store'])->name('bib_login_store');
});

Route::middleware(['auth', 'role:Lector'])->prefix('biblioteca')->group(function () {
    Route::get('/', [BibReaderController::class, 'index'])->name('bib_reader_home');
    Route::get('libro/secciones/{sectionId}/paginas', [BibReaderController::class, 'sectionPages'])
        ->name('bib_reader_section_pages');
    Route::get('paginas/{id}', [BibReaderController::class, 'showPage'])
        ->whereNumber('id')
        ->name('bib_reader_page_show');
    Route::post('logout', [BibAuthController::class, 'destroy'])->name('bib_logout');
});

Route::middleware(['auth', 'verified'])->prefix('bibliodata')->group(function () {

    Route::get('dashboard', [BibliodataController::class, 'index'])
        ->name('bib_dashboard');

    Route::middleware(['middleware' => 'permission:bib_categorias_listar'])
        ->get('categories', [BibCategoryController::class, 'index'])
        ->name('bib_categories');

    Route::middleware(['middleware' => 'permission:bib_categorias_nuevo'])
        ->post('categories/store', [BibCategoryController::class, 'store'])
        ->name('bib_categories_store_update');

    Route::middleware(['middleware' => 'permission:bib_categorias_eliminar'])
        ->delete('categories/destroy/{id}', [BibCategoryController::class, 'destroy'])
        ->name('bib_categories_destroy');

    Route::middleware(['middleware' => 'permission:bib_tags_listar'])
        ->get('tags', [BibTagController::class, 'index'])
        ->name('bib_tags');

    Route::middleware(['middleware' => 'permission:bib_tags_nuevo'])
        ->post('tags/store', [BibTagController::class, 'store'])
        ->name('bib_tags_store_update');

    Route::middleware(['middleware' => 'permission:bib_tags_eliminar'])
        ->delete('tags/destroy/{id}', [BibTagController::class, 'destroy'])
        ->name('bib_tags_destroy');

    Route::middleware(['middleware' => 'permission:bib_autores_listar'])
        ->get('authors', [BibAuthorController::class, 'index'])
        ->name('bib_authors');

    Route::middleware(['middleware' => 'permission:bib_autores_nuevo'])
        ->get('authors/create', [BibAuthorController::class, 'create'])
        ->name('bib_authors_create');

    Route::middleware(['middleware' => 'permission:bib_autores_nuevo'])
        ->post('authors/store', [BibAuthorController::class, 'store'])
        ->name('bib_authors_store');

    Route::middleware(['middleware' => 'permission:bib_autores_editar'])
        ->get('authors/edit/{id}', [BibAuthorController::class, 'edit'])
        ->name('bib_authors_edit');

    Route::post('authors/update', [BibAuthorController::class, 'update'])
        ->name('bib_authors_update');

    Route::middleware(['middleware' => 'permission:bib_autores_eliminar'])
        ->delete('authors/destroy/{id}', [BibAuthorController::class, 'destroy'])
        ->name('bib_authors_destroy');

    Route::middleware(['middleware' => 'permission:bib_libros_listado'])
        ->get('books', [BibBookController::class, 'index'])
        ->name('bib_books');

    Route::middleware(['middleware' => 'permission:bib_libros_nuevo'])
        ->get('books/create', [BibBookController::class, 'create'])
        ->name('bib_books_create');

    Route::post('books/store', [BibBookController::class, 'store'])
        ->name('bib_books_store');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->get('books/edit/{id}', [BibBookController::class, 'edit'])
        ->name('bib_books_edit');

    Route::post('books/update', [BibBookController::class, 'update'])
        ->name('bib_books_update');

    Route::middleware(['middleware' => 'permission:bib_libros_eliminar'])
        ->delete('books/destroy/{id}', [BibBookController::class, 'destroy'])
        ->name('bib_books_destroy');

    Route::post('books/upload-image', [BibBookController::class, 'uploadImage'])
        ->name('bib_books_upload_image');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->get('books/{id}/content', [BibBookController::class, 'content'])
        ->name('bib_books_content');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->get('books/{bookId}/sections/tree', [BibBookSectionController::class, 'tree'])
        ->name('bib_books_sections_tree');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->post('books/sections', [BibBookSectionController::class, 'store'])
        ->name('bib_books_sections_store');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->patch('books/sections/{id}', [BibBookSectionController::class, 'update'])
        ->name('bib_books_sections_update');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->delete('books/sections/{id}', [BibBookSectionController::class, 'destroy'])
        ->name('bib_books_sections_destroy');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->get('books/sections/{sectionId}/pages', [BibBookPageController::class, 'index'])
        ->name('bib_books_pages_index');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->get('books/sections/{sectionId}/pages/navigate', [BibBookPageController::class, 'navigate'])
        ->name('bib_books_pages_navigate');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->post('books/sections/{sectionId}/pages', [BibBookPageController::class, 'store'])
        ->name('bib_books_pages_store');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->post('books/sections/{sectionId}/pages/bulk', [BibBookPageController::class, 'bulkStore'])
        ->name('bib_books_pages_bulk');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->get('books/pages/{id}', [BibBookPageController::class, 'show'])
        ->name('bib_books_pages_show');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->patch('books/pages/{id}', [BibBookPageController::class, 'update'])
        ->name('bib_books_pages_update');

    Route::middleware(['middleware' => 'permission:bib_libros_editar'])
        ->delete('books/pages/{id}', [BibBookPageController::class, 'destroy'])
        ->name('bib_books_pages_destroy');

    Route::middleware(['middleware' => 'permission:bib_planes_listar'])
        ->get('subscription-plans', [BibSubscriptionPlanController::class, 'index'])
        ->name('bib_subscription_plans');

    Route::middleware(['middleware' => 'permission:bib_planes_nuevo'])
        ->get('subscription-plans/create', [BibSubscriptionPlanController::class, 'create'])
        ->name('bib_subscription_plans_create');

    Route::middleware(['middleware' => 'permission:bib_planes_nuevo'])
        ->post('subscription-plans/store', [BibSubscriptionPlanController::class, 'store'])
        ->name('bib_subscription_plans_store');

    Route::middleware(['middleware' => 'permission:bib_planes_editar'])
        ->get('subscription-plans/edit/{id}', [BibSubscriptionPlanController::class, 'edit'])
        ->name('bib_subscription_plans_edit');

    Route::middleware(['middleware' => 'permission:bib_planes_editar'])
        ->post('subscription-plans/update', [BibSubscriptionPlanController::class, 'update'])
        ->name('bib_subscription_plans_update');

    Route::middleware(['middleware' => 'permission:bib_planes_eliminar'])
        ->delete('subscription-plans/destroy/{id}', [BibSubscriptionPlanController::class, 'destroy'])
        ->name('bib_subscription_plans_destroy');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_listar'])
        ->get('organizations', [BibOrganizationController::class, 'index'])
        ->name('bib_organizations');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo'])
        ->get('organizations/create', [BibOrganizationController::class, 'create'])
        ->name('bib_organizations_create');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo'])
        ->post('organizations/store', [BibOrganizationController::class, 'store'])
        ->name('bib_organizations_store');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_editar'])
        ->get('organizations/edit/{id}', [BibOrganizationController::class, 'edit'])
        ->name('bib_organizations_edit');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_editar'])
        ->post('organizations/update', [BibOrganizationController::class, 'update'])
        ->name('bib_organizations_update');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_eliminar'])
        ->delete('organizations/destroy/{id}', [BibOrganizationController::class, 'destroy'])
        ->name('bib_organizations_destroy');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/search/list', [BibOrganizationSearchController::class, 'searchList'])
        ->name('bib_organizations_search_list');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/search/internal', [BibOrganizationSearchController::class, 'searchInternal'])
        ->name('bib_organizations_search_internal');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/search/sunat', [BibOrganizationSearchController::class, 'searchSunat'])
        ->name('bib_organizations_search_sunat');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/search/save-person', [BibOrganizationSearchController::class, 'savePerson'])
        ->name('bib_organizations_search_save_person');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/members/search', [BibOrganizationMemberController::class, 'search'])
        ->name('bib_organizations_members_search');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/members/search/reniec', [BibOrganizationMemberController::class, 'searchReniec'])
        ->name('bib_organizations_members_reniec');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->get('organizations/members/{id}', [BibOrganizationMemberController::class, 'show'])
        ->name('bib_organizations_members_show');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->post('organizations/members/store', [BibOrganizationMemberController::class, 'store'])
        ->name('bib_organizations_members_store');

    Route::middleware(['middleware' => 'permission:bib_organizaciones_nuevo|bib_organizaciones_editar'])
        ->put('organizations/members/{id}', [BibOrganizationMemberController::class, 'update'])
        ->name('bib_organizations_members_update');

    Route::middleware(['middleware' => 'permission:bib_suscripciones_listar'])
        ->get('subscriptions', [BibSubscriptionController::class, 'index'])
        ->name('bib_subscriptions');

    Route::middleware(['middleware' => 'permission:bib_suscripciones_nuevo'])
        ->get('subscriptions/create', [BibSubscriptionController::class, 'create'])
        ->name('bib_subscriptions_create');

    Route::middleware(['middleware' => 'permission:bib_suscripciones_nuevo'])
        ->post('subscriptions/store', [BibSubscriptionController::class, 'store'])
        ->name('bib_subscriptions_store');

    Route::middleware(['middleware' => 'permission:bib_suscripciones_editar'])
        ->get('subscriptions/edit/{id}', [BibSubscriptionController::class, 'edit'])
        ->name('bib_subscriptions_edit');

    Route::middleware(['middleware' => 'permission:bib_suscripciones_editar'])
        ->post('subscriptions/update', [BibSubscriptionController::class, 'update'])
        ->name('bib_subscriptions_update');

    Route::middleware(['middleware' => 'permission:bib_suscripciones_cancelar'])
        ->post('subscriptions/cancel/{id}', [BibSubscriptionController::class, 'cancel'])
        ->name('bib_subscriptions_cancel');
});
