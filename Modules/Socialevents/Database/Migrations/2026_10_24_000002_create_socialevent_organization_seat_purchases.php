<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialevent_organization_seat_purchases', function (Blueprint $table) {
            $table->id();
            // Los nombres de claves e índices se indican explícitamente porque los
            // generados automáticamente superan el límite de 64 caracteres de MySQL.
            $table->foreignId('organization_id')
                ->constrained('socialevent_organizations')
                ->cascadeOnDelete();
            $table->foreignId('purchased_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('external_reference')->unique('se_org_seat_purchases_ref_unique');
            $table->uuid('idempotency_key')->unique('se_org_seat_purchases_idem_unique');
            $table->string('provider_payment_id', 80)->nullable()->unique('se_org_seat_purchases_payid_unique');
            $table->string('payment_method', 30);
            $table->string('status', 30)->default('processing');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 12, 2);
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at'], 'se_org_seat_purchases_org_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_seat_purchases');
    }
};
