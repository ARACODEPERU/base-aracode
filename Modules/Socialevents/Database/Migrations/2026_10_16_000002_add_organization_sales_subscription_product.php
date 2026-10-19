<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'presentations' => fn (Blueprint $table) => $table->boolean('presentations')->default(false),
                'is_product' => fn (Blueprint $table) => $table->boolean('is_product')->default(true),
                'type_sale_affectation_id' => fn (Blueprint $table) => $table->string('type_sale_affectation_id', 10)->nullable(),
                'type_purchase_affectation_id' => fn (Blueprint $table) => $table->string('type_purchase_affectation_id', 10)->nullable(),
                'type_unit_measure_id' => fn (Blueprint $table) => $table->string('type_unit_measure_id', 10)->nullable(),
                'status' => fn (Blueprint $table) => $table->boolean('status')->default(true),
                'category_id' => fn (Blueprint $table) => $table->unsignedBigInteger('category_id')->nullable(),
                'brand_id' => fn (Blueprint $table) => $table->unsignedBigInteger('brand_id')->nullable(),
                'icbper' => fn (Blueprint $table) => $table->boolean('icbper')->default(false),
            ] as $column => $add) {
                if (! Schema::hasColumn('products', $column)) {
                    $add($table);
                }
            }
        });

        Schema::table('sale_products', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_products', 'saleProduct')) {
                $table->json('saleProduct')->nullable();
            }
            if (! Schema::hasColumn('sale_products', 'entity_name_product')) {
                $table->string('entity_name_product')->nullable();
            }
        });

        Schema::table('sale_document_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_document_items', 'entity_name_product')) {
                $table->string('entity_name_product')->nullable();
            }
        });

        Schema::table('sale_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_documents', 'status_pay')) {
                $table->boolean('status_pay')->nullable();
            }
        });

        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'ubigeo_description')) {
                $table->string('ubigeo_description')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (Schema::hasColumn('people', 'ubigeo_description')) {
                $table->dropColumn('ubigeo_description');
            }
        });
        Schema::table('sale_documents', function (Blueprint $table) {
            if (Schema::hasColumn('sale_documents', 'status_pay')) {
                $table->dropColumn('status_pay');
            }
        });
        Schema::table('sale_document_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_document_items', 'entity_name_product')) {
                $table->dropColumn('entity_name_product');
            }
        });
        Schema::table('sale_products', function (Blueprint $table) {
            foreach (['entity_name_product', 'saleProduct'] as $column) {
                if (Schema::hasColumn('sale_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('products', function (Blueprint $table) {
            foreach (['presentations', 'is_product', 'type_sale_affectation_id', 'type_purchase_affectation_id', 'type_unit_measure_id', 'status', 'category_id', 'brand_id', 'icbper'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
