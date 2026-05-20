<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_contract_payments', function (Blueprint $table) {
            $table->decimal('balance_amount', 12, 2)->default(0)->after('total_amount');
            $table->json('document_payments')->nullable()->after('paid_at');
        });

        DB::table('commercial_contract_payments')->update([
            'balance_amount' => DB::raw('total_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('commercial_contract_payments', function (Blueprint $table) {
            $table->dropColumn(['balance_amount', 'document_payments']);
        });
    }
};
