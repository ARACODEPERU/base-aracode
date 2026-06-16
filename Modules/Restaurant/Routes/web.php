<?php

use Illuminate\Support\Facades\Route;
use Modules\Restaurant\Http\Controllers\ResComandaController;
use Modules\Restaurant\Http\Controllers\ResMenuComandaController;
use Modules\Restaurant\Http\Controllers\ResMenuController;
use Modules\Restaurant\Http\Controllers\ResPresentationController;
use Modules\Restaurant\Http\Controllers\ResRecipeController;
use Modules\Restaurant\Http\Controllers\ResSaleController;
use Modules\Restaurant\Http\Controllers\ResSupplyController;
use Modules\Restaurant\Http\Controllers\ResSupplyPurchaseController;
use Modules\Restaurant\Http\Controllers\RestaurantController;

Route::middleware(['auth', 'verified'])->prefix('restaurant')->group(function () {

    Route::middleware(['middleware' => 'permission:res_dashboard'])
        ->get('dashboard', [RestaurantController::class, 'index'])
        ->name('res_dashboard');

    Route::middleware(['middleware' => 'permission:res_comandas'])
        ->get('comandas', [ResComandaController::class, 'index'])
        ->name('res_comandas_list');

    Route::middleware(['middleware' => 'permission:res_comandas_nuevo'])
        ->get('comandas/create', [ResComandaController::class, 'create'])
        ->name('res_comandas_create');

    Route::middleware(['middleware' => 'permission:res_comandas_editar'])
        ->get('comandas/edit/{id}', [ResComandaController::class, 'edit'])
        ->name('res_comandas_edit');

    Route::middleware(['middleware' => 'permission:res_comandas'])
        ->get('presentations/category/{id}', [ResPresentationController::class, 'getByCategory'])
        ->name('res_presentation_by_cat_list');

    Route::middleware(['middleware' => 'permission:res_comandas_nuevo'])
        ->post('comandas/store', [ResComandaController::class, 'store'])
        ->name('res_comandas_store');

    Route::middleware(['middleware' => 'permission:res_comandas_editar'])
        ->post('comandas/update', [ResComandaController::class, 'update'])
        ->name('res_comandas_update');

    Route::middleware(['middleware' => 'permission:res_comandas_eliminar'])
        ->delete('comandas/destroy/{id}', [ResComandaController::class, 'destroy'])
        ->name('res_comandas_destroy');

    Route::middleware(['middleware' => 'permission:res_menu'])
        ->get('menus', [ResMenuController::class, 'index'])
        ->name('res_menu_list');

    Route::middleware(['middleware' => 'permission:res_menu_nuevo'])
        ->get('menus/create', [ResMenuController::class, 'create'])
        ->name('res_menu_create');

    Route::middleware(['middleware' => 'permission:res_menu_verimprimir'])
        ->get('menus/show/{id}', [ResMenuController::class, 'show'])
        ->name('res_menus_show');

    Route::middleware(['middleware' => 'permission:res_menu_editar'])
        ->get('menus/edit/{id}', [ResMenuController::class, 'edit'])
        ->name('res_menus_edit');

    Route::middleware(['middleware' => 'permission:res_menu_nuevo'])
        ->post('menus/store', [ResMenuController::class, 'store'])
        ->name('res_menus_store');

    Route::middleware(['middleware' => 'permission:res_menu_editar'])
        ->put('menus/update/{id}', [ResMenuController::class, 'update'])
        ->name('res_menus_update');

    Route::middleware(['middleware' => 'permission:res_menu_eliminar'])
        ->delete('menus/destroy/{id}', [ResMenuController::class, 'destroy'])
        ->name('res_menus_destroy');

    Route::middleware(['middleware' => 'permission:res_menu_agregar_comandas'])
        ->get('menus/comandas/all/{id}', [ResMenuComandaController::class, 'getComandas'])
        ->name('res_menus_comandas');

    Route::middleware(['middleware' => 'permission:res_menu_agregar_comandas'])
        ->put('menus/comandas/store/{id}', [ResMenuComandaController::class, 'storeComandas'])
        ->name('res_menus_comandas_store');

    Route::middleware(['middleware' => 'permission:res_venta_nuevo'])
        ->get('sales/create', [ResSaleController::class, 'create'])
        ->name('res_sales_create');

    Route::middleware(['middleware' => 'permission:res_venta'])
        ->get('sales/list', [ResSaleController::class, 'index'])
        ->name('res_sales_list');

    Route::middleware(['middleware' => 'permission:res_venta_nuevo'])
        ->post('sale/store', [ResSaleController::class, 'store'])
        ->name('res_sales_store');

    Route::middleware(['middleware' => 'permission:res_venta_editar'])
        ->get('sales/{id}/edit', [ResSaleController::class, 'edit'])
        ->name('res_sales_edit');

    Route::middleware(['middleware' => 'permission:res_venta_editar'])
        ->put('sale/update/{id}', [ResSaleController::class, 'update'])
        ->name('res_sales_update');

    Route::middleware(['middleware' => 'permission:res_venta_eliminar'])
        ->delete('sale/destroy/{id}', [ResSaleController::class, 'destroy'])
        ->name('res_sales_destroy');

    Route::middleware(['middleware' => 'permission:res_venta'])
        ->get('sales/cuisine', [ResSaleController::class, 'cuisine'])
        ->name('res_sales_cuisine');

    Route::middleware(['middleware' => 'permission:res_venta'])
        ->get('sales/find/sale/{id}', [ResSaleController::class, 'getSale'])
        ->name('res_sales_find_reset');

    Route::middleware(['middleware' => 'permission:res_venta'])
        ->patch('sales/{id}/kitchen', [ResSaleController::class, 'updateKitchenStatus'])
        ->name('res_sales_kitchen_update');

    Route::middleware(['middleware' => 'permission:res_insumos'])
        ->get('supplies', [ResSupplyController::class, 'index'])
        ->name('res_supplies_list');

    Route::middleware(['middleware' => 'permission:res_insumos_nuevo'])
        ->get('supplies/create', [ResSupplyController::class, 'create'])
        ->name('res_supplies_create');

    Route::middleware(['middleware' => 'permission:res_insumos_nuevo'])
        ->post('supplies/store', [ResSupplyController::class, 'store'])
        ->name('res_supplies_store');

    Route::middleware(['middleware' => 'permission:res_insumos_editar'])
        ->get('supplies/edit/{id}', [ResSupplyController::class, 'edit'])
        ->name('res_supplies_edit');

    Route::middleware(['middleware' => 'permission:res_insumos_editar'])
        ->post('supplies/update', [ResSupplyController::class, 'update'])
        ->name('res_supplies_update');

    Route::middleware(['middleware' => 'permission:res_insumos_editar'])
        ->delete('supplies/destroy/{id}', [ResSupplyController::class, 'destroy'])
        ->name('res_supplies_destroy');

    Route::middleware(['middleware' => 'permission:res_insumos'])
        ->get('supplies/search', [ResSupplyController::class, 'search'])
        ->name('res_supplies_search');

    Route::middleware(['middleware' => 'permission:res_lista_compras'])
        ->get('supplies/shopping-list', [ResSupplyController::class, 'shoppingList'])
        ->name('res_supplies_shopping_list');

    Route::middleware(['middleware' => 'permission:res_insumos_compra'])
        ->get('supplies/purchase', [ResSupplyPurchaseController::class, 'create'])
        ->name('res_supplies_purchase');

    Route::middleware(['middleware' => 'permission:res_insumos_compra'])
        ->post('supplies/purchase/store', [ResSupplyPurchaseController::class, 'store'])
        ->name('res_supplies_purchase_store');

    Route::middleware(['middleware' => 'permission:res_recetas'])
        ->get('comandas/{id}/recipe', [ResRecipeController::class, 'show'])
        ->name('res_comandas_recipe');

    Route::middleware(['middleware' => 'permission:res_recetas_editar'])
        ->put('comandas/{id}/recipe', [ResRecipeController::class, 'update'])
        ->name('res_comandas_recipe_update');

    Route::middleware(['middleware' => 'permission:res_recetas_editar'])
        ->post('comandas/{id}/recipe/copy', [ResRecipeController::class, 'copyFrom'])
        ->name('res_comandas_recipe_copy');

    // Boleta/factura SUNAT — fuera de alcance v1
    // Route::get('sales/document/boleta/{id}', [ResSaleController::class, 'createBoleta'])
    //     ->name('res_sales_document_boleta');
});
