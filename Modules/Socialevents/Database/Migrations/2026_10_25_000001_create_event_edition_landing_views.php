<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_edition_landing_views')) {
            Schema::create('event_edition_landing_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edition_id')->constrained('event_editions')->cascadeOnDelete();
                // Solo se guarda el hash de IP + agente: identifica visitantes únicos
                // sin conservar la dirección IP completa.
                $table->char('visitor_hash', 64);
                $table->string('country', 80)->nullable();
                $table->string('country_code', 8)->nullable();
                $table->string('region', 120)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('source', 80)->default('Directo');
                $table->string('referrer_host', 255)->nullable();
                $table->text('referrer')->nullable();
                $table->string('browser', 80)->nullable();
                $table->string('browser_version', 40)->nullable();
                $table->string('platform', 80)->nullable();
                $table->string('device_type', 20)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('viewed_at');
                $table->timestamps();

                $table->index(['edition_id', 'viewed_at']);
                $table->index(['edition_id', 'visitor_hash']);
                $table->index(['edition_id', 'city']);
                $table->index(['edition_id', 'device_type']);
            });
        }

        if (! Schema::hasColumn('event_editions', 'landing_views')) {
            Schema::table('event_editions', function (Blueprint $table) {
                $table->unsignedBigInteger('landing_views')->default(0)->after('app_downloads')
                    ->comment('Cantidad de visitas registradas en la landing pública del torneo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_editions', 'landing_views')) {
            Schema::table('event_editions', function (Blueprint $table) {
                $table->dropColumn('landing_views');
            });
        }

        Schema::dropIfExists('event_edition_landing_views');
    }
};
