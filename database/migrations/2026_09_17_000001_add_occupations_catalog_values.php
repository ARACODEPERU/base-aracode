<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Catalogo de cargos solicitado (mismo orden en que se pidio).
     *
     * Los que ya existan se saltan solos: Asesor Legal, Asistente
     * Administrativo, Auxiliar Contable, Perito Contable y Otros ya estaban en
     * el catalogo original de occupations.
     */
    private const DESCRIPTIONS = [
        'Asesor Legal',
        'Actualmente No Laboro',
        'Administrador',
        'Analista Administrativo - GAF',
        'Analista Contable',
        'Analista Contable Senior',
        'Analista de Auditoría Interna',
        'Analista de Contabilidad',
        'Analista de Gestión y Control Interno',
        'Analista de Imagen Corporativa',
        'Analista de Peritajes',
        'Analista Financiero',
        'Analista Junior De Cuentas Por Cobrar',
        'Analista Tributario',
        'Asesor Contable - Financiero',
        'Asistente Administrativo',
        'Asistente Contable',
        'Asistente de Auditoría',
        'Asistente de Contabilidad',
        'Asistente de Control Interno',
        'Asistente de Impuestos',
        'Auditor Financiero',
        'Auditor Interno',
        'Auditor Semi Senior',
        'Auditor Senior',
        'Auditor y Consultor',
        'Auxiliar Contable',
        'Auxiliar de Contabilidad',
        'CEO',
        'CFO',
        'Consultora Senior en Sostenibilidad',
        'Contador Financiero',
        'Contador General',
        'Contralor',
        'Controller',
        'Coordinador de Contabilidad',
        'Coordinador de Impuestos',
        'Coordinador de Sostenibilidad',
        'Coordinadora Contable',
        'Coordinadora ESG',
        'Coordinadora OCI',
        'Director Administrativo',
        'Director de Contabilidad',
        'Director Financiero Administrativo',
        'Director General de Administración',
        'Directora de Finanzas',
        'Docente Universitario',
        'Encargada de Auditoría',
        'Encargado de Costos',
        'Gerente de Administración y Finanzas',
        'Gerente de Auditoría y Consultoría Financiera',
        'Gerente de Contabilidad',
        'Gerente de Desarrollo y Presupuesto',
        'Gerente de Impuestos',
        'Gerente de Soporte TI',
        'Gerente Financiero',
        'Jefatura de Administración y Contabilidad',
        'Jefe de Auditoría Interna',
        'Jefe de Contabilidad y Finanzas',
        'Jefe de Control Interno',
        'Jefe de Estados Financieros',
        'Jefe de Impuestos',
        'Jefe de Tributación',
        'Jefe Economía y Finanzas',
        'Jefe Sostenibilidad y Comunic Externas',
        'Líder de Auditoría Financiera y de Procesos',
        'Otros',
        'Perito Contable',
        'Practicante',
        'Responsable de Auditoría Interna',
        'Senior de Audit & Advisory',
        'Senior de Audit & Assurance',
        'Senior de Auditoría',
        'Senior de Auditoría Financiera',
        'Senior de Contabilidad',
        'Socio de Auditoría',
        'Sub Contador',
        'Sub Gerencia De Finanzas',
        'Sub-Gerente de Administración y Finanzas',
        'Subgerente de Contabilidad',
        'Supervisor Activo Fijo y Patrimonio',
        'Supervisor Contador de Ingresos',
        'Supervisor de Contabilidad y Finanzas',
        'Supervisor Técnica de AFG',
        'Sustainability Developer',
    ];

    /**
     * Textos del catalogo pedido que ya existian antes de esta migracion.
     *
     * Sirve para que el down() no borre filas que no creo esta migracion (son
     * las unicas que coinciden textualmente con el catalogo original).
     */
    private const PREEXISTING = [
        'Asesor Legal',
        'Asistente Administrativo',
        'Auxiliar Contable',
        'Otros',
        'Perito Contable',
    ];

    /**
     * Desde que id empiezan los codigos reservados del catalogo original
     * ("Sin especificar" = 998, "Otros" = 999). Los cargos nuevos se numeran
     * por debajo de este limite.
     */
    private const RESERVED_FROM = 998;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // occupations.id es smallint sin autoincremento: los ids se asignan a
        // mano, continuando despues del ultimo id real del catalogo (46).
        $nextId = (int) DB::table('occupations')
            ->where('id', '<', self::RESERVED_FROM)
            ->max('id');

        foreach (self::DESCRIPTIONS as $description) {
            if ($this->findId($description)) {
                continue;
            }

            // Defensivo: si el id calculado ya esta ocupado (catalogo ampliado a
            // mano entre migraciones), se busca el primero libre.
            do {
                $nextId++;
            } while (DB::table('occupations')->where('id', $nextId)->exists());

            DB::table('occupations')->insert([
                'id' => $nextId,
                'description' => $description,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Solo se quitan las filas que agrego esta migracion, y solo si nadie las
        // usa. Las de PREEXISTING ya estaban con ese mismo texto: no se borran.
        foreach (self::DESCRIPTIONS as $description) {
            if (in_array($description, self::PREEXISTING, true)) {
                continue;
            }

            $id = $this->findId($description);

            if (! $id || DB::table('people')->where('occupation_id', $id)->exists()) {
                continue;
            }

            DB::table('occupations')->where('id', $id)->delete();
        }
    }

    /**
     * Id del cargo por descripcion, ignorando mayusculas y espacios.
     *
     * El catalogo original guarda algunas descripciones con distinta
     * capitalizacion ("Asesor legal"), asi que la comparacion normalizada evita
     * duplicar un cargo que ya existe escrito de otra forma.
     */
    private function findId(string $description): ?int
    {
        $id = DB::table('occupations')
            ->whereRaw('LOWER(TRIM(description)) = ?', [Str::lower(trim($description))])
            ->value('id');

        return $id === null ? null : (int) $id;
    }
};
