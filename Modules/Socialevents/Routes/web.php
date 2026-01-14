<?php

use Illuminate\Support\Facades\Route;
use Modules\Socialevents\Http\Controllers\EvenCategoryController;
use Modules\Socialevents\Http\Controllers\EvenEventController;
use Modules\Socialevents\Http\Controllers\EvenEventTickeClientController;
use Modules\Socialevents\Http\Controllers\EvenEventTickePriceController;
use Modules\Socialevents\Http\Controllers\EvenLocalController;
use Modules\Socialevents\Http\Controllers\EventEditionAccordanceController;
use Modules\Socialevents\Http\Controllers\EventEditionController;
use Modules\Socialevents\Http\Controllers\EventEditionMatchController;
use Modules\Socialevents\Http\Controllers\EventEditionMatchReportController;
use Modules\Socialevents\Http\Controllers\EventEditionMatchSanctionController;
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
    Route::middleware(['middleware' => 'permission:even_ediciones_partido_resultado'])->post('editions/fixtures/match/score', [EventEditionMatchController::class, 'editionMatchScoreStore'])->name('even_edition_match_score_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_partido_eliminar'])->delete('editions/fixtures/match/{id}/destroy', [EventEditionMatchController::class, 'editionMatchScoreDestroy'])->name('even_edition_match_score_destroy');
    Route::middleware(['middleware' => 'permission:even_ediciones_sanciones'])->get('editions/{id}/collection/penalties', [EventEditionMatchSanctionController::class, 'collectionPenalties'])->name('even_ediciones_pago_sanciones');
    Route::middleware(['middleware' => 'permission:even_ediciones_sanciones'])->post('editions/collection/penalties/store', [EventEditionMatchSanctionController::class, 'paymentStore'])->name('even_ediciones_pago_sanciones_store');
    Route::middleware(['middleware' => 'permission:even_ediciones_partido_acta'])->post('editions/fixtures/match/report/store', [EventEditionMatchController::class, 'storeMatchReport'])->name('even_ediciones_partido_acta_store');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas'])->get('editions/{id}/reports', [EventEditionMatchReportController::class, 'editionMinutes'])->name('even_ediciones_actas_listado');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas'])->post('editions/reports/pdf', [EventEditionMatchReportController::class, 'generateTemporaryPdf'])->name('even_ediciones_actas_pdf');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas'])->post('editions/reports/update', [EventEditionMatchReportController::class, 'updateReportSolution'])->name('even_ediciones_actas_solucion_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas'])->post('editions/reports/upload/file', [EventEditionMatchReportController::class, 'updateReportFile'])->name('even_ediciones_actas_file_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas'])->post('editions/accordance/pdf', [EventEditionAccordanceController::class, 'generateAccordancePdf'])->name('even_ediciones_accordance_pdf');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas_nuevo'])->get('editions/{id}/reports/create', [EventEditionAccordanceController::class, 'create'])->name('even_ediciones_actas_crear');
    Route::middleware(['middleware' => 'permission:even_ediciones_actas_nuevo'])->post('editions/reports/accordance/store', [EventEditionAccordanceController::class, 'store'])->name('even_ediciones_accordance_store');
    Route::middleware(['middleware' => 'permission:even_ediciones_partido_acta_editar'])->get('editions/match/reports/{id}/edit', [EventEditionMatchReportController::class, 'edit'])->name('even_ediciones_pertido_actas_editar');
    Route::middleware(['middleware' => 'permission:even_ediciones_acta_editar'])->get('editions/accordance/{id}/edit', [EventEditionAccordanceController::class, 'edit'])->name('even_ediciones_actas_editar');
    Route::middleware(['middleware' => 'permission:even_ediciones_acta_editar'])->post('editions/accordance/update', [EventEditionAccordanceController::class, 'update'])->name('even_ediciones_actas_update');
    Route::middleware(['middleware' => 'permission:even_ediciones_partido_acta_editar'])->post('editions/match/accordance/update', [EventEditionMatchReportController::class, 'update'])->name('even_ediciones_match_accordance_update');
});
