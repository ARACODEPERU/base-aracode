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
use Modules\Socialevents\Http\Controllers\Api\EditionPublicController;
use Modules\Socialevents\Http\Controllers\Api\TeamAdminController;
use Modules\Socialevents\Http\Middleware\EnsureSocialeventsAdmin;

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
	Route::prefix('v1')->group(function () {
		// Información del evento y edición
		Route::get('events', [EventApiController::class, 'getActiveEvents'])
			->name('events.active');

		Route::get('event/current', [EventApiController::class, 'getCurrentEvent'])
			->name('event.current');

		Route::get('event/{id}', [EventApiController::class, 'getEventById'])
			->name('event.show');

		Route::get('edition/current', [EventApiController::class, 'getCurrentEdition'])
			->name('edition.current');

		Route::get('edition/{editionId}/public', [EditionPublicController::class, 'show'])
			->name('edition.public');

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

		// Administración (solo admins)
		Route::middleware([EnsureSocialeventsAdmin::class])->prefix('admin')->group(function () {
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

			// Equipos y jugadores (admin móvil)
			Route::get('teams/catalog', [TeamAdminController::class, 'catalog'])
				->name('admin.teams.catalog');

			Route::post('teams', [TeamAdminController::class, 'storeTeam'])
				->name('admin.teams.store');

			Route::get('editions/{editionId}/teams', [TeamAdminController::class, 'editionTeams'])
				->name('admin.editions.teams');

			Route::post('editions/{editionId}/teams', [TeamAdminController::class, 'assignTeam'])
				->name('admin.editions.teams.assign');

			Route::delete('editions/{editionId}/teams/{teamId}', [TeamAdminController::class, 'unassignTeam'])
				->name('admin.editions.teams.unassign');

			Route::get('editions/{editionId}/teams/{teamId}/players', [TeamAdminController::class, 'players'])
				->name('admin.editions.teams.players');

			Route::post('editions/{editionId}/teams/{teamId}/players/link', [TeamAdminController::class, 'linkPlayer'])
				->name('admin.editions.teams.players.link');

			Route::post('editions/{editionId}/teams/{teamId}/players', [TeamAdminController::class, 'createPlayer'])
				->name('admin.editions.teams.players.create');

			Route::put('editions/{editionId}/teams/{teamId}/players/{personId}', [TeamAdminController::class, 'updatePlayer'])
				->name('admin.editions.teams.players.update');

			Route::post('editions/{editionId}/teams/{teamId}/players/{personId}/photo', [TeamAdminController::class, 'uploadPlayerPhoto'])
				->name('admin.editions.teams.players.photo');

			Route::delete('editions/{editionId}/teams/{teamId}/players/{personId}', [TeamAdminController::class, 'deletePlayer'])
				->name('admin.editions.teams.players.delete');

			Route::post('persons/search', [TeamAdminController::class, 'searchPerson'])
				->name('admin.persons.search');

			Route::post('persons', [TeamAdminController::class, 'savePerson'])
				->name('admin.persons.store');

			Route::get('ubigeo', [TeamAdminController::class, 'ubigeo'])
				->name('admin.ubigeo');
		});
	});
});
