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

use Illuminate\Support\Facades\Route;
use Modules\Onlineshop\Http\Controllers\OnliSaleController;

Route::middleware(['auth', 'verified'])->prefix('onlineshop')->group(function () {
    Route::middleware(['middleware' => 'permission:onli_dashboard'])->get('dashboard', 'OnlineshopController@index')->name('onlineshop_dashboard');
    Route::middleware(['middleware' => 'permission:onli_items'])->get('items', 'OnliItemController@index')->name('onlineshop_items');
    Route::middleware(['middleware' => 'permission:onli_items_nuevo'])->get('items/create', 'OnliItemController@create')->name('onlineshop_items_create');
    Route::middleware(['middleware' => 'permission:onli_items_nuevo'])->post('items/store', 'OnliItemController@store')->name('onlineshop_items_store');
    Route::middleware(['middleware' => 'permission:onli_items_editar'])->get('items/{id}/edit', 'OnliItemController@edit')->name('onlineshop_items_edit');
    Route::middleware(['middleware' => 'permission:onli_items_editar'])->post('items/update', 'OnliItemController@update')->name('onlineshop_items_update');
    Route::middleware(['middleware' => 'permission:onli_items_eliminar'])->delete('items/destroy/{id}', 'OnliItemController@destroy')->name('onlineshop_items_destroy');
    Route::middleware(['middleware' => 'permission:onli_pedidos'])->get('sales', 'OnliSaleController@index')->name('onlineshop_sales');
    Route::middleware(['middleware' => 'permission:onli_pedidos'])->post('items/images', 'OnliItemImageController@upload')->name('onlineshop_items_images_upload');
    Route::middleware(['middleware' => 'permission:onli_pedidos'])->delete('items/images/destroy/{id}', 'OnliItemImageController@destroy')->name('onlineshop_items_images_destroy');
    Route::get('sales/shoppingcart/{mo}/pay', [OnliSaleController::class, 'shoppingCart'])->name('onlineshop_sales_shoppingcart');
    Route::post('sales/shoppingcart/mercadopago/pay', [OnliSaleController::class, 'formMercadopago'])->name('onlineshop_sales_formmercadopago');
});

Route::get('mercadopago/preference/{id}', 'OnliSaleController@getPreference')->name('onlineshop_mercadopago_preference');
Route::post('client/account/store', 'OnliSaleController@store')->name('onlineshop_client_account_store');
Route::post('get/item', 'OnliItemController@getItemCarrito')->name('onlineshop_get_item_carrito');
