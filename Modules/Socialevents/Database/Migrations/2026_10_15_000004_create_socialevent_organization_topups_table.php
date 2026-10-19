<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialevent_organization_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('external_reference')->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('provider_payment_id', 80)->nullable()->unique();
            $table->string('payment_method', 30);
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 12, 2);
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_topups');
    }
};
