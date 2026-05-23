<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bib_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('bib_subscription_plans')->restrictOnDelete();
            $table->enum('subscriber_type', ['individual', 'organization']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('bib_organizations')->nullOnDelete();
            $table->foreignId('book_id')->nullable()->constrained('bib_books')->nullOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'suspended'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bib_subscriptions');
    }
};
