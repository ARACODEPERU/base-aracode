<?php

use Illuminate\Support\Facades\Route;
use Modules\Bibliodata\Http\Controllers\BibBookController;
use Modules\Bibliodata\Http\Controllers\BibCategoryController;
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

    Route::get('book', [BibBookController::class, 'index'])
        ->name('bib_book');

    Route::get('book/{id}/read', [BibBookController::class, 'index'])
        ->name('bib_read_book');
});
