<?php

use Illuminate\Support\Facades\Route;
use Modules\Bibliodata\Http\Controllers\BibAuthorController;
use Modules\Bibliodata\Http\Controllers\BibBookController;
use Modules\Bibliodata\Http\Controllers\BibCategoryController;
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
});
