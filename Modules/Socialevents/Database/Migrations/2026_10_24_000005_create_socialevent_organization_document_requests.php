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
            $table->foreignId('organization_id')
                ->constrained('socialevent_organizations', null, 'se_org_doc_req_org_fk')
                ->cascadeOnDelete();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users', null, 'se_org_doc_req_requester_fk')
                ->nullOnDelete();
            $table->string('purchase_type', 30);
            $table->unsignedBigInteger('purchase_id');
            $table->foreignId('document_type_id')
                ->constrained('sale_document_types', null, 'se_org_doc_req_doctype_fk');
            $table->string('status', 20)->default('requested');
            $table->json('billing_data')->nullable();
            $table->foreignId('sale_document_id')
                ->nullable()
                ->constrained('sale_documents', null, 'se_org_doc_req_sale_doc_fk')
                ->nullOnDelete();
            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users', null, 'se_org_doc_req_issuer_fk')
                ->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->json('delivery_channels')->nullable();
            $table->json('delivery_log')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Nombres explícitos: los automáticos superan el límite de 64
            // caracteres que MySQL impone a los identificadores.
            $table->index(['organization_id', 'status'], 'se_org_doc_requests_org_status_idx');
            $table->index(['purchase_type', 'purchase_id'], 'se_org_doc_requests_purchase_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_document_requests');
    }
};
