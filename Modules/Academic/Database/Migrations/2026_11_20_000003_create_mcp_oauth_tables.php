<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OAuth 2.1 del servidor MCP: clientes registrados dinámicamente (claude.ai,
 * Cursor...) y códigos de autorización de un solo uso con PKCE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mcp_oauth_clients')) {
            Schema::create('mcp_oauth_clients', function (Blueprint $table) {
                $table->id();
                $table->string('client_id', 64)->unique();
                $table->string('client_name', 120)->nullable();
                $table->json('redirect_uris');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mcp_oauth_codes')) {
            Schema::create('mcp_oauth_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code_hash', 64)->unique();
                $table->string('client_id', 64)->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('redirect_uri', 500);
                $table->string('code_challenge', 128);
                $table->string('scope', 200)->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_oauth_codes');
        Schema::dropIfExists('mcp_oauth_clients');
    }
};
