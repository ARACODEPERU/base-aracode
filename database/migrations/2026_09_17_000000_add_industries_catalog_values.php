<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Rubros que ya existian con otro texto.
     *
     * Se renombra la fila existente (no se crea otra): asi el desplegable no
     * muestra dos opciones parecidas y quien ya tenia la industria asignada
     * conserva su industry_id.
     */
    private const RENAMES = [
        'Financiera y Banca' => 'Banca y Finanzas',
        'Retail y Comercio' => 'Comercial, Retail',
        'Educación' => 'Servicios Educativos',
        'Textil y Moda' => 'Textil',
        'Transporte y Logística' => 'Transporte de Carga y Logística',
        'Turismo y Viajes' => 'Turismo y Hotelería',
        'Salud y Farmacéutica' => 'Salud',
        'Consultoría y Servicios Profesionales' => 'Consultoría Empresarial y/o Auditoría',
        'Construcción' => 'Inmobiliaria y Construcción',
        'Agricultura y Ganadería' => 'Agrícola y/o Agroexportadora',
    ];

    /**
     * Catalogo solicitado (mismo orden en que se pidio).
     *
     * Los que ya existan se saltan solos: los 4 que coinciden textualmente con
     * el catalogo original (Manufactura, Otros, Seguros y Telecomunicaciones) y
     * los 10 destinos de RENAMES, que ya quedaron con ese nombre en el paso
     * anterior.
     */
    private const DESCRIPTIONS = [
        'Aeropuertos',
        'Agrícola y/o Agroexportadora',
        'Alquiler de Maquinarias',
        'Apuestas Deportivas',
        'Asesoría Jurídica',
        'Automotriz',
        'Avícola y Ganadería',
        'Club Deportivo',
        'Comercial, Retail',
        'Consultoría Empresarial y/o Auditoría',
        'Contraloría General De La República',
        'Distribución de Agua Potable y Saneamiento',
        'Servicios Educativos',
        'Energía Eléctrica',
        'Gastronomía',
        'Turismo y Hotelería',
        'Industrial',
        'Ingeniería y Arquitectura',
        'Inmobiliaria y Construcción',
        'Manufactura',
        'Metalmecánica',
        'Metalurgia',
        'Minería',
        'Molino de Arroz',
        'ONG',
        'Otros',
        'Outsourcing Contable y/o de Planillas',
        'Pesca',
        'Publicidad y Marketing',
        'Salud',
        'Banca y Finanzas',
        'Sector Público',
        'Seguridad y Tecnología',
        'Seguros',
        'Transporte de Carga y Logística',
        'Sin Fines de Lucro',
        'Telecomunicaciones',
        'Textil',
        'Turismo',
        'Venta de Combustibles',
        'Veterinaria',
        'Bienes Raíces',
        'Mercado de Valores',
        'Notaría',
        'Petróleo y Gas',
    ];

    /**
     * Textos del catalogo pedido que ya existian tal cual (con la misma
     * descripcion) antes de esta migracion.
     *
     * Sirve para que el down() no borre filas que no creo esta migracion: sin
     * esta lista, "Manufactura", "Otros", "Seguros" y "Telecomunicaciones"
     * caerian en el borrado por coincidir con el catalogo pedido.
     */
    private const PREEXISTING = [
        'Manufactura',
        'Otros',
        'Seguros',
        'Telecomunicaciones',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Los renombrados van primero: asi, cuando toca insertar, los textos
        // nuevos ya existen y el bucle de abajo los salta sin trabajo extra.
        foreach (self::RENAMES as $anterior => $nuevo) {
            $id = $this->findId($anterior);

            // Si el texto anterior ya no existe (migracion ya corrida) o el nuevo
            // ya esta tomado por otra fila, no se toca nada: nunca se dejan dos
            // industrias con la misma descripcion.
            if (! $id || $this->findId($nuevo)) {
                continue;
            }

            DB::table('industries')->where('id', $id)->update([
                'description' => $nuevo,
                'updated_at' => now(),
            ]);
        }

        foreach (self::DESCRIPTIONS as $description) {
            if ($this->findId($description)) {
                continue;
            }

            DB::table('industries')->insert([
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Solo se quitan las filas que agrego esta migracion, y solo si nadie las
        // usa. Las de RENAMES son filas preexistentes renombradas y las de
        // PREEXISTING ya estaban con ese mismo texto: ninguna de las dos se borra.
        // Tampoco se revierte el renombrado, porque cambiarlo de vuelta dejaria
        // mal el nombre que ya vio quien eligio esa industria.
        $noSeBorran = array_merge(array_values(self::RENAMES), self::PREEXISTING);

        foreach (self::DESCRIPTIONS as $description) {
            if (in_array($description, $noSeBorran, true)) {
                continue;
            }

            $id = $this->findId($description);

            if (! $id || DB::table('people')->where('industry_id', $id)->exists()) {
                continue;
            }

            DB::table('industries')->where('id', $id)->delete();
        }
    }

    /**
     * Id de la industria por descripcion, ignorando mayusculas y espacios.
     *
     * La columna no tiene indice unico, asi que la idempotencia no puede
     * apoyarse en la base: se compara normalizado para no duplicar una fila que
     * alguien haya creado a mano (por ejemplo "salud" o "Salud ").
     */
    private function findId(string $description): ?int
    {
        $id = DB::table('industries')
            ->whereRaw('LOWER(TRIM(description)) = ?', [Str::lower(trim($description))])
            ->value('id');

        return $id === null ? null : (int) $id;
    }
};
