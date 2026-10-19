<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolGradeCompetency;
use Modules\Academic\Entities\AcaSchoolJourney;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSchedule;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Entities\AcaSchoolTestMode;
use Modules\Academic\Entities\AcaSchoolTestRecord;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaTeacher;

/**
 * Datos de prueba del modulo escolar (Academico > Modos de Prueba).
 *
 * Permite trabajar con el colegio lleno de informacion sin cargarla a mano:
 *
 * - fill(): crea niveles, grados, secciones, jornadas, areas, docentes,
 *   auxiliares, alumnos, apoderados, matriculas y bloques de horario. Nada de
 *   lo que crea queda "anonimo": cada fila se anota en aca_school_test_records
 *   (con su entidad, su id y la corrida de llenado).
 * - purge(): borra lo anotado en el registro y, ademas, todo lo que se genero
 *   al usar esos datos (asistencias de aula y de puerta, avisos SMS,
 *   calificaciones, cobros, cronogramas de pago) y los comprobantes de venta
 *   que esos cobros hayan emitido, siempre que no se hayan enviado a SUNAT.
 * - lockProduction(): borra lo mismo y deja el sistema en modo produccion: la
 *   pantalla ya no permite llenar ni borrar (bloqueo definitivo desde la
 *   interfaz).
 *
 * Regla de oro del borrado: solo se elimina lo que esta en el registro o lo que
 * cuelga de el. Todo lo demas del colegio (datos reales) se respeta, y las
 * personas que todavia tengan alguna referencia se conservan y se informan.
 */
class SchoolTestDataService
{
    /** Tipo de negocio "Colegio" (parametro P000009). */
    public const BUSINESS_COLEGIO = '5';

    /** Tipo de negocio "Colegio y capacitaciones" (parametro P000009). */
    public const BUSINESS_COLEGIO_CAPACITACIONES = '6';

    /** Entidades del registro de datos de prueba. */
    public const ENTITY_YEAR = 'year';
    public const ENTITY_LEVEL = 'level';
    public const ENTITY_GRADE = 'grade';
    public const ENTITY_SECTION = 'section';
    public const ENTITY_JOURNEY = 'journey';
    public const ENTITY_AREA = 'area';
    public const ENTITY_PERSON = 'person';
    public const ENTITY_TEACHER = 'teacher';
    public const ENTITY_STUDENT = 'student';
    public const ENTITY_GUARDIAN = 'guardian';
    public const ENTITY_ENROLLMENT = 'enrollment';
    public const ENTITY_SCHEDULE = 'schedule';

    /** Marca visible de todo lo que crea el modo prueba (codigos y nombres). */
    public const MARK = 'PRUEBA';

    /** Prefijo de los documentos de identidad de prueba (people.number). */
    private const DOCUMENT_PREFIX = 'TEST';

    /** Tipo de documento generico usado por las personas de prueba. */
    private const DOCUMENT_TYPE_ID = '0';

    /** Valores por defecto y maximos del llenado (validacion de seguridad). */
    public const DEFAULT_SECTIONS_PER_GRADE = 1;
    public const DEFAULT_STUDENTS_PER_SECTION = 5;
    public const MAX_SECTIONS_PER_GRADE = 3;
    public const MAX_STUDENTS_PER_SECTION = 20;

    /** Cuantos docentes y auxiliares de prueba se aseguran. */
    private const TEACHER_COUNT = 3;
    private const AUXILIARY_COUNT = 2;

    /** Bloques de clase por dia y hora de inicio (horario de prueba). */
    private const SCHEDULE_BLOCKS_PER_DAY = 2;
    private const SCHEDULE_FIRST_BLOCK = '08:00:00';

    /**
     * Estructura de niveles SOLO del modo prueba (no toca el sistema):
     * Estimulación, Inicial, Primaria y Secundaria con sus grados.
     */
    private const TEST_LEVEL_STRUCTURE = [
        ['code' => 'estimulacion', 'name' => 'Estimulación', 'grades' => ['1 año', '2 años']],
        ['code' => 'inicial', 'name' => 'Inicial', 'grades' => ['3 años', '4 años', '5 años']],
        ['code' => 'primaria', 'name' => 'Primaria', 'grades' => ['1°', '2°', '3°', '4°', '5°', '6°']],
        ['code' => 'secundaria', 'name' => 'Secundaria', 'grades' => ['1°', '2°', '3°', '4°', '5°']],
    ];

    /**
     * Areas del nivel Estimulación SOLO para el modo prueba (el CNEB del
     * sistema no define areas para ese nivel, así que se usan estas para que
     * sus secciones también tengan horario).
     */
    private const TEST_ESTIMULACION_AREAS = [
        'Desarrollo psicomotor',
        'Comunicación temprana',
        'Descubrimiento del entorno',
    ];

    /** Nombres de pila para las personas de prueba (se combinan al azar). */
    private const TEST_FIRST_NAMES_MALE = [
        'Adrián', 'Alejandro', 'Alonso', 'Bruno', 'César', 'Cristhofer', 'Damián', 'Diego',
        'Emmanuel', 'Fabricio', 'Gabriel', 'Gonzalo', 'Hugo', 'Iván', 'Joaquín', 'Jorge',
        'Leonardo', 'Luis', 'Marco', 'Martín', 'Mateo', 'Miguel', 'Nicolás', 'Paolo',
        'Renato', 'Rodrigo', 'Santiago', 'Sebastián', 'Thiago', 'Valentino',
    ];

