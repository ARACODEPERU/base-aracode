<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Socialevents\Http\Controllers\Api\EventApiController;
use Modules\Socialevents\Http\Controllers\Api\MatchesApiController;
use Modules\Socialevents\Http\Controllers\Api\StandingsApiController;
use Modules\Socialevents\Http\Controllers\Api\PlayerStatsApiController;

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
	});
});
