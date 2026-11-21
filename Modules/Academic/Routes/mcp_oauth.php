<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\McpOAuthController;

/*
|--------------------------------------------------------------------------
| OAuth del servidor MCP: consentimiento
|--------------------------------------------------------------------------
|
| Estas dos rutas viven en el grupo web porque necesitan sesión y CSRF: son
| las que ve la persona cuando autoriza a un agente. El resto del flujo
| (registro de clientes, token y metadatos) está en routes/ai.php porque lo
| llama el conector, sin sesión.
|
*/

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/oauth/authorize', [McpOAuthController::class, 'authorizeForm'])
        ->name('mcp.oauth.authorize');

    Route::post('/oauth/authorize', [McpOAuthController::class, 'approve'])
        ->name('mcp.oauth.approve');
});
