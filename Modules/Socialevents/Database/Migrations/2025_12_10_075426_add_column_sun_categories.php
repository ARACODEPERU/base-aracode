<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('even_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('main_category_id')->nullable()->comment('si la categoria pertenese a una categoria superior');
            $table->text('informations')->nullable()->comment('informacion corta del tipo del evento');
            $table->foreign('main_category_id')->references('id')->on('even_categories')->nullOnDelete();
        });

        DB::table('even_categories')->insert([
            ['description' => 'Deportivos', 'status' => 1, 'main_category_id' => null, 'informations' => null],
            ['description' => 'Culturales y Artísticos', 'status' => 1, 'main_category_id' => null, 'informations' => null],
            ['description' => 'Sociales y de Entretenimiento', 'status' => 1, 'main_category_id' => null, 'informations' => null],
            ['description' => 'Académicos y de Negocios', 'status' => 1, 'main_category_id' => null, 'informations' => null],
            ['description' => 'Corporativos e Internos', 'status' => 1, 'main_category_id' => null, 'informations' => null],
            ['description' => 'Torneo / Liga', 'status' => 1, 'main_category_id' => 1, 'informations' => 'Competiciones con múltiples partidos/jornadas.'],
            ['description' => 'Maratón / Carrera', 'status' => 1, 'main_category_id' => 1, 'informations' => 'Eventos de resistencia o participación masiva (5K, 10K, etc.).'],
            ['description' => 'Concierto / Festival', 'status' => 1, 'main_category_id' => 2, 'informations' => 'Actuaciones musicales, danza o grandes reuniones artísticas.'],
            ['description' => 'Obra de Teatro', 'status' => 1, 'main_category_id' => 2, 'informations' => 'Representaciones escénicas en vivo.'],
            ['description' => 'Exposición / Muestra', 'status' => 1, 'main_category_id' => 2, 'informations' => 'Presentación de arte, tecnología o productos (ej. exposición de pintura).'],
            ['description' => 'Fiesta Privada', 'status' => 1, 'main_category_id' => 3, 'informations' => 'Celebraciones específicas (cumpleaños, aniversarios, bodas).'],
            ['description' => 'Cenas de Gala', 'status' => 1, 'main_category_id' => 3, 'informations' => 'Eventos formales con cena y protocolo.'],
            ['description' => 'Evento de Networking', 'status' => 1, 'main_category_id' => 3, 'informations' => 'Reuniones informales para crear contactos profesionales.'],
            ['description' => 'Conferencia / Congreso', 'status' => 1, 'main_category_id' => 4, 'informations' => 'Grandes reuniones de expertos con ponencias y varios días de duración.'],
            ['description' => 'Seminario / Taller', 'status' => 1, 'main_category_id' => 4, 'informations' => 'Eventos educativos cortos e interactivos, enfocados en formación práctica.'],
            ['description' => 'Presentación de Producto', 'status' => 1, 'main_category_id' => 4, 'informations' => 'Lanzamiento de un nuevo servicio o tecnología.'],
            ['description' => 'Reunión de Equipo', 'status' => 1, 'main_category_id' => 5, 'informations' => 'Juntas o sesiones de trabajo dentro de una empresa.'],
            ['description' => 'Convención Anual', 'status' => 1, 'main_category_id' => 5, 'informations' => 'Sesiones de formación para empleados.'],
            ['description' => 'Capacitación', 'status' => 1, 'main_category_id' => 5, 'informations' => 'Reuniones grandes de toda la empresa o sector.'],
            ['description' => 'Fútbol Asociado (Soccer)', 'status' => 1, 'main_category_id' => 6, 'informations' => 'Fútbol 11 (Campo grande), Fútbol 7, Fútbol Sala (Futsal), Fútbol Playa, Fútbol Americano, Fútbol relampago.'],
            ['description' => 'Baloncesto (Basketball)', 'status' => 1, 'main_category_id' => 6, 'informations'=> '5v5 (Cancha completa), 3x3 (Half Court), Baloncesto en Silla de Ruedas.'],
            ['description' => 'Tenis', 'status' => 1, 'main_category_id' => 6, 'informations'=> 'Singles, Dobles, Dobles Mixtos, Tenis de Mesa (Ping Pong).'],
            ['description' => 'Voleibol', 'status' => 1, 'main_category_id' => 6, 'informations'=> 'Voleibol de Sala (6v6), Voleibol de Playa (2v2), Voleibol sentado.'],
            ['description' => 'Otros', 'status' => 1, 'main_category_id' => 6, 'informations'=> 'Béisbol, Rugby (XV/7s), Hockey sobre hielo, Atletismo (Pista y Campo).'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('even_categories', function (Blueprint $table) {
            $table->dropColumn('informations');
            $table->dropColumn('main_category_id');
        });
    }
};
