<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bib_subscription_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('bib_subscriptions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subscription_id', 'user_id']);
        });

        $orgSubscriptions = DB::table('bib_subscriptions')
            ->where('subscriber_type', 'organization')
            ->whereNotNull('organization_id')
            ->get(['id', 'organization_id']);

        foreach ($orgSubscriptions as $sub) {
            $memberIds = DB::table('bib_organization_users')
                ->where('organization_id', $sub->organization_id)
                ->pluck('user_id');

            foreach ($memberIds as $userId) {
                DB::table('bib_subscription_beneficiaries')->insertOrIgnore([
                    'subscription_id' => $sub->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bib_subscription_beneficiaries');
    }
};
