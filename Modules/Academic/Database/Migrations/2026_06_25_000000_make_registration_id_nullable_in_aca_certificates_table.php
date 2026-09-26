<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // En sqlite no se puede dropear una FK por nombre (la suite de tests
        // corre con DB_FOREIGN_KEYS=false, asi que basta con el change()).
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('aca_certificates', function (Blueprint $table) {
                $table->unsignedBigInteger('registration_id')->nullable()->change();
            });

            return;
        }

        Schema::table('aca_certificates', function (Blueprint $table) {
            $table->dropForeign('reg_cer_fk');
            $table->unsignedBigInteger('registration_id')->nullable()->change();
            $table->foreign('registration_id', 'reg_cer_fk')
                ->references('id')
                ->on('aca_cap_registrations')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('aca_certificates', function (Blueprint $table) {
                $table->unsignedBigInteger('registration_id')->nullable(false)->change();
            });

            return;
        }

        Schema::table('aca_certificates', function (Blueprint $table) {
            $table->dropForeign('reg_cer_fk');
            $table->unsignedBigInteger('registration_id')->nullable(false)->change();
            $table->foreign('registration_id', 'reg_cer_fk')
                ->references('id')
                ->on('aca_cap_registrations')
                ->onDelete('cascade');
        });
    }
};
