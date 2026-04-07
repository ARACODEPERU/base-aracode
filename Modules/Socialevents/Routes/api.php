<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Socialevents\Http\Controllers\Api\EventApiController;
use Modules\Socialevents\Http\Controllers\Api\MatchesApiController;
use Modules\Socialevents\Http\Controllers\Api\StandingsApiController;
use Modules\Socialevents\Http\Controllers\Api\PlayerStatsApiController;
use Modules\Socialevents\Http\Controllers\Api\TeamApiController;
use Modules\Socialevents\Http\Controllers\Api\PlayerApiController;
use Modules\Socialevents\Http\Controllers\Api\MatchAdminController;

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
Route::prefix('socialevents')->name('api.')->group(function () {
	// Rutas públicas - sin autenticación
	Route::prefix('v1')->name('api.')->group(function () {
		// Información del evento y edición
		Route::get('events', [EventApiController::class, 'getActiveEvents'])
			->name('events.active');

		Route::get('event/current', [EventApiController::class, 'getCurrentEvent'])
			->name('event.current');

		Route::get('event/{id}', [EventApiController::class, 'getEventById'])
			->name('event.show');

		Route::get('edition/current', [EventApiController::class, 'getCurrentEdition'])
			->name('edition.current');

		// Partidos
		Route::get('edition/{editionId}/matches/upcoming', [MatchesApiController::class, 'getUpcomingMatches'])
			->name('matches.upcoming');

		Route::get('edition/{editionId}/matches/results', [MatchesApiController::class, 'getRecentResults'])
			->name('matches.results');

		Route::get('edition/{editionId}/matches/all', [MatchesApiController::class, 'getAllMatches'])
			->name('matches.all');

		Route::get('edition/{editionId}/matches/{filter}', [MatchesApiController::class, 'getAllMatches'])
			->name('matches.filter');

		Route::get('edition/{editionId}/matches', [MatchesApiController::class, 'getAllMatches'])
			->name('matches.query');

		// Tabla de posiciones
		Route::get('edition/{editionId}/standings', [StandingsApiController::class, 'getStandings'])
			->name('standings.index');

		// Estadísticas de jugadores
		Route::get('edition/{editionId}/stats/players/{filter}', [PlayerStatsApiController::class, 'getAllStats'])
			->name('stats.players');
	});

	// Rutas protegidas - requieren autenticación
	Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
		Route::get('socialevents', fn (Request $request) => $request->user())->name('socialevents');

		// Equipo del usuario (delegado)
		Route::get('team/my-team', [TeamApiController::class, 'getMyTeam'])
			->name('team.my-team');

		Route::put('team/my-team', [TeamApiController::class, 'updateMyTeam'])
			->name('team.update');

		Route::post('team/my-team/logo', [TeamApiController::class, 'uploadTeamLogo'])
			->name('team.upload-logo');

		Route::get('team/check', [TeamApiController::class, 'checkMyTeam'])
			->name('team.check');

		// Jugadores del equipo
		Route::get('team/players', [PlayerApiController::class, 'getPlayers'])
			->name('team.players');

		Route::post('team/players', [PlayerApiController::class, 'createPlayer'])
			->name('team.players.create');

		Route::put('team/players/{personId}', [PlayerApiController::class, 'updatePlayer'])
			->name('team.players.update');

		Route::delete('team/players/{personId}', [PlayerApiController::class, 'deletePlayer'])
			->name('team.players.delete');

		Route::post('team/players/{personId}/photo', [PlayerApiController::class, 'uploadPlayerPhoto'])
			->name('team.players.photo');

		// Partidos del equipo
		Route::get('team/matches', [TeamApiController::class, 'getTeamMatches'])
			->name('team.matches');

		// Sanciones del equipo
		Route::get('team/sanctions', [TeamApiController::class, 'getTeamSanctions'])
			->name('team.sanctions');

		// Administración de partidos (solo admins)
		Route::prefix('admin')->group(function () {
			Route::put('matches/edition/{editionId}', [MatchAdminController::class, 'index'])
				->name('admin.matches');

			Route::post('matches', [MatchAdminController::class, 'store'])
				->name('admin.matches.create');

			Route::put('matches/{match}', [MatchAdminController::class, 'update'])
				->name('admin.matches.update');

			Route::delete('matches/{match}', [MatchAdminController::class, 'destroy'])
				->name('admin.matches.delete');

			Route::get('teams/{editionId}', [MatchAdminController::class, 'teams'])
				->name('admin.teams');

			Route::get('locations', [MatchAdminController::class, 'locations'])
				->name('admin.locations');

            Route::get('matches/{matchId}/players', [MatchAdminController::class, 'getMatchPlayers']);
            Route::put('matches/{matchId}/result', [MatchAdminController::class, 'saveMatchResult']);
            Route::put('matches/{matchId}/report', [MatchAdminController::class, 'closeMatchReport'])
                ->name('admin.matches.report');
		});
	});
});