    /** Nombres de pila para las personas de prueba (se combinan al azar). */
    private const TEST_FIRST_NAMES_FEMALE = [
        'Abril', 'Aitana', 'Alessandra', 'Antonia', 'Ariana', 'Bianca', 'Camila', 'Daniela',
        'Danna', 'Emma', 'Fabiana', 'Fernanda', 'Gianna', 'Jimena', 'Kiara', 'Lucía',
        'Luciana', 'María', 'Mía', 'Miranda', 'Nicole', 'Paula', 'Rafaella', 'Regina',
        'Renata', 'Sofía', 'Valentina', 'Valeria', 'Victoria', 'Ximena',
    ];

    /** Apellidos para las personas de prueba (se combinan al azar). */
    private const TEST_LAST_NAMES = [
        'Aguilar', 'Alvarado', 'Apaza', 'Arévalo', 'Barreto', 'Cabrera', 'Cáceres', 'Campos',
        'Cárdenas', 'Carrasco', 'Castillo', 'Céspedes', 'Chávez', 'Condori', 'Cruz', 'Delgado',
        'Espinoza', 'Fernández', 'Flores', 'García', 'Gonzales', 'Gutiérrez', 'Huamán', 'León',
        'López', 'Mamani', 'Medina', 'Mendoza', 'Miranda', 'Morales', 'Núñez', 'Paredes',
        'Quispe', 'Ramírez', 'Ramos', 'Reyes', 'Rojas', 'Salazar', 'Sánchez', 'Silva',
        'Soto', 'Torres', 'Vargas', 'Vásquez', 'Vega', 'Velásquez', 'Vera', 'Yupanqui',
    ];

    private int $documentSequence = 0;
    private int $studentSequence = 0;
    private int $personSequence = 0;

    public function __construct(private readonly SchoolContextService $context)
    {
    }

    /* ------------------------------------------------------------------
     * Consultas de estado
     * ------------------------------------------------------------------ */

    /** Valor actual del parametro de tipo de negocio (P000009). */
    public static function businessType(): string
    {
        try {
            return trim((string) Parameter::where('parameter_code', 'P000009')->value('value_default'));
        } catch (\Throwable) {
            return '';
        }
    }

    /** true solo con tipo de negocio Colegio (5) o Colegio y capacitaciones (6). */
    public function isAvailable(): bool
    {
        return in_array(self::businessType(), [self::BUSINESS_COLEGIO, self::BUSINESS_COLEGIO_CAPACITACIONES], true);
    }

    public function state(): AcaSchoolTestMode
    {
        return AcaSchoolTestMode::state();
    }

    /** true cuando el sistema ya quedo en modo produccion. */
    public function isLocked(): bool
    {
        return $this->state()->isLocked();
    }

