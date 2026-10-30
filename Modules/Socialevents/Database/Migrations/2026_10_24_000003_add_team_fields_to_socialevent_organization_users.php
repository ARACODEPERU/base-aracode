<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socialevent_organization_users', function (Blueprint $table) {
            if (! Schema::hasColumn('socialevent_organization_users', 'profile_key')) {
                $table->string('profile_key', 60)->nullable()->after('role');
            }
            if (! Schema::hasColumn('socialevent_organization_users', 'capabilities')) {
                $table->json('capabilities')->nullable()->after('profile_key');
            }
            if (! Schema::hasColumn('socialevent_organization_users', 'status')) {
                $table->string('status', 20)->default('active')->after('capabilities');
            }
            if (! Schema::hasColumn('socialevent_organization_users', 'permissions_updated_at')) {
                $table->timestamp('permissions_updated_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('socialevent_organization_users', 'invited_by')) {
                $table->foreignId('invited_by')->nullable()->after('permissions_updated_at')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Los miembros actuales conservan el acceso completo que ya tenían.
        DB::table('socialevent_organization_users')->update([
            'profile_key' => 'full',
            'capabilities' => json_encode(['full']),
            'status' => 'active',
        ]);
    }

    public function down(): void
    {
        Schema::table('socialevent_organization_users', function (Blueprint $table) {
            if (Schema::hasColumn('socialevent_organization_users', 'invited_by')) {
                $table->dropConstrainedForeignId('invited_by');
            }
            foreach (['permissions_updated_at', 'status', 'capabilities', 'profile_key'] as $column) {
                if (Schema::hasColumn('socialevent_organization_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
