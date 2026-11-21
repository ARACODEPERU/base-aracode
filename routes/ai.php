<?php

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Modules\Academic\Http\Controllers\McpOAuthController;
use Modules\Academic\Mcp\Servers\AcademicServer;

/*
|--------------------------------------------------------------------------
| Rutas de servidores MCP
|--------------------------------------------------------------------------
|
| El módulo Académico expone un servidor MCP para que agentes de IA (Claude
| Code, Claude Desktop, claude.ai web, Freebuff, Cursor...) creen cursos. Todo
| curso nace en borrador (status = false): activarlo sigue siendo una acción
| humana en el ERP, con su revisión y su permiso.
|
| Autenticación: token Bearer de Sanctum. Los clientes de escritorio usan
| `php artisan mcp:token`; los conectores web usan OAuth 2.1 con PKCE
| (authorization code + registro dinámico), que también emite tokens Sanctum.
|
*/

Mcp::web('/mcp/academic', AcademicServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);

// Metadatos OAuth: los lee el conector al conectarse (sin sesión).
Route::get('/.well-known/oauth-protected-resource', [McpOAuthController::class, 'protectedResource'])
    ->name('mcp.oauth.protected-resource');

Route::get('/.well-known/oauth-protected-resource/mcp/academic', [McpOAuthController::class, 'protectedResource'])
    ->name('mcp.oauth.protected-resource.resource');

Route::get('/.well-known/oauth-authorization-server', [McpOAuthController::class, 'authorizationServer'])
    ->name('mcp.oauth.authorization-server');

// Registro dinámico de clientes públicos (RFC 7591).
Route::post('/oauth/register', [McpOAuthController::class, 'register'])
    ->middleware('throttle:30,1')
    ->name('mcp.oauth.register');

// Canje del código por token (PKCE S256). Sin sesión: lo llama el conector.
Route::post('/oauth/token', [McpOAuthController::class, 'token'])
    ->middleware('throttle:30,1')
    ->name('mcp.oauth.token');