    /** Datos de bloqueo para la pantalla. */
    public function lockInfo(): array
    {
        $state = $this->state();

        return [
            'locked' => $state->isLocked(),
            'locked_at' => $state->locked_at?->format('d/m/Y H:i'),
            'locked_by' => $state->locked_by_name,
            'last_fill_at' => $state->last_fill_at?->format('d/m/Y H:i'),
            'last_purge_at' => $state->last_purge_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Resumen para la pantalla: totales por entidad y estado del modo.
     */
    public function summary(): array
    {
        $schoolId = $this->context->currentSchoolId();

        $counts = AcaSchoolTestRecord::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->selectRaw('entity, COUNT(*) as total')
            ->groupBy('entity')
            ->pluck('total', 'entity');

        return [
            'total' => (int) $counts->sum(),
            'counts' => collect(self::entityLabels())
                ->map(fn (string $label, string $entity) => [
                    'entity' => $entity,
                    'label' => $label,
                    'total' => (int) ($counts[$entity] ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }

    /** Etiquetas legibles de cada entidad del registro. */
    public static function entityLabels(): array
    {
        return [
            self::ENTITY_YEAR => 'Años escolares',
            self::ENTITY_LEVEL => 'Niveles',
            self::ENTITY_GRADE => 'Grados',
            self::ENTITY_SECTION => 'Secciones',
            self::ENTITY_JOURNEY => 'Jornadas',
            self::ENTITY_AREA => 'Áreas curriculares',
            self::ENTITY_TEACHER => 'Docentes y auxiliares',
            self::ENTITY_STUDENT => 'Alumnos',
            self::ENTITY_GUARDIAN => 'Apoderados',
            self::ENTITY_ENROLLMENT => 'Matrículas',
            self::ENTITY_SCHEDULE => 'Bloques de horario',
            self::ENTITY_PERSON => 'Personas',
        ];
    }

    /** Limites y valores por defecto que valida el controlador antes de llenar. */
    public static function limits(): array
    {
        return [
            'sections_per_grade' => [
                'default' => self::DEFAULT_SECTIONS_PER_GRADE,
                'min' => 1,
                'max' => self::MAX_SECTIONS_PER_GRADE,
            ],
            'students_per_section' => [
                'default' => self::DEFAULT_STUDENTS_PER_SECTION,
                'min' => 1,
                'max' => self::MAX_STUDENTS_PER_SECTION,
            ],
        ];
    }

    /* ------------------------------------------------------------------
     * Llenado
     * ------------------------------------------------------------------ */

    /**
     * Crea (o completa) la estructura y los alumnos de prueba.
     *
     * @param  array{sections_per_grade?: int, students_per_section?: int} $options
     * @return array{run_id: string, created: array<string, int>, total: int}
     */
    public function fill(array $options = []): array
    {
        $this->assertUnlocked();

        $school = $this->context->currentSchool();

        if (! $school) {
            throw new \RuntimeException('No hay un colegio configurado: registra el colegio antes de llenar datos de prueba.');
        }

        $sectionsPerGrade = $this->clamp((int) ($options['sections_per_grade'] ?? self::DEFAULT_SECTIONS_PER_GRADE), 1, self::MAX_SECTIONS_PER_GRADE);
        $studentsPerSection = $this->clamp((int) ($options['students_per_section'] ?? self::DEFAULT_STUDENTS_PER_SECTION), 1, self::MAX_STUDENTS_PER_SECTION);

        $runId = (string) Str::uuid();
        $created = [];

        $register = function (string $entity, int $recordId, ?string $label = null) use ($runId, $school, &$created): void {
            AcaSchoolTestRecord::create([
                'run_id' => $runId,
                'school_id' => $school->id,
                'entity' => $entity,
                'record_id' => $recordId,
                'label' => $label,
            ]);

            $created[$entity] = ($created[$entity] ?? 0) + 1;
        };

        DB::transaction(function () use ($school, $register, $sectionsPerGrade, $studentsPerSection) {
            $year = $this->ensureYear($school, $register);
            $levels = $this->ensureLevels($school, $register);

            foreach ($levels as $level) {
                $this->ensureJourney($school, $level, $register);
                $this->ensureAreas($school, $level, $register);
            }

            $teachers = $this->ensureTeachers($school, $register);
            $sections = $this->ensureGradesAndSections($school, $levels, $teachers, $sectionsPerGrade, $register);

            $this->ensureStudents($school, $year, $sections, $studentsPerSection, $register);
            $this->ensureSchedules($school, $year, $sections, $register);

            $this->state()->update(['last_fill_at' => now()]);
        });

        Log::info('Academico: se llenaron datos de prueba', [
            'school_id' => $school->id,
            'run_id' => $runId,
            'created' => $created,
        ]);

        return [
            'run_id' => $runId,
            'created' => $created,
            'total' => array_sum($created),
        ];
    }

    /**
     * Año escolar de trabajo: reutiliza el activo (o el primero) y solo crea uno
     * cuando el colegio no tiene ninguno (en ese caso queda registrado como
     * dato de prueba y se borra al purgar).
     */
    private function ensureYear(AcaSchool $school, callable $register): AcaSchoolYear
    {
        $year = AcaSchoolYear::where('school_id', $school->id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first()
            ?? AcaSchoolYear::where('school_id', $school->id)->first();

        if ($year) {
            return $year;
        }

        $year = AcaSchoolYear::create([
            'school_id' => $school->id,
            'year' => (int) now()->format('Y'),
            'status' => AcaSchoolYear::STATUS_ACTIVE,
            'observations' => self::MARK.': año escolar creado por el modo de prueba.',
        ]);

        $register(self::ENTITY_YEAR, $year->id, (string) $year->year);

        return $year;
    }

    /**
     * Los cuatro niveles del modo prueba (Estimulación, Inicial, Primaria y
     * Secundaria). Es estructura solo de prueba: no usa ni modifica la del
     * sistema. Reutiliza el nivel real que ya tenga ese codigo (no lo
     * registra: no es nuestro) y crea solo los que falten.
     *
     * @return Collection<int, AcaSchoolLevel>
     */
    private function ensureLevels(AcaSchool $school, callable $register): Collection
    {
        $levels = new Collection();

        foreach (self::TEST_LEVEL_STRUCTURE as $index => $definition) {
            $level = AcaSchoolLevel::where('school_id', $school->id)
                ->where('code', $definition['code'])
                ->first();

            if (! $level) {
                $level = AcaSchoolLevel::create([
                    'school_id' => $school->id,
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'sort_order' => $index + 1,
                    'status' => true,
                    'scale' => in_array($definition['code'], ['estimulacion', AcaSchoolLevel::INICIAL], true)
                        ? AcaSchoolGradeCompetency::SCALE_LITERAL
                        : AcaSchoolGradeCompetency::SCALE_VIGESIMAL,
                ]);

                $register(self::ENTITY_LEVEL, $level->id, $level->name);
            }

            $levels->push($level);
        }

        return $levels;
    }

    /**
     * Grados de prueba de cada nivel. Siempre son nuestros y van marcados: los
     * alumnos de prueba nunca se matriculan en un grado real.
     *
     * @return Collection<int, AcaSchoolGrade>
     */
    private function ensureGradesAndSections(AcaSchool $school, Collection $levels, Collection $teachers, int $sectionsPerGrade, callable $register): Collection
    {
        $registeredGrades = $this->registeredModels($school, self::ENTITY_GRADE, AcaSchoolGrade::class);
        $sections = $this->registeredModels($school, self::ENTITY_SECTION, AcaSchoolSection::class);
        $tutors = $teachers->filter(fn (AcaTeacher $teacher) => str_contains((string) $teacher->teacher_code, '-D'))->values();
        $auxiliaries = $teachers->filter(fn (AcaTeacher $teacher) => str_contains((string) $teacher->teacher_code, '-A'))->values();

        foreach ($levels as $level) {
            $definition = collect(self::TEST_LEVEL_STRUCTURE)->firstWhere('code', $level->code);
            $gradeNames = $definition['grades'] ?? ['1°'];

            $existing = $registeredGrades->where('level_id', $level->id);

            if ($existing->isEmpty()) {
                foreach ($gradeNames as $index => $gradeName) {
                    $grade = AcaSchoolGrade::create([
                        'school_id' => $school->id,
                        'level_id' => $level->id,
                        'name' => self::MARK.' '.$gradeName,
                        'sort_order' => $index + 1,
                        'status' => true,
                    ]);

                    $register(self::ENTITY_GRADE, $grade->id, $grade->name);
                    $existing->push($grade);
                }
            }

            foreach ($existing as $grade) {
                $gradeSections = $sections->where('grade_id', $grade->id);

                for ($index = $gradeSections->count(); $index < $sectionsPerGrade; $index++) {
                    // Secciones de la A a la Z según el caso (nombres reales,
                    // sin marca: el registro de prueba sabe cuáles son suyas).
                    $name = chr(65 + ($index % 26));

                    $section = AcaSchoolSection::create([
                        'school_id' => $school->id,
                        'grade_id' => $grade->id,
                        'name' => mb_substr($name, 0, 20),
                        'capacity' => max(1, self::DEFAULT_STUDENTS_PER_SECTION * 2),
                        'shift' => AcaSchoolSection::SHIFT_MANANA,
                        'tutor_person_id' => $tutors->isEmpty() ? null : $tutors[($index + $grade->id) % $tutors->count()]->person_id,
                        'auxiliary_person_id' => $auxiliaries->isEmpty() ? null : $auxiliaries[($index + $grade->id) % $auxiliaries->count()]->person_id,
                        'status' => true,
                    ]);

                    $register(self::ENTITY_SECTION, $section->id, $level->name.' - '.$grade->name.' - '.$section->name);
                    $sections->push($section);
                }
            }
        }

        return $sections;
    }

    /**
     * Jornada de mañana del nivel (hora oficial de entrada y salida). Reutiliza
     * la que exista para ese nivel y turno.
     */
    private function ensureJourney(AcaSchool $school, AcaSchoolLevel $level, callable $register): void
    {
        $exists = AcaSchoolJourney::where('school_id', $school->id)
            ->where('level_id', $level->id)
            ->where('shift', AcaSchoolSection::SHIFT_MANANA)
            ->exists();

        if ($exists) {
            return;
        }

        $journey = AcaSchoolJourney::create([
            'school_id' => $school->id,
            'level_id' => $level->id,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'entry_time' => '07:30:00',
            'exit_time' => '13:30:00',
            'tolerance_minutes' => 10,
            'recess_start' => '10:00:00',
            'recess_end' => '10:30:00',
            'status' => true,
        ]);

        $register(self::ENTITY_JOURNEY, $journey->id, $level->name.' - Mañana');
    }

    /**
     * Areas curriculares estandar del nivel (CNEB). Si el colegio ya tiene el
     * area, se reutiliza; las que se crean quedan registradas. Para el nivel
     * Estimulación (solo existe en el modo prueba) se usan areas propias de
     * prueba, sin tocar el catálogo del sistema.
     */
    private function ensureAreas(AcaSchool $school, AcaSchoolLevel $level, callable $register): void
    {
        $names = AcaSchoolArea::standardCnebAreas()[$level->code]
            ?? ($level->code === 'estimulacion' ? self::TEST_ESTIMULACION_AREAS : []);

        foreach ($names as $index => $name) {
            $exists = AcaSchoolArea::where('school_id', $school->id)
                ->where('level', $level->code)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            $area = AcaSchoolArea::create([
                'school_id' => $school->id,
                'name' => $name,
                'level' => $level->code,
                'sort_order' => $index + 1,
                'status' => true,
            ]);

            $register(self::ENTITY_AREA, $area->id, $level->name.' - '.$area->name);
        }
    }

    /**
     * Docentes y auxiliares de prueba (personas + ficha en aca_teachers).
     *
     * @return Collection<int, AcaTeacher>
     */
    private function ensureTeachers(AcaSchool $school, callable $register): Collection
    {
        $teachers = $this->registeredModels($school, self::ENTITY_TEACHER, AcaTeacher::class);
        $codes = [];

        for ($index = 1; $index <= self::TEACHER_COUNT; $index++) {
            $codes['D'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)] = 'Docente';
        }

        for ($index = 1; $index <= self::AUXILIARY_COUNT; $index++) {
            $codes['A'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)] = 'Auxiliar';
        }

        $position = 0;

        foreach ($codes as $suffix => $role) {
            $code = self::MARK.'-'.$suffix;

            if ($teachers->contains(fn (AcaTeacher $teacher) => $teacher->teacher_code === $code)) {
                $position++;

                continue;
            }

            $person = $this->createPerson($role, $position % 2 === 0 ? 'M' : 'F');
            $position++;

            $register(self::ENTITY_PERSON, $person->id, $person->full_name);

            $teacher = AcaTeacher::create([
                'teacher_code' => $code,
                'person_id' => $person->id,
            ]);

            $register(self::ENTITY_TEACHER, $teacher->id, ($role === 'Docente' ? 'Docente ' : 'Auxiliar ').$code);
            $teachers->push($teacher);
        }

        return $teachers;
    }

    /**
     * Alumnos de prueba con su matricula y su apoderado. Completa hasta
     * studentsPerSection los alumnos que falten en cada seccion de prueba.
     *
     * @param  Collection<int, AcaSchoolSection> $sections
     */
    private function ensureStudents(AcaSchool $school, AcaSchoolYear $year, Collection $sections, int $studentsPerSection, callable $register): void
    {
        $enrollments = AcaSchoolEnrollment::where('school_id', $school->id)
            ->where('year_id', $year->id)
            ->get(['id', 'section_id']);

        foreach ($sections as $section) {
            $taken = $enrollments->where('section_id', $section->id)->count();

            for ($index = $taken; $index < $studentsPerSection; $index++) {
                $studentPerson = $this->createPerson('Alumno', $index % 2 === 0 ? 'M' : 'F');
                $register(self::ENTITY_PERSON, $studentPerson->id, $studentPerson->full_name);

                $student = AcaSchoolStudent::create([
                    'school_id' => $school->id,
                    'person_id' => $studentPerson->id,
                    'student_code' => $this->nextStudentCode($school->id),
                    'status' => true,
                ]);

                $register(self::ENTITY_STUDENT, $student->id, $student->student_code);

                $guardianPerson = $this->createPerson('Apoderado', 'F');
                $register(self::ENTITY_PERSON, $guardianPerson->id, $guardianPerson->full_name);

                $enrollment = AcaSchoolEnrollment::create([
                    'school_id' => $school->id,
                    'year_id' => $year->id,
                    'student_id' => $student->id,
                    'section_id' => $section->id,
                    'type' => AcaSchoolEnrollment::TYPE_NUEVA,
                    'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
                    'enrollment_date' => now()->toDateString(),
                    'guardian_person_id' => $guardianPerson->id,
                    'guardian_relationship' => 'madre',
                    'guardian_phone' => '900000000',
                    'observations' => self::MARK.': matrícula creada por el modo de prueba.',
                ]);

                $register(self::ENTITY_ENROLLMENT, $enrollment->id, $student->student_code);

                $guardian = AcaSchoolStudentGuardian::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'guardian_person_id' => $guardianPerson->id,
                    'relationship' => 'madre',
                    'is_primary' => true,
                    'status' => true,
                ]);

                $register(self::ENTITY_GUARDIAN, $guardian->id, $guardianPerson->full_name);
            }
        }
    }

    /**
     * Bloques de horario de cada seccion de prueba (lunes a viernes) usando las
     * areas de su nivel. Solo se crean si la seccion todavia no tiene ninguno.
     *
     * @param  Collection<int, AcaSchoolSection> $sections
     */
    private function ensureSchedules(AcaSchool $school, AcaSchoolYear $year, Collection $sections, callable $register): void
    {
        $registeredSchedules = AcaSchoolTestRecord::where('school_id', $school->id)
            ->where('entity', self::ENTITY_SCHEDULE)
            ->pluck('record_id')
            ->all();

        $existingSectionIds = $registeredSchedules === []
            ? []
            : AcaSchoolSchedule::whereIn('id', $registeredSchedules)->pluck('section_id')->unique()->all();

        foreach ($sections as $section) {
            if (in_array($section->id, $existingSectionIds, true)) {
                continue;
            }

            $levelCode = $section->grade?->level?->code;
            $areaIds = AcaSchoolArea::where('school_id', $school->id)
                ->where('level', $levelCode)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();

            $teacherIds = AcaTeacher::whereIn('teacher_code', $this->teacherCodes())
                ->pluck('person_id')
                ->all();

            if ($areaIds === [] || $teacherIds === []) {
                continue;
            }

            $sort = 0;

            foreach (AcaSchoolSchedule::WEEKDAYS_SCHOOL as $weekday) {
                for ($block = 0; $block < self::SCHEDULE_BLOCKS_PER_DAY; $block++) {
                    $start = $this->blockStart($block);
                    $schedule = AcaSchoolSchedule::create([
                        'school_id' => $school->id,
                        'year_id' => $year->id,
                        'section_id' => $section->id,
                        'area_id' => $areaIds[$sort % count($areaIds)],
                        'teacher_person_id' => $teacherIds[$sort % count($teacherIds)],
                        'weekday' => $weekday,
                        'start_time' => $start,
                        'end_time' => $this->addMinutes($start, 90),
                        'room' => self::MARK,
                        'sort_order' => $sort + 1,
                        'status' => true,
                    ]);

                    $register(self::ENTITY_SCHEDULE, $schedule->id, $section->name.' - '.AcaSchoolSchedule::weekdayLabel($weekday));
                    $sort++;
                }
            }
        }
    }

    /* ------------------------------------------------------------------
     * Borrado
     * ------------------------------------------------------------------ */

    /**
     * Borra los datos de prueba y todo lo generado a partir de ellos.
     *
     * @return array{deleted: array<string, int>, documents_kept: int, people_kept: int}
     */
    public function purge(): array
    {
        $this->assertUnlocked();

        $school = $this->context->currentSchool();

        if (! $school) {
            throw new \RuntimeException('No hay un colegio configurado: no hay datos de prueba que borrar.');
        }

        $deleted = [];
        $documentsKept = 0;
        $peopleKept = 0;

        DB::transaction(function () use ($school, &$deleted, &$documentsKept, &$peopleKept) {
            $ids = fn (string $entity): array => AcaSchoolTestRecord::where('school_id', $school->id)
                ->where('entity', $entity)
                ->pluck('record_id')
                ->all();

            $sectionIds = $ids(self::ENTITY_SECTION);
            $studentIds = $ids(self::ENTITY_STUDENT);
            $gradeIds = $ids(self::ENTITY_GRADE);
            $levelIds = $ids(self::ENTITY_LEVEL);
            $personIds = $ids(self::ENTITY_PERSON);
            $teacherIds = $ids(self::ENTITY_TEACHER);
            $yearIds = $ids(self::ENTITY_YEAR);
            $areaIds = $ids(self::ENTITY_AREA);
            $journeyIds = $ids(self::ENTITY_JOURNEY);
            $scheduleIds = $ids(self::ENTITY_SCHEDULE);

            /*
             * Matriculas alcanzadas: las nuestras y tambien las que usan una
             * seccion o un alumno de prueba (por ejemplo, si alguien matriculo a
             * un alumno real en una seccion PRUEBA). Todo lo que cuelga de esas
             * matriculas es informacion generada a partir de los datos de prueba.
             */
            $enrollmentIds = array_values(array_unique(array_merge(
                $ids(self::ENTITY_ENROLLMENT),
                $this->idsIn('aca_school_enrollments', 'section_id', $sectionIds),
                $this->idsIn('aca_school_enrollments', 'student_id', $studentIds),
            )));

            $guardianIds = array_values(array_unique(array_merge(
                $ids(self::ENTITY_GUARDIAN),
                $this->idsIn('aca_school_student_guardians', 'student_id', $studentIds),
            )));

            // 1) Asistencia de puerta y avisos SMS enviados por ella.
            $gateIds = $this->relatedIds('aca_school_gate_attendances', [
                'enrollment_id' => $enrollmentIds,
                'student_id' => $studentIds,
                'section_id' => $sectionIds,
            ]);

            $deleted['Avisos SMS de asistencia'] = $this->deleteRelated('aca_school_notice_deliveries', [
                'student_id' => $studentIds,
                'gate_attendance_id' => $gateIds,
            ]);

            $deleted['Suscripciones Telegram de asistencia'] = $this->deleteRelated('aca_school_telegram_subscriptions', [
                'student_id' => $studentIds,
                'guardian_person_id' => $personIds,
            ]);

            $deleted['Asistencias de puerta'] = $this->deleteByIds('aca_school_gate_attendances', 'id', $gateIds);

            // 2) Asistencia de aula (mes a mes).
            $deleted['Asistencias de aula'] = $this->deleteRelated('aca_school_attendances', [
                'enrollment_id' => $enrollmentIds,
                'section_id' => $sectionIds,
            ]);

            // 3) Calificaciones (competencias CNEB y notas por area).
            $deleted['Calificaciones por competencia'] = $this->deleteRelated('aca_school_grade_competencies', [
                'enrollment_id' => $enrollmentIds,
                'section_id' => $sectionIds,
            ]);

            $deleted['Notas por área'] = $this->deleteRelated('aca_school_grade_records', [
                'enrollment_id' => $enrollmentIds,
                'section_id' => $sectionIds,
            ]);

            // 4) Cobros: se guardan los comprobantes de venta antes de borrarlos.
            $chargeIds = $this->relatedIds('aca_school_charges', ['enrollment_id' => $enrollmentIds]);
            $documentIds = [];
            $saleIds = [];

            if ($chargeIds !== []) {
                $documentIds = DB::table('aca_school_charges')->whereIn('id', $chargeIds)
                    ->whereNotNull('sale_document_id')->pluck('sale_document_id')->all();
                $saleIds = DB::table('aca_school_charges')->whereIn('id', $chargeIds)
                    ->whereNotNull('sale_id')->pluck('sale_id')->all();
            }

            $deleted['Cronogramas de pago'] = $this->deleteRelated('aca_school_payment_schedules', ['enrollment_id' => $enrollmentIds]);
            $deleted['Cobros a alumnos'] = $this->deleteByIds('aca_school_charges', 'id', $chargeIds);

            [$removedDocuments, $documentsKept] = $this->deleteChargeDocuments($documentIds, $saleIds);
            $deleted['Comprobantes de cobro (no enviados)'] = $removedDocuments;

            // 5) Horario, matrículas, apoderados y alumnos de prueba.
            $deleted['Bloques de horario'] = $this->deleteByIds('aca_school_schedules', 'id', $scheduleIds)
                + $this->deleteRelated('aca_school_schedules', ['section_id' => $sectionIds]);

            $deleted['Matrículas'] = $this->deleteByIds('aca_school_enrollments', 'id', $enrollmentIds);
            $deleted['Apoderados'] = $this->deleteByIds('aca_school_student_guardians', 'id', $guardianIds);
            $deleted['Alumnos'] = $this->deleteByIds('aca_school_students', 'id', $studentIds);

            // 6) Docentes y auxiliares, areas, jornadas, secciones, grados,
            //    niveles y años (solo los que quedaron libres).
            $deleted['Docentes y auxiliares'] = $this->deleteByIds('aca_teachers', 'id', $teacherIds);

            $deleted['Áreas curriculares'] = $this->deleteByIdsIfFree('aca_school_areas', $areaIds, 'aca_school_schedules', 'area_id');
            $deleted['Jornadas'] = $this->deleteByIds('aca_school_journeys', 'id', $journeyIds);
            $deleted['Secciones'] = $this->deleteByIds('aca_school_sections', 'id', $sectionIds);
            $deleted['Grados'] = $this->deleteByIds('aca_school_grades', 'id', $gradeIds);
            $deleted['Niveles'] = $this->deleteByIdsIfFree('aca_school_levels', $levelIds, 'aca_school_grades', 'level_id');
            $deleted['Años escolares'] = $this->deleteByIdsIfFree('aca_school_years', $yearIds, 'aca_school_enrollments', 'year_id');

            // 7) Personas de prueba: solo las que ya nadie referencia.
            [$removedPeople, $peopleKept] = $this->deleteTestPeople($personIds);
            $deleted['Personas'] = $removedPeople;

            // 8) Registro limpio y marca de la ultima purga.
            $deleted['Registros de prueba'] = (int) AcaSchoolTestRecord::where('school_id', $school->id)->delete();

            $this->state()->update(['last_purge_at' => now()]);
        });

        $deleted = array_filter($deleted, fn (int $total) => $total > 0);

        Log::warning('Academico: se borraron datos de prueba', [
            'school_id' => $school->id,
            'deleted' => $deleted,
            'documents_kept' => $documentsKept,
            'people_kept' => $peopleKept,
        ]);

        return [
            'deleted' => $deleted,
            'documents_kept' => $documentsKept,
            'people_kept' => $peopleKept,
        ];
    }

    /**
     * Borra y bloquea: deja el sistema en modo produccion.
     *
     * Primero elimina los datos de prueba (igual que purge) y despues marca el
     * bloqueo definitivo, de modo que la pantalla ya no permita llenar ni
     * borrar. Desde la interfaz no se puede revertir.
     */
    public function lockProduction(?int $userId, ?string $userName): array
    {
        if ($this->isLocked()) {
            throw new \RuntimeException('El sistema ya está en modo producción.');
        }

        $result = $this->purge();

        $this->state()->update([
            'locked_at' => now(),
            'locked_by_user_id' => $userId,
            'locked_by_name' => $userName,
        ]);

        Log::warning('Academico: el sistema quedo en modo produccion (modo prueba bloqueado)', [
            'user_id' => $userId,
            'user_name' => $userName,
        ]);

        return $result;
    }

    /* ------------------------------------------------------------------
     * Apoyo
     * ------------------------------------------------------------------ */

    /** Falla si el sistema ya esta en modo produccion. */
    private function assertUnlocked(): void
    {
        if ($this->isLocked()) {
            throw new \RuntimeException('El sistema está en modo producción: esta acción quedó bloqueada definitivamente.');
        }
    }

    /**
     * Registros propios de una entidad (los que anoto el modo prueba).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function registeredModels(AcaSchool $school, string $entity, string $modelClass): Collection
    {
        $ids = AcaSchoolTestRecord::where('school_id', $school->id)
            ->where('entity', $entity)
            ->pluck('record_id')
            ->all();

        if ($ids === []) {
            return new Collection();
        }

        return $modelClass::whereIn('id', $ids)->get();
    }

    /**
     * Persona de prueba con nombre realista: combina al azar un nombre de pila
     * (según el género) con dos apellidos, para no ver "PRUEBA PRUEBA" en la
     * ficha. El documento sigue marcado (TEST...) y el registro de prueba sabe
     * que la persona es suya, así el borrado no depende del nombre.
     */
    private function createPerson(string $role, string $gender): Person
    {
        $number = $this->nextDocumentNumber();
        $this->personSequence++;

        $firstName = $this->randomFirstName($gender);
        $fatherLastname = $this->randomLastName();
        $motherLastname = $this->randomLastName();

        return Person::create([
            'document_type_id' => self::DOCUMENT_TYPE_ID,
            'number' => $number,
            'names' => $firstName,
            'father_lastname' => $fatherLastname,
            'mother_lastname' => $motherLastname,
            'short_name' => trim($firstName.' '.$fatherLastname),
            'full_name' => trim($fatherLastname.' '.$motherLastname.' '.$firstName),
            'gender' => $gender,
            'status' => true,
            'country_id' => 1,
            'is_client' => false,
            'is_provider' => false,
            'ubigeo_description' => self::MARK,
        ]);
    }

    /** Nombre de pila al azar según el género (M = masculinos, otro = femeninos). */
    private function randomFirstName(string $gender): string
    {
        $pool = strtoupper($gender) === 'M' ? self::TEST_FIRST_NAMES_MALE : self::TEST_FIRST_NAMES_FEMALE;

        return $pool[array_rand($pool)];
    }

    /** Apellido al azar de la lista de prueba. */
    private function randomLastName(): string
    {
        return self::TEST_LAST_NAMES[array_rand(self::TEST_LAST_NAMES)];
    }

    /** Codigo de documento unico para las personas de prueba. */
    private function nextDocumentNumber(): string
    {
        do {
            $this->documentSequence++;
            $number = self::DOCUMENT_PREFIX.str_pad((string) $this->documentSequence, 8, '0', STR_PAD_LEFT);
        } while (Person::where('number', $number)->exists());

        return $number;
    }

    /** Codigo de alumno de prueba, unico dentro del colegio. */
    private function nextStudentCode(int $schoolId): string
    {
        do {
            $this->studentSequence++;
            $code = self::MARK.'-'.str_pad((string) $this->studentSequence, 4, '0', STR_PAD_LEFT);
        } while (AcaSchoolStudent::where('school_id', $schoolId)->where('student_code', $code)->exists());

        return $code;
    }

    /** @return array<int, string> */
    private function teacherCodes(): array
    {
        $codes = [];

        for ($index = 1; $index <= self::TEACHER_COUNT; $index++) {
            $codes[] = self::MARK.'-D'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        }

        for ($index = 1; $index <= self::AUXILIARY_COUNT; $index++) {
            $codes[] = self::MARK.'-A'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        }

        return $codes;
    }

    /** Hora de inicio del bloque n (n empieza en 0). */
    private function blockStart(int $block): string
    {
        return $this->addMinutes(self::SCHEDULE_FIRST_BLOCK, $block * 110);
    }

    /** Suma minutos a una hora HH:MM:SS. */
    private function addMinutes(string $time, int $minutes): string
    {
        return date('H:i:s', strtotime($time) + ($minutes * 60));
    }

    /**
     * Borra los comprobantes de venta emitidos desde cobros de prueba.
     *
     * Un comprobante ya enviado a SUNAT (invoice_send_date) es un registro
     * fiscal: no se toca. Las ventas a las que pertenece un comprobante
     * conservado tampoco se borran.
     *
     * @return array{0: int, 1: int}  [borrados, conservados]
     */
    private function deleteChargeDocuments(array $documentIds, array $saleIds): array
    {
        if ($documentIds === [] && $saleIds === []) {
            return [0, 0];
        }

        if (! Schema::hasTable('sale_documents')) {
            return [0, 0];
        }

        $candidates = DB::table('sale_documents')
            ->where(function ($query) use ($documentIds, $saleIds) {
                if ($documentIds !== []) {
                    $query->orWhereIn('id', $documentIds);
                }

                if ($saleIds !== []) {
                    $query->orWhereIn('sale_id', $saleIds);
                }
            })
            ->get(['id', 'sale_id', 'invoice_send_date']);

        if ($candidates->isEmpty()) {
            return [0, 0];
        }

        $removable = $candidates->filter(fn ($document) => $document->invoice_send_date === null);
        $blockedSaleIds = $candidates->whereNotIn('id', $removable->pluck('id')->all())
            ->pluck('sale_id')->filter()->unique()->all();

        $removableDocumentIds = $removable->pluck('id')->all();
        $removableSaleIds = array_values(array_diff($removable->pluck('sale_id')->filter()->unique()->all(), $blockedSaleIds));

        if ($removableDocumentIds !== []) {
            $this->deleteByIds('sale_document_items', 'document_id', $removableDocumentIds);
            $this->deleteByIds('sale_documents', 'id', $removableDocumentIds);
        }

        if ($removableSaleIds !== []) {
            $this->deleteByIds('sale_products', 'sale_id', $removableSaleIds);
            $this->deleteByIds('sales', 'id', $removableSaleIds);
        }

        return [count($removableDocumentIds), $candidates->count() - count($removableDocumentIds)];
    }

    /** Borra las personas de prueba que ya no tengan ninguna referencia. */
    private function deleteTestPeople(array $personIds): array
    {
        if ($personIds === []) {
            return [0, 0];
        }

        $removed = 0;
        $kept = 0;

        foreach (Person::whereIn('id', $personIds)->get() as $person) {
            if ($this->personInUse($person->id)) {
                $kept++;
                continue;
            }

            $person->delete();
            $removed++;
        }

        return [$removed, $kept];
    }

    /** true si alguna tabla sigue apuntando a la persona. */
    private function personInUse(int $personId): bool
    {
        $references = [
            ['aca_school_students', 'person_id'],
            ['aca_school_enrollments', 'guardian_person_id'],
            ['aca_school_student_guardians', 'guardian_person_id'],
            ['aca_school_telegram_subscriptions', 'guardian_person_id'],
            ['aca_school_sections', 'tutor_person_id'],
            ['aca_school_sections', 'auxiliary_person_id'],
            ['aca_school_schedules', 'teacher_person_id'],
            ['aca_teachers', 'person_id'],
            ['aca_students', 'person_id'],
            ['sales', 'client_id'],
        ];

        foreach ($references as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $personId)->exists()) {
                return true;
            }
        }

        return Schema::hasTable('users') && Schema::hasColumn('users', 'person_id')
            && DB::table('users')->where('person_id', $personId)->exists();
    }

    /** @return array<int, int> */
    private function idsIn(string $table, string $column, array $values): array
    {
        if ($values === [] || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)->whereIn($column, $values)->pluck('id')->all();
    }

    /**
     * Ids de las filas de una tabla que cumplen cualquiera de las condiciones.
     *
     * @param  array<string, array<int, int>> $wheres
     * @return array<int, int>
     */
    private function relatedIds(string $table, array $wheres): array
    {
        if ($wheres === [] || ! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table);

        foreach ($wheres as $column => $values) {
            $query->orWhereIn($column, $values);
        }

        return $query->pluck('id')->all();
    }

    /** @param array<string, array<int, int>> $wheres */
    private function deleteRelated(string $table, array $wheres): int
    {
        return $this->deleteByIds($table, 'id', $this->relatedIds($table, $wheres));
    }

    /** Borra filas por id y devuelve cuantas elimino. */
    private function deleteByIds(string $table, string $column, array $ids): int
    {
        if ($ids === [] || ! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->whereIn($column, $ids)->delete();
    }

    /**
     * Borra las filas indicadas solo si ninguna otra tabla las referencia: se
     * usa con los catalogos compartidos (niveles y areas) para no romper datos
     * reales que el administrador haya colgado de ellos.
     */
    private function deleteByIdsIfFree(string $table, array $ids, string $referenceTable, string $referenceColumn): int
    {
        if ($ids === [] || ! Schema::hasTable($table)) {
            return 0;
        }

        $free = $ids;

        if (Schema::hasTable($referenceTable) && Schema::hasColumn($referenceTable, $referenceColumn)) {
            $used = DB::table($referenceTable)->whereIn($referenceColumn, $ids)->pluck($referenceColumn)->unique()->all();
            $free = array_values(array_diff($ids, $used));
        }

        return $this->deleteByIds($table, 'id', $free);
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
