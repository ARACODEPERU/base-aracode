<?php

use Illuminate\Support\Facades\Route;
use Modules\Socialevents\Http\Controllers\EvenCategoryController;
use Modules\Socialevents\Http\Controllers\EvenEventController;
use Modules\Socialevents\Http\Controllers\EvenEventTickeClientController;
use Modules\Socialevents\Http\Controllers\EvenEventTickePriceController;
use Modules\Socialevents\Http\Controllers\EvenLocalController;
use Modules\Socialevents\Http\Controllers\EventEditionController;
use Modules\Socialevents\Http\Controllers\EventEditionMatchController;
use Modules\Socialevents\Http\Controllers\EventEditionTeamController;
use Modules\Socialevents\Http\Controllers\EventEditionTeamPlayerController;
use Modules\Socialevents\Http\Controllers\EventTeamController;

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

Route::middleware(['auth', 'verified'])->prefix('socialevents')->group(function () {
    Route::get('dashboard', 'SocialeventsController@index')->name('even_dashboard');

    Route::middleware(['middleware' => 'permission:even_categoria_listado'])->get('categories', [EvenCategoryController::class, 'index'])->name('even_categories_list');
    Route::middleware(['middleware' => 'permission:even_categoria_nuevo'])->get('categories/create', [EvenCategoryController::class, 'create'])->name('even_categoriess_create');
    Route::post('categories/store', [EvenCategoryController::class, 'store'])->name('even_categories_store');
    Route::middleware(['middleware' => 'permission:even_categoria_editar'])->get('categories/{id}/edit', [EvenCategoryController::class, 'edit'])->name('even_categories_editar');
    Route::post('categories/update', [EvenCategoryController::class, 'update'])->name('even_categories_update');
    Route::middleware(['middleware' => 'permission:even_categoria_eliminar'])->delete('categories/destroy/{id}', [EvenCategoryController::class, 'destroy'])->name('even_categories_destroy');

    Route::middleware(['middleware' => 'permission:even_local_listado'])->get('locales', [EvenLocalController::class, 'index'])->name('even_local_list');
    Route::middleware(['middleware' => 'permission:even_local_nuevo'])->get('locales/create', [EvenLocalController::class, 'create'])->name('even_local_create');
    Route::post('locales/store', [EvenLocalController::class, 'store'])->name('even_local_store');
    Route::middleware(['middleware' => 'permission:even_local_editar'])->get('locales/{id}/edit', [EvenLocalController::class, 'edit'])->name('even_local_editar');
    Route::post('locales/update', [EvenLocalController::class, 'update'])->name('even_local_update');
    Route::middleware(['middleware' => 'permission:even_local_eliminar'])->delete('locales/destroy/{id}', [EvenLocalController::class, 'destroy'])->name('even_local_destroy');

    Route::middleware(['middleware' => 'permission:even_evento_listado'])->get('events', [EvenEventController::class, 'index'])->name('even_eventos_list');
    Route::middleware(['middleware' => 'permission:even_evento_nuevo'])->get('events/create', [EvenEventController::class, 'create'])->name('even_eventos_create');
    Route::post('events/store', [EvenEventController::class, 'store'])->name('even_events_store');
    Route::middleware(['middleware' => 'permission:even_evento_editar'])->get('events/{id}/edit', [EvenEventController::class, 'edit'])->name('even_eventos_editar');
    Route::post('events/update', [EvenEventController::class, 'update'])->name('even_eventos_update');
    Route::middleware(['middleware' => 'permission:even_evento_eliminar'])->delete('events/destroy/{id}', [EvenEventController::class, 'destroy'])->name('even_eventos_destroy');
    Route::post('events/prices/tickets/store', [EvenEventTickePriceController::class, 'store'])->name('even_events_preices_ticket_store');
    Route::middleware(['middleware' => 'permission:even_ventas_listado'])->get('tickets', [EvenEventTickeClientController::class, 'index'])->name('even_tickets_listado');
    Route::middleware(['middleware' => 'permission:even_equipos_listado'])->get('teams', [EventTeamController::class, 'index'])->name('even_equipos_listado');
    Route::middleware(['middleware' => 'permission:even_equipos_nuevo'])->get('teams/create', [EventTeamController::class, 'create'])->name('even_equipos_create');
    Route::middleware(['middleware' => 'permission:even_equipos_nuevo'])->post('teams/store', [EventTeamController::class, 'store'])->name('even_equipos_store');
    Route::middleware(['middleware' => 'permission:even_equipos_editar'])->get('teams/{id}/edit', [EventTeamController::class, 'edit'])->name('even_equipos_edit');
    Route::middleware(['middleware' => 'permission:even_equipos_nuevo'])->post('teams/update', [EventTeamController::class, 'update'])->name('even_equipos_update');
    Route::middleware(['middleware' => 'permission:even_equipos_nuevo'])->delete('teams/{id}/destroy', [EventTeamController::class, 'destroy'])->name('even_equipos_destroy');
    Route::middleware(['middleware' => 'permission:even_ediciones_listado'])->get('editions', [EventEditionController::class, 'index'])->name('even_ediciones_listado');
    Route::middleware(['middleware' => 'permission:even_ediciones_nuevo'])->get('editions/create', [EventEditionController::class, 'create'])->name('even_ediciones_nuevo');
    Route::middleware(['middleware' => 'permission:even_ediciones_nuevo'])->post('editions/store', [EventEditionController::class, 'store'])->name('even_ediciones_store');
    Route::middleware(['middleware' => 'permission:even_ediciones_editar'])->get('editions/{id}/edit', [EventEditionController::class, 'edit'])->name('even_ediciones_editar');
    Route::middleware(['middleware' => 'permission:even_ediciones_editar'])->post('editions/update', [EventEditionController::class, 'update'])->name('even_ediciones_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_eliminar'])->delete('editions/{id}/destroy', [EventEditionController::class, 'destroy'])->name('even_ediciones_destroy');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipos'])->get('editions/{id}/teams', [EventEditionTeamController::class, 'index'])->name('even_ediciones_equipos');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipos'])->post('editions/{id}/teams/add', [EventEditionTeamController::class, 'store'])->name('even_ediciones_equipos_agregar');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipos_eliminar'])->delete('editions/{eId}/teams/{tId}/destroy', [EventEditionTeamController::class, 'destroy'])->name('even_ediciones_equipos_destroy');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipo_jugadores'])->get('editions/{eId}/teams/{tId}/payers', [EventEditionTeamPlayerController::class, 'teamPlayersCreate'])->name('even_ediciones_equipo_jugadores');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipo_jugadores'])->post('editions/teams/player/store', [EventEditionTeamPlayerController::class, 'teamPlayersStore'])->name('even_team_player_store');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipo_jugadores'])->post('editions/teams/player/update', [EventEditionTeamPlayerController::class, 'teamPlayersUpdate'])->name('even_team_player_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_equipo_jugadores'])->delete('editions/{eId}/teams/{tId}/player/{pId}/destroy', [EventEditionTeamPlayerController::class, 'teamPlayersDestroy'])->name('even_ediciones_equipos_player_destroy');
    Route::middleware(['middleware' => 'permission:even_ediciones_fixtures'])->get('editions/{id}/fixtures', [EventEditionMatchController::class, 'editionFixtures'])->name('even_ediciones_fixtures');
    Route::middleware(['middleware' => 'permission:even_ediciones_fixtures'])->put('editions/{id}/fixtures/generate', [EventEditionMatchController::class, 'editionFixturesGenerate'])->name('even_ediciones_fixtures_generate');
    Route::middleware(['middleware' => 'permission:even_ediciones_fixtures'])->put('editions/fixtures/{fId}/update', [EventEditionMatchController::class, 'editionFixturesUpdate'])->name('even_ediciones_fixtures_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_fixtures_nuevo'])->get('editions/{id}/fixtures/create', [EventEditionMatchController::class, 'editionFixturesCreate'])->name('even_ediciones_fixtures_create');
    Route::middleware(['middleware' => 'permission:even_ediciones_fixtures_nuevo'])->post('editions/fixtures/store', [EventEditionMatchController::class, 'editionFixturesStore'])->name('even_ediciones_fixtures_store');
});
