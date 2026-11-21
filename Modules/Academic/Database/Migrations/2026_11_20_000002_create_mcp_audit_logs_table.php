<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de las herramientas MCP del Académico: quién llamó qué, con qué
 * argumentos (resumidos), si salió bien y cuánto tardó.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mcp_audit_logs')) {
            Schema::create('mcp_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('tool', 100);
                $table->string('status', 20)->default('ok');
                $table->json('arguments')->nullable();
                $table->text('error')->nullable();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->string('ip', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_audit_logs');
    }
};
