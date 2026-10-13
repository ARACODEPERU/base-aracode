<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socialevent_organizations', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('access_until')->nullable();
        });

        Schema::create('socialevent_user_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedSmallInteger('users_quantity');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('complimentary')->default(false);
            $table->timestamps();
        });

        Schema::create('socialevent_organization_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('edition_id')->nullable()->constrained('event_editions')->nullOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('event_edition_matches')->nullOnDelete();
            $table->string('entry_type', 30);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('reference', 180)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['match_id', 'entry_type'], 'socialevent_ledger_match_entry_unique');
            $table->index(['organization_id', 'created_at']);
        });

        Schema::table('event_editions', function (Blueprint $table) {
            $table->decimal('match_rate', 10, 2)->nullable()->after('inscription_fee');
        });
    }

    public function down(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            $table->dropColumn('match_rate');
        });

        Schema::dropIfExists('socialevent_organization_ledger');
        Schema::dropIfExists('socialevent_user_packages');

        Schema::table('socialevent_organizations', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'access_until']);
        });
    }
};
