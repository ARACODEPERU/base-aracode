<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('socialevent_organization_settings', 'alert_settings')) {
            Schema::table('socialevent_organization_settings', function (Blueprint $table) {
                $table->json('alert_settings')->nullable();
            });
        }
        Schema::table('socialevent_organizations', function (Blueprint $table) {
            foreach ([
                'deleted_at' => fn (Blueprint $table) => $table->timestamp('deleted_at')->nullable(),
                'tipo_organizacion' => fn (Blueprint $table) => $table->string('tipo_organizacion', 30)->nullable(),
                'ruc' => fn (Blueprint $table) => $table->string('ruc', 11)->nullable(),
                'address' => fn (Blueprint $table) => $table->string('address')->nullable(),
                'ubigeo' => fn (Blueprint $table) => $table->string('ubigeo', 6)->nullable(),
            ] as $column => $add) {
                if (! Schema::hasColumn('socialevent_organizations', $column)) $add($table);
            }
        });

        if (! Schema::hasTable('socialevent_organization_alerts')) {
            Schema::create('socialevent_organization_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 30);
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at', 'created_at'], 'socialevent_org_alert_user_read_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_alerts');
        Schema::table('socialevent_organizations', function (Blueprint $table) {
            foreach (['deleted_at', 'tipo_organizacion', 'ruc', 'address', 'ubigeo'] as $column) {
                if (Schema::hasColumn('socialevent_organizations', $column)) $table->dropColumn($column);
            }
        });
        if (Schema::hasColumn('socialevent_organization_settings', 'alert_settings')) {
            Schema::table('socialevent_organization_settings', function (Blueprint $table) {
                $table->dropColumn('alert_settings');
            });
        }
    }
};
