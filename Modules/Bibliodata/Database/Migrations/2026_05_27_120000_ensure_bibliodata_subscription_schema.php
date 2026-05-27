<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repara entornos donde las migraciones de suscripciones quedaron registradas
 * sin crear las tablas (deploy incompleto, fallo parcial, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bib_subscription_plans')) {
            Schema::create('bib_subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->enum('scope_type', ['single_book', 'limited_books', 'all_books'])->default('single_book');
                $table->unsignedInteger('max_books')->nullable();
                $table->enum('duration_type', ['monthly', 'yearly', 'lifetime'])->default('monthly');
                $table->unsignedInteger('duration_value')->nullable();
                $table->boolean('includes_ai_chatbot')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bib_subscription_plan_books') && Schema::hasTable('bib_books')) {
            Schema::create('bib_subscription_plan_books', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('bib_subscription_plans')->cascadeOnDelete();
                $table->foreignId('book_id')->constrained('bib_books')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['plan_id', 'book_id']);
            });
        }

        if (! Schema::hasTable('bib_organizations')) {
            Schema::create('bib_organizations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('document_number')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (
            Schema::hasTable('bib_organizations')
            && Schema::hasColumn('bib_organizations', 'id')
            && ! Schema::hasColumn('bib_organizations', 'person_id')
            && Schema::hasTable('people')
        ) {
            Schema::table('bib_organizations', function (Blueprint $table) {
                $table->foreignId('person_id')->nullable()->after('id')->constrained('people')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('bib_organization_users') && Schema::hasTable('bib_organizations')) {
            Schema::create('bib_organization_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('bib_organizations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('role', ['owner', 'member'])->default('member');
                $table->timestamps();

                $table->unique(['organization_id', 'user_id']);
            });
        }

        if (
            ! Schema::hasTable('bib_subscriptions')
            && Schema::hasTable('bib_subscription_plans')
            && Schema::hasTable('bib_books')
        ) {
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

        if (! Schema::hasTable('bib_subscription_beneficiaries') && Schema::hasTable('bib_subscriptions')) {
            Schema::create('bib_subscription_beneficiaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_id')->constrained('bib_subscriptions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['subscription_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        // No revertir: esta migración solo repara esquema faltante.
    }
};
