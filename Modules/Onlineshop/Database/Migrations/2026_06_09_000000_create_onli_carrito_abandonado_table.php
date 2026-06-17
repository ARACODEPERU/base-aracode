<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('onli_carrito_abandonado')) {
            Schema::create('onli_carrito_abandonado', function (Blueprint $table) {
                $table->id();
                $table->string('client_id', 100)->nullable()->index();
                $table->string('phone_country', 10)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('name', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->json('cart_items')->nullable();
                $table->decimal('cart_total', 12, 2)->nullable();
                $table->timestamp('notification_sent_at')->nullable();
                $table->integer('notification_count')->default(0);
                $table->timestamps();

                $table->index('phone');
                $table->index('email');
                $table->index('created_at');
            });
        } else {
            // La tabla ya existe de una migración fallida anterior
            // Agregar índices faltantes si no existen
            $indexes = collect(DB::select('SHOW INDEXES FROM onli_carrito_abandonado'))
                ->pluck('Key_name')
                ->toArray();

            Schema::table('onli_carrito_abandonado', function (Blueprint $table) use ($indexes) {
                if (!in_array('onli_carrito_abandonado_phone_index', $indexes)) {
                    $table->index('phone');
                }
                if (!in_array('onli_carrito_abandonado_email_index', $indexes)) {
                    $table->index('email');
                }
                if (!in_array('onli_carrito_abandonado_created_at_index', $indexes)) {
                    $table->index('created_at');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('onli_carrito_abandonado');
    }
};
