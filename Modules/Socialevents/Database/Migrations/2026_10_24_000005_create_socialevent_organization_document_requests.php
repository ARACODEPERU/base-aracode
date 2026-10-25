<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialevent_organization_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purchase_type', 30);
            $table->unsignedBigInteger('purchase_id');
            $table->foreignId('document_type_id')->constrained('sale_document_types');
            $table->string('status', 20)->default('requested');
            $table->json('billing_data')->nullable();
            $table->foreignId('sale_document_id')->nullable()->constrained('sale_documents')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->json('delivery_channels')->nullable();
            $table->json('delivery_log')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['purchase_type', 'purchase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_document_requests');
    }
};
