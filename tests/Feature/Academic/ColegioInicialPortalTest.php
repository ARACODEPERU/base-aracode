<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaSchool;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGrade;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualLesson;
use Modules\Academic\Entities\AcaVirtualResource;
use Modules\Academic\Entities\AcaVirtualUnit;
use Tests\TestCase;

/**
 * Portal del alumno de Inicial: el login solo deja pasar a matrículas activas
 * del nivel y el aula muestra los cursos configurados por el administrador.
 */
class ColegioInicialPortalTest extends TestCase
{
    use RefreshDatabase;

    private AcaSchool $school;

    private AcaSchoolYear $year;

    private AcaSchoolSection $section;

    private AcaSchoolStudent $student;

    private User $user;

    private AcaVirtualCourse $course;

    private AcaVirtualUnit $unit;

    private AcaVirtualLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = AcaSchool::defaultSchool();
        $this->school->update(['name' => 'Colegio Semilla', 'status' => true]);

        $this->year = AcaSchoolYear::create([
            'school_id' => $this->school->id,
            'year' => 2027,
            'status' => AcaSchoolYear::STATUS_ACTIVE,
        ]);

        $level = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'code' => AcaSchoolLevel::INICIAL,
            'name' => 'Inicial',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'level_id' => $level->id,
            'name' => '4 años',
            'status' => true,
        ]);

        $this->section = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
            'capacity' => 20,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        [$this->student, $this->user] = $this->makeStudent('Lucía', 'García', '87654321', 'lucia@colegio.test');

        AcaSchoolEnrollment::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'student_id' => $this->student->id,
            'section_id' => $this->section->id,
            'type' => AcaSchoolEnrollment::TYPE_NUEVA,
            'status' => AcaSchoolEnrollment::STATUS_ACTIVO,
            'enrollment_date' => now()->toDateString(),
        ]);

        $area = AcaSchoolArea::create([
            'school_id' => $this->school->id,
            'name' => 'Desarrollo personal, social y comunicación',
            'level' => AcaSchoolArea::LEVEL_INICIAL,
            'sort_order' => 1,
            'status' => true,
        ]);

        $this->course = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'section_id' => $this->section->id,
            'area_id' => $area->id,
            'name' => null,
            'status' => true,
            'sort_order' => 1,
        ]);

        $this->unit = AcaVirtualUnit::create([
            'course_id' => $this->course->id,
            'title' => 'Unidad de bienvenida',
            'purpose' => 'Reconocer a los amigos del aula.',
            'duration_hours' => 4,
            'sort_order' => 1,
            'status' => true,
        ]);

        $this->lesson = AcaVirtualLesson::create([
            'unit_id' => $this->unit->id,
            'title' => 'Sesión de los nombres',
            'purpose' => 'Aprender el nombre de cada compañero.',
            'development' => 'Jugamos con tarjetas de nombres.',
            'duration_minutes' => 45,
            'sort_order' => 1,
            'status' => true,
        ]);
    }

    public function test_el_alumno_entra_con_su_codigo_y_su_contrasena(): void
    {
        $response = $this->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => 'inicial123',
        ]);

        $response->assertRedirect(route('colegio.inicial.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_la_primera_entrada_usa_el_dni_y_crea_la_cuenta_del_alumno(): void
    {
        // Alumno sin cuenta todavía: entra con su código y su DNI.
        User::where('person_id', $this->student->person_id)->delete();

        $response = $this->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => '87654321',
        ]);

        $response->assertRedirect(route('colegio.inicial.dashboard'));

        $user = User::where('person_id', $this->student->person_id)->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('87654321', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_el_correo_y_el_dni_no_sirven_como_codigo_de_alumno(): void
    {
        foreach (['lucia@colegio.test', '87654321'] as $identificador) {
            $response = $this->from(route('colegio.inicial.login'))->post(route('colegio.inicial.login.store'), [
                'codigo' => $identificador,
                'clave' => 'inicial123',
            ]);

            $response->assertRedirect(route('colegio.inicial.login'))
                ->assertSessionHasErrors('codigo');

            $this->assertGuest();
        }
    }

    public function test_un_alumno_sin_dni_no_puede_entrar_la_primera_vez(): void
    {
        User::where('person_id', $this->student->person_id)->delete();
        // La columna no admite NULL: sin DNI equivale a un número vacío.
        $this->student->person->update(['number' => '']);

        $response = $this->from(route('colegio.inicial.login'))->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => '87654321',
        ]);

        $response->assertRedirect(route('colegio.inicial.login'))
            ->assertSessionHasErrors('codigo');

        $this->assertGuest();
        $this->assertStringContainsString('contraseña inicial es tu número de DNI', session('errors')->first('codigo'));
    }

    public function test_una_matricula_de_primaria_no_entra_por_el_acceso_de_inicial(): void
    {
        $primaria = AcaSchoolLevel::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'code' => AcaSchoolLevel::PRIMARIA,
            'name' => 'Primaria',
            'status' => true,
        ]);

        $grade = AcaSchoolGrade::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'level_id' => $primaria->id,
            'name' => '1°',
            'status' => true,
        ]);

        $section = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'B',
            'capacity' => 20,
            'shift' => AcaSchoolSection::SHIFT_MANANA,
            'status' => true,
        ]);

        // La matrícula del año es única por alumno: se traslada a Primaria.
        AcaSchoolEnrollment::where('student_id', $this->student->id)->update([
            'section_id' => $section->id,
            'type' => AcaSchoolEnrollment::TYPE_PROMOVIDA,
        ]);

        $response = $this->from(route('colegio.inicial.login'))->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => 'inicial123',
        ]);

        $response->assertRedirect(route('colegio.inicial.login'))
            ->assertSessionHasErrors('codigo');

        $this->assertGuest();

        $this->assertStringContainsString(
            'nivel Primaria',
            session('errors')->first('codigo')
        );
    }

    public function test_un_alumno_sin_matricula_activa_no_entra_al_aula(): void
    {
        $this->section->enrollments()->update(['status' => AcaSchoolEnrollment::STATUS_RETIRADO]);

        $response = $this->from(route('colegio.inicial.login'))->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => 'inicial123',
        ]);

        $response->assertRedirect(route('colegio.inicial.login'))
            ->assertSessionHasErrors('codigo');

        $this->assertGuest();
        $this->assertStringContainsString('no está activa', session('errors')->first('codigo'));
    }

    public function test_una_contrasena_incorrecta_no_abre_la_sesion(): void
    {
        $response = $this->from(route('colegio.inicial.login'))->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => 'no-es-la-clave',
        ]);

        $response->assertRedirect(route('colegio.inicial.login'))
            ->assertSessionHasErrors('codigo');

        $this->assertGuest();
    }

    public function test_el_dashboard_muestra_los_cursos_y_sus_conteos(): void
    {
        $response = $this->actingAs($this->user)->get(route('colegio.inicial.dashboard'));

        $response->assertOk()
            ->assertSee('Lucía')
            ->assertSee('Desarrollo personal, social y comunicación')
            ->assertSee('4 años · Aula A')
            ->assertSee('Juegos')
            // Las unidades y sesiones se abren dentro del curso, no en el panel.
            ->assertDontSee('Unidad de bienvenida')
            ->assertDontSee('Sesión de los nombres');
    }

    public function test_la_vista_de_curso_muestra_unidades_y_sesiones_configuradas(): void
    {
        $response = $this->actingAs($this->user)->get(route('colegio.inicial.course', $this->course->id));

        $response->assertOk()
            ->assertSee('Mis unidades y sesiones')
            ->assertSee('Unidad de bienvenida')
            ->assertSee('Sesión de los nombres')
            ->assertSee('Aprender el nombre de cada compañero.')
            ->assertSee('Jugamos con tarjetas de nombres.')
            ->assertSee('45 minutos');
    }

    public function test_la_vista_de_curso_trae_el_modal_con_todos_los_materiales(): void
    {
        Storage::fake('public');

        $ficha = $this->makeResource([
            'title' => 'Ficha de nombres',
            'path' => 'materiales/ficha.pdf',
            'mime_type' => 'application/pdf',
            'size_kb' => 2048,
        ]);

        $foto = $this->makeResource([
            'title' => 'Foto del aula',
            'unit_id' => $this->unit->id,
            'lesson_id' => $this->lesson->id,
            'path' => 'materiales/aula.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($this->user)->get(route('colegio.inicial.course', $this->course->id));

        $response->assertOk()
            ->assertSee('materialDialog', false)
            ->assertSee('Materiales de apoyo')
            ->assertSee('2 materiales disponibles')
            ->assertSee('Ficha de nombres')
            ->assertSee('Foto del aula')
            ->assertSee(route('colegio.inicial.material.view', $ficha->id), false)
            ->assertSee(route('colegio.inicial.material.download', $foto->id), false);
    }

    public function test_el_alumno_ve_la_imagen_del_material_en_el_visor(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materiales/aula.png', 'contenido-de-la-imagen');

        $resource = $this->makeResource([
            'title' => 'Foto del aula',
            'path' => 'materiales/aula.png',
            'mime_type' => 'image/png',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('colegio.inicial.material.view', $resource->id));

        $response->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->assertSame(
            'contenido-de-la-imagen',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_el_alumno_descarga_el_material_con_un_nombre_legible(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materiales/ficha.pdf', '%PDF-1.4');

        $resource = $this->makeResource([
            'title' => 'Ficha de nombres',
            'path' => 'materiales/ficha.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->actingAs($this->user)
            ->get(route('colegio.inicial.material.download', $resource->id))
            ->assertOk()
            ->assertDownload('ficha-de-nombres.pdf');
    }

    public function test_el_material_de_un_curso_ajeno_no_se_puede_abrir(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materiales/ajeno.pdf', '%PDF-1.4');

        $otraSeccion = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $this->section->grade_id,
            'name' => 'Z',
            'capacity' => 20,
            'shift' => AcaSchoolSection::SHIFT_TARDE,
            'status' => true,
        ]);

        $ajeno = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'section_id' => $otraSeccion->id,
            'area_id' => AcaSchoolArea::where('level', AcaSchoolArea::LEVEL_INICIAL)->firstOrFail()->id,
            'status' => true,
        ]);

        $resource = $this->makeResource([
            'course_id' => $ajeno->id,
            'title' => 'Material de otra aula',
            'path' => 'materiales/ajeno.pdf',
        ]);

        $this->actingAs($this->user)
            ->get(route('colegio.inicial.material.view', $resource->id))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('colegio.inicial.material.download', $resource->id))
            ->assertNotFound();
    }

    public function test_el_alumno_no_puede_abrir_el_curso_de_otra_seccion(): void
    {
        $otraSeccion = AcaSchoolSection::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'grade_id' => $this->section->grade_id,
            'name' => 'Z',
            'capacity' => 20,
            'shift' => AcaSchoolSection::SHIFT_TARDE,
            'status' => true,
        ]);

        $area = AcaSchoolArea::where('level', AcaSchoolArea::LEVEL_INICIAL)->firstOrFail();

        $ajeno = AcaVirtualCourse::create([
            'school_id' => $this->school->id,
            'year_id' => $this->year->id,
            'section_id' => $otraSeccion->id,
            'area_id' => $area->id,
            'status' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('colegio.inicial.course', $ajeno->id))
            ->assertNotFound();
    }

    public function test_el_invitado_es_redirigido_al_login_del_aula(): void
    {
        $this->get(route('colegio.inicial.dashboard'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->get(route('colegio.inicial.games'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->get(route('colegio.inicial.puzzle'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->get(route('colegio.inicial.fruits'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->get(route('colegio.inicial.letters'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->get(route('colegio.inicial.colors'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->get(route('colegio.inicial.memory'))
            ->assertRedirect(route('colegio.inicial.login'));
    }

    public function test_la_zona_de_juegos_muestra_la_vista_previa(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.games'))
            ->assertOk()
            ->assertSee('Aprender jugando')
            ->assertSee('Próximamente')
            ->assertSee('Memoria de animales');
    }

    public function test_la_tarjeta_del_rompecabezas_lleva_al_juego(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.games'))
            ->assertOk()
            ->assertSee('Rompecabezas')
            ->assertSee(route('colegio.inicial.puzzle'), false)
            ->assertSee('4 niveles');
    }

    public function test_el_rompecabezas_se_abre_con_sus_ilustraciones(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('colegio.inicial.puzzle'));

        $response->assertOk()
            ->assertSee('data-juego="rompecabezas"', false)
            ->assertSee(asset('themes/colegio/js/juegos/rompecabezas.js'), false)
            ->assertSee(asset('themes/colegio/img/juegos/sol.svg'), false)
            ->assertSee('Volver a los juegos');

        // Las cuatro ilustraciones de los niveles se sirven desde el tema.
        foreach (['sol.svg', 'gato.svg', 'granja.svg', 'parque.svg'] as $illustration) {
            $this->assertFileExists(public_path('themes/colegio/img/juegos/' . $illustration));
        }
    }

    public function test_la_tarjeta_de_contando_frutas_lleva_al_juego(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.games'))
            ->assertOk()
            ->assertSee('Contando frutas')
            ->assertSee(route('colegio.inicial.fruits'), false)
            ->assertSee('10 niveles · del 1 al 10');
    }

    public function test_contando_frutas_se_abre_con_sus_frutas(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.fruits'))
            ->assertOk()
            ->assertSee('data-juego="contando-frutas"', false)
            ->assertSee(asset('themes/colegio/js/juegos/contando-frutas.js'), false)
            ->assertSee(asset('themes/colegio/img/juegos/frutas'), false)
            ->assertSee('Volver a los juegos')
            ->assertSee('Niveles');

        // Las seis frutas del juego se sirven desde el tema.
        foreach (['manzana', 'banana', 'naranja', 'fresa', 'pera', 'uvas'] as $fruit) {
            $this->assertFileExists(public_path('themes/colegio/img/juegos/frutas/' . $fruit . '.svg'));
        }
    }

    public function test_la_tarjeta_de_reconoce_la_letra_lleva_al_juego(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.games'))
            ->assertOk()
            ->assertSee('Reconoce la letra')
            ->assertSee(route('colegio.inicial.letters'), false)
            ->assertSee('10 niveles · voz y teclado')
            ->assertDontSee('Traza las letras');
    }

    public function test_reconoce_la_letra_se_abre_con_su_teclado(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.letters'))
            ->assertOk()
            ->assertSee('data-juego="reconoce-letra"', false)
            ->assertSee(asset('themes/colegio/js/juegos/reconoce-letra.js'), false)
            ->assertSee('Volver a los juegos')
            ->assertSee('Niveles');
    }

    public function test_la_tarjeta_de_colores_y_formas_lleva_al_juego(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.games'))
            ->assertOk()
            ->assertSee('Colores y formas')
            ->assertSee(route('colegio.inicial.colors'), false)
            ->assertSee('10 niveles · colores y figuras');
    }

    public function test_colores_y_formas_se_abre_con_su_tablero(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.colors'))
            ->assertOk()
            ->assertSee('data-juego="colores-formas"', false)
            ->assertSee(asset('themes/colegio/js/juegos/colores-formas.js'), false)
            ->assertSee('Volver a los juegos')
            ->assertSee('Niveles');
    }

    public function test_la_tarjeta_de_memoria_de_animales_lleva_al_juego(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.games'))
            ->assertOk()
            ->assertSee('Memoria de animales')
            ->assertSee(route('colegio.inicial.memory'), false)
            ->assertSee('10 niveles · sonidos y parejas')
            ->assertSee(asset('themes/colegio/img/juegos/animales/perro.jpg'), false);
    }

    public function test_memoria_de_animales_se_abre_con_su_configuracion(): void
    {
        $this->actingAs($this->user)
            ->get(route('colegio.inicial.memory'))
            ->assertOk()
            ->assertSee('data-juego="memoria-animales"', false)
            ->assertSee(asset('themes/colegio/js/juegos/memoria-animales.js'), false)
            ->assertSee('id="animalesConfig"', false)
            ->assertSee('Créditos y licencias')
            ->assertSee('Wikimedia Commons')
            ->assertSee('Volver a los juegos')
            ->assertSee('Niveles');
    }

    public function test_los_animales_tienen_fotografia_audio_y_creditos_locales(): void
    {
        $animals = config('colegio_animales.animals');
        $required = [
            'perro', 'gato', 'gallina', 'vaca', 'cerdo', 'oveja', 'pato',
            'caballo', 'gallo', 'leon', 'elefante', 'mono', 'rana', 'pajaro', 'buho',
        ];

        $this->assertSame($required, array_keys($animals));
        $this->assertCount(15, $animals);

        $photos = [];
        $audios = [];

        foreach ($animals as $id => $animal) {
            $this->assertSame($id, $animal['id']);
            $this->assertNotSame('', trim($animal['name']));
            $this->assertContains($animal['group'], ['casa', 'granja', 'selva', 'bosque']);

            foreach (['photo', 'audio'] as $field) {
                $path = $animal[$field];
                $this->assertStringStartsWith('themes/colegio/', $path);
                $this->assertFileExists(public_path($path));
                $this->assertGreaterThan(8000, (int) filesize(public_path($path)));
            }

            $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->file(public_path($animal['photo'])));
            $this->assertSame('audio/mpeg', (new \finfo(FILEINFO_MIME_TYPE))->file(public_path($animal['audio'])));

            $dimensions = getimagesize(public_path($animal['photo']));
            $this->assertIsArray($dimensions);
            $this->assertGreaterThanOrEqual(600, $dimensions[0]);
            $this->assertGreaterThanOrEqual(400, $dimensions[1]);

            foreach (['photo_credit', 'audio_credit'] as $credit) {
                $this->assertNotSame('', trim($animal[$credit]['author']));
                $this->assertNotSame('', trim($animal[$credit]['license']));
                $this->assertStringStartsWith(
                    'https://commons.wikimedia.org/wiki/File:',
                    $animal[$credit]['page']
                );

                if ($animal[$credit]['license_url'] !== '') {
                    $this->assertStringStartsWith('https://creativecommons.org/', $animal[$credit]['license_url']);
                }
            }

            $photos[] = $animal['photo'];
            $audios[] = $animal['audio'];
        }

        $this->assertCount(15, array_unique($photos));
        $this->assertCount(15, array_unique($audios));
    }

    public function test_cerrar_sesion_vuelve_al_login_del_aula(): void
    {
        $this->actingAs($this->user)
            ->post(route('colegio.inicial.logout'))
            ->assertRedirect(route('colegio.inicial.login'));

        $this->assertGuest();
    }

    public function test_el_login_muestra_el_error_y_abre_el_formulario(): void
    {
        $this->from(route('colegio.inicial.login'))->post(route('colegio.inicial.login.store'), [
            'codigo' => '2026-0001',
            'clave' => 'incorrecta',
        ]);

        $this->get(route('colegio.inicial.login'))
            ->assertOk()
            ->assertSee('El código o la contraseña no son correctos.')
            ->assertSee('data-open-login="true"', false);
    }

    /**
     * Material de apoyo del curso con los valores minimos del aula virtual.
     */
    private function makeResource(array $attributes = []): AcaVirtualResource
    {
        return AcaVirtualResource::create(array_merge([
            'course_id' => $this->course->id,
            'unit_id' => null,
            'lesson_id' => null,
            'type' => 'file',
            'title' => 'Material de prueba',
            'path' => 'materiales/material.pdf',
            'mime_type' => 'application/pdf',
            'size_kb' => 1024,
            'visibility' => 'students',
            'sort_order' => 1,
            'status' => true,
        ], $attributes));
    }

    /**
     * @return array{0: AcaSchoolStudent, 1: User}
     */
    private function makeStudent(string $names, string $lastname, string $document, string $email): array
    {
        $person = Person::create([
            'names' => $names,
            'father_lastname' => $lastname,
            'mother_lastname' => 'Ramos',
            'full_name' => $names . ' ' . $lastname . ' Ramos',
            'number' => $document,
            'email' => $email,
        ]);

        $user = User::create([
            'name' => $names . ' ' . $lastname,
            'email' => $email,
            'password' => Hash::make('inicial123'),
            'person_id' => $person->id,
            'status' => true,
        ]);

        $student = AcaSchoolStudent::create([
            'school_id' => $this->school->id,
            'person_id' => $person->id,
            'student_code' => AcaSchoolStudent::nextStudentCode($this->school->id),
            'status' => true,
        ]);

        return [$student, $user];
    }
}
