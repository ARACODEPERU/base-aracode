<?php

use Illuminate\Http\Request;
use Modules\Blog\Http\Controllers\BlogController;

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

Route::middleware('auth:api')->get('/blog', function (Request $request) {
    return $request->user();
});

Route::prefix('blog_v1')->group(function () {
    Route::get('all', [BlogController::class, 'apiGetDataBlog']);
    Route::get('GetArticle/{url?}', [BlogController::class, 'apiGetDataArticle']);
    Route::get('GetArticlesByCategories/{id}', [BlogController::class, 'apiGetDataArticlesByCategories']);
});
