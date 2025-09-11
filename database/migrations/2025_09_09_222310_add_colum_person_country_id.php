<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->default(1)->comment('id del pais al que pertenese');
        });

        Schema::table('identity_document_type', function (Blueprint $table) {
            $table->string('long_description')->nullable()->comment('descripcion completa');
            $table->string('sunat_code')->nullable()->comment('codigo de sunat');
            $table->integer('number_characters')->default(0)->comment('cuantos caracteres deve tener el numero de identificacion');
        });

        DB::table('identity_document_type')->where('id', 0)->update([
            'long_description' => 'DOC.TRIB.NO.DOM.SIN.RUC',
            'description' => 'OTROS',
            'sunat_code' => '00',
            'number_characters' => 15
        ]);
        DB::table('identity_document_type')->where('id', 1)->update([
            'long_description' => 'LIBRETA ELECTORAL O DNI',
            'description' => 'L.E / DNI',
            'sunat_code' => '01',
            'number_characters' => 8
        ]);
        DB::table('identity_document_type')->where('id', 6)->update([
            'long_description' => 'REG. UNICO DE CONTRIBUYENTES',
            'description' => 'RUC',
            'sunat_code' => '06',
            'number_characters' => 11
        ]);
        DB::table('identity_document_type')->insert([
            [
                'id' => '4',
                'long_description' => 'CARNET DE EXTRANJERIA',
                'description' => 'CARNET EXT.',
                'sunat_code' => '04',
                'number_characters' => 12
            ],
            [
                'id' => '7',
                'long_description' => 'PASAPORTE',
                'description' => 'PASAPORTE',
                'sunat_code' => '07',
                'number_characters' => 12
            ],
            [
                'id' => '11',
                'long_description' => 'PART. DE NACIMIENTO-IDENTIDAD',
                'description' => 'P. NAC.',
                'sunat_code' => '11',
                'number_characters' => 15
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
};
