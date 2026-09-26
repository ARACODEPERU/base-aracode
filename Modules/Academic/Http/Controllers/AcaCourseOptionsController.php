<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaModality;

/**
 * Mantenedor de las opciones que alimentan los selects del formulario de
 * cursos: Categoria, Tipo, Sector y Modalidad.
 *
 * Categorias y Modalidades viven en tablas (aca_category_courses,
 * aca_modalities). Tipo y Sector son columnas ENUM de aca_courses, asi que
 * agregar, renombrar o quitar valores se hace con ALTER TABLE (misma tecnica
 * de la migracion 2026_08_17_000000_add_new_course_options.php).
 *
 * Ninguna opcion en uso puede eliminarse: en lugar de responder JSON (Inertia
 * pintaria un modal de error generico), las rutas de borrado redirigen de
 * vuelta al mantenedor con los parametros de "en uso" para que index() arme el
 * prop inUse con la lista de cursos; el aviso ofrece reasignar cada curso o
 * abrirlo en el editor.
 */
class AcaCourseOptionsController extends Controller
{
    /** Campos de curso que la reasignacion rapida puede tocar. */
    private const ASSIGNABLE_FIELDS = [
        'category_id' => 'integer',
        'modality_id' => 'integer',
        'type_description' => 'string',
        'sector_description' => 'string',
    ];

    /** Tipos de opcion que acepta la sonda ?in_use= (evita interpolar input). */
    private const IN_USE_KINDS = ['category', 'type', 'sector', 'modality'];

    public function index(Request $request)
    {
        return Inertia::render('Academic::Settings/CourseOptions', [
            'categories' => $this->categoryOptions(),
            'types' => $this->enumOptions('type_description'),
            'sectors' => $this->enumOptions('sector_description'),
            'modalities' => $this->modalityOptions(),
            'inUse' => $this->inUsePayloadFromQuery($request),
        ]);
    }

    public function storeCategory(Request $request)
    {
        AcaCategoryCourse::create($this->validatedDescription($request, 'aca_category_courses') + [
            'capacitation' => true,
            'image' => null,
        ]);

        return $this->backWithMessage('Categoria agregada correctamente.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = AcaCategoryCourse::findOrFail($id);

        $category->update($this->validatedDescription($request, 'aca_category_courses', $category->id));

        return $this->backWithMessage('Categoria actualizada correctamente.');
    }

    public function destroyCategory(int $id)
    {
        $category = AcaCategoryCourse::findOrFail($id);

        if (AcaCourse::where('category_id', $category->id)->exists()) {
            return redirect()->route('aca_course_options', ['in_use' => 'category', 'id' => $category->id]);
        }

        $category->delete();

        return $this->backWithMessage('Categoria eliminada correctamente.');
    }

    public function storeModality(Request $request)
    {
        // AcaModality tiene $fillable vacio, asi que se asigna columna por
        // columna en lugar de creacion masiva.
        $modality = new AcaModality();
        $modality->description = $this->validatedDescription($request, 'aca_modalities')['description'];
        $modality->status = true;
        $modality->save();

        return $this->backWithMessage('Modalidad agregada correctamente.');
    }

    public function updateModality(Request $request, int $id)
    {
        $modality = AcaModality::findOrFail($id);

        $modality->description = $this->validatedDescription($request, 'aca_modalities', $modality->id)['description'];
        $modality->save();

        return $this->backWithMessage('Modalidad actualizada correctamente.');
    }

    public function destroyModality(int $id)
    {
        $modality = AcaModality::findOrFail($id);

        if (AcaCourse::where('modality_id', $modality->id)->exists()) {
            return redirect()->route('aca_course_options', ['in_use' => 'modality', 'id' => $modality->id]);
        }

        // La FK de aca_cap_registrations (la tabla real de inscripciones) es
        // ON DELETE CASCADE: sin cursos apuntandole pero con inscripciones
        // historicas, borrar la fila eliminaria datos de alumnos, asi que se bloquea.
        $usedByRegistrations = DB::table('aca_cap_registrations')
            ->where('modality_id', $modality->id)->exists();

        if ($usedByRegistrations) {
            return redirect()->route('aca_course_options', ['in_use' => 'modality', 'id' => $modality->id]);
        }

        $modality->delete();

        return $this->backWithMessage('Modalidad eliminada correctamente.');
    }

    /**
     * Agrega un valor a la columna ENUM de Tipo o Sector. Los selects de todo
     * el sistema leen el ENUM via getEnumValues(), asi que el cambio es
     * inmediato en crear/editar cursos, filtros y el modulo Onlineshop.
     */
    public function storeEnum(Request $request)
    {
        [$column, $label] = $this->enumColumnOrFail($request);

        $value = $this->validatedDescription($request)['description'];
        $values = getEnumValues('aca_courses', $column);

        if (in_array($value, $values, true)) {
            return $this->backWithError("El valor ya existe en {$label}.");
        }

        $this->modifyEnum($column, array_merge($values, [$value]));

        return $this->backWithMessage(ucfirst($label).' agregado correctamente.');
    }

    /**
     * Renombra un valor ENUM y mueve a los cursos que lo usaban hacia el nuevo
     * valor, en una transaccion para que la tabla y los cursos queden
     * consistentes.
     */
    public function updateEnum(Request $request)
    {
        [$column, $label] = $this->enumColumnOrFail($request);

        $validated = $request->validate([
            'value' => 'required|string',
            'description' => ['required', 'string', 'max:100'],
        ]);

        $oldValue = trim($validated['value']);
        $newValue = trim($validated['description']);
        $values = getEnumValues('aca_courses', $column);

        if (! in_array($oldValue, $values, true)) {
            return $this->backWithError("El valor no existe en {$label}.");
        }

        if (strcasecmp($newValue, $oldValue) !== 0 && in_array($newValue, $values, true)) {
            return $this->backWithError("El valor ya existe en {$label}.");
        }

        // Nada de transacciones aqui: ALTER TABLE es DDL y MySQL lo confirma
        // implicitamente, lo que rompe la transaccion ("no active transaction").
        // Ademas el MODIFY no puede quitar el valor viejo antes de mover los
        // cursos: con sql_mode estricto MySQL fallaria con "Data truncated".
        // Secuencia segura: 1) agregar el valor nuevo junto al viejo,
        // 2) mover los cursos, 3) retirar el valor viejo.
        $final = array_map(
            fn (string $current) => strcasecmp($current, $oldValue) === 0 ? $newValue : $current,
            $values
        );

        $newValueExists = collect($values)
            ->contains(fn (string $current) => strcasecmp($current, $newValue) === 0);

        if ($newValueExists) {
            // Renombre dentro de la misma palabra (mayus/minus): el ALTER con la
            // lista final conserva los cursos por la colacion case-insensitive.
            $this->modifyEnum($column, $final);
            AcaCourse::where($column, $oldValue)->update([$column => $newValue]);
        } else {
            $this->modifyEnum($column, array_merge($values, [$newValue]));
            AcaCourse::where($column, $oldValue)->update([$column => $newValue]);
            $this->modifyEnum($column, $final);
        }

        return $this->backWithMessage(ucfirst($label).' actualizado correctamente.');
    }

    /**
     * Quita un valor de la columna ENUM de Tipo o Sector. Si algun curso lo
     * usa, redirige al aviso "en uso" en lugar de eliminar.
     */
    public function destroyEnum(Request $request)
    {
        [$column, $label] = $this->enumColumnOrFail($request);

        $value = trim((string) $request->input('value', ''));

        if ($value === '') {
            return $this->backWithError('El valor es obligatorio.');
        }

        if (AcaCourse::where($column, $value)->exists()) {
            return redirect()->route('aca_course_options', ['in_use' => $column === 'type_description' ? 'type' : 'sector', 'value' => $value]);
        }

        $values = getEnumValues('aca_courses', $column);
        $remaining = array_values(array_filter($values, fn (string $current) => strcasecmp($current, $value) !== 0));

        if (count($remaining) === count($values)) {
            return $this->backWithError("El valor no existe en {$label}.");
        }

        if ($remaining === []) {
            return $this->backWithError("No se puede eliminar el unico valor de {$label}.");
        }

        $this->modifyEnum($column, $remaining);

        return $this->backWithMessage(ucfirst($label).' eliminado correctamente.');
    }

    /**
     * Reasignacion rapida desde el aviso de eliminacion: cambia la opcion de un
     * curso concreto sin salir del mantenedor.
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:aca_courses,id',
            'field' => ['required', Rule::in(array_keys(self::ASSIGNABLE_FIELDS))],
            'value' => 'required',
        ]);

        $field = $validated['field'];
        $course = AcaCourse::findOrFail($validated['course_id']);
        $value = $validated['value'];

        if (self::ASSIGNABLE_FIELDS[$field] === 'integer') {
            $exists = $field === 'category_id'
                ? AcaCategoryCourse::whereKey($value)->exists()
                : AcaModality::whereKey($value)->exists();
        } else {
            $exists = in_array($value, getEnumValues('aca_courses', $field), true);
        }

        if (! $exists) {
            return $this->backWithError('La opción elegida ya no existe.');
        }

        $course->{$field} = $value;
        $course->save();

        // back() conserva los parametros ?in_use=... del referer, asi que el
        // aviso vuelve a abrirse con la lista de cursos ya actualizada.
        return redirect()->back()->with('message', 'Curso actualizado correctamente.');
    }

    // ------------------------------------------------------------- catalogos

    private function categoryOptions(): array
    {
        return AcaCategoryCourse::query()
            ->orderBy('description')
            ->get()
            ->map(fn (AcaCategoryCourse $category) => $this->optionRow(
                $category->id,
                $category->description,
                AcaCourse::where('category_id', $category->id)->count()
            ))
            ->all();
    }

    private function modalityOptions(): array
    {
        // El conteo suma cursos e inscripciones: la FK de aca_cap_registrations
        // es ON DELETE CASCADE y el borrado cascadaeria datos de inscripcion
        // aunque ningun curso apuntara a la modalidad.
        return AcaModality::query()
            ->orderBy('description')
            ->get()
            ->map(fn (AcaModality $modality) => $this->optionRow(
                $modality->id,
                $modality->description,
                AcaCourse::where('modality_id', $modality->id)->count()
                    + DB::table('aca_cap_registrations')->where('modality_id', $modality->id)->count()
            ))
            ->all();
    }

    private function enumOptions(string $column): array
    {
        $counts = AcaCourse::query()
            ->selectRaw("{$column} as value, count(*) as total")
            ->whereNotNull($column)
            ->groupBy($column)
            ->pluck('total', 'value');

        return collect(getEnumValues('aca_courses', $column))
            ->map(fn (string $value) => $this->optionRow($value, $value, (int) ($counts[$value] ?? 0)))
            ->values()
            ->all();
    }

    private function optionRow(int|string $id, string $description, int $useCount): array
    {
        return [
            'id' => $id,
            'description' => $description,
            'use_count' => $useCount,
        ];
    }

    // ----------------------------------------------------------- aviso en uso

    /**
     * Arma el prop inUse a partir de la query (?in_use=category&id=1,
     * ?in_use=type&value=Curso). Devuelve null cuando la peticion no pide el
     * aviso o la opcion ya no esta en uso, de modo que el aviso solo aparece
     * cuando corresponde.
     */
    private function inUsePayloadFromQuery(Request $request): ?array
    {
        $kind = $request->query('in_use');

        if (! in_array($kind, self::IN_USE_KINDS, true)) {
            return null;
        }

        if ($kind === 'category') {
            $category = AcaCategoryCourse::find($request->query('id'));

            if (! $category) {
                return null;
            }

            $courses = AcaCourse::where('category_id', $category->id)->orderBy('description')
                ->get(['id', 'description', 'category_id']);

            if ($courses->isEmpty()) {
                return null;
            }

            return $this->inUsePayload($courses, 'category_id', 'category', 'Categoría', $category->description);
        }

        if ($kind === 'modality') {
            $modality = AcaModality::find($request->query('id'));

            if (! $modality) {
                return null;
            }

            $courses = AcaCourse::where('modality_id', $modality->id)->orderBy('description')
                ->get(['id', 'description', 'modality_id']);

            if ($courses->isNotEmpty()) {
                return $this->inUsePayload($courses, 'modality_id', 'modality', 'Modalidad', $modality->description);
            }

            // Sin cursos pero con inscripciones historicas (FK en cascade).
            $usedByRegistrations = DB::table('aca_cap_registrations')
                ->where('modality_id', $modality->id)->exists();

            if (! $usedByRegistrations) {
                return null;
            }

            return [
                'kind' => 'modality',
                'field' => 'modality_id',
                'label' => $modality->description,
                'section' => 'Modalidad',
                'message' => 'La modalidad no puede eliminarse porque tiene inscripciones de alumnos asociadas.',
                'courses' => [],
            ];
        }

        // type | sector
        $column = $kind === 'type' ? 'type_description' : 'sector_description';
        $value = trim((string) $request->query('value', ''));

        if ($value === '') {
            return null;
        }

        $courses = AcaCourse::where($column, $value)->orderBy('description')
            ->get(['id', 'description', $column]);

        if ($courses->isEmpty()) {
            return null;
        }

        return $this->inUsePayload($courses, $column, $kind, $kind === 'type' ? 'Tipo' : 'Sector', $value);
    }

    private function inUsePayload($courses, string $field, string $kind, string $section, string $label): array
    {
        return [
            'kind' => $kind,
            'field' => $field,
            'label' => $label,
            'section' => $section,
            'message' => "No se puede eliminar \"{$label}\" porque {$courses->count()} curso(s) lo usan.",
            'courses' => $courses->map(fn ($course) => [
                'id' => $course->id,
                'description' => $course->description,
                'current_value' => $course->{$field},
                'edit_url' => route('aca_courses_edit', $course->id),
            ])->values()->all(),
        ];
    }

    // --------------------------------------------------------------- helpers

    private function validatedDescription(Request $request, ?string $uniqueTable = null, ?int $ignoredId = null): array
    {
        $rules = [
            'description' => ['required', 'string', 'max:100'],
        ];

        if ($uniqueTable !== null) {
            $rules['description'][] = Rule::unique($uniqueTable, 'description')->ignore($ignoredId);
        }

        return $request->validate($rules);
    }

    /**
     * ALTER TABLE de la columna ENUM con los valores indicados, escapando cada
     * uno igual que la migracion add_new_course_options.
     */
    private function modifyEnum(string $column, array $values): void
    {
        $enum = implode(',', array_map(fn (string $value) => "'".addslashes($value)."'", $values));

        DB::statement("ALTER TABLE aca_courses MODIFY COLUMN {$column} ENUM({$enum}) NULL");
    }

    /**
     * Solo acepta las dos columnas ENUM conocidas; jamas interpola un nombre de
     * columna elegido por el usuario en el ALTER TABLE.
     */
    private function enumColumnOrFail(Request $request): array
    {
        $field = $request->input('field');

        if (! in_array($field, ['type_description', 'sector_description'], true)) {
            abort(422, 'Campo ENUM invalido.');
        }

        return [$field, $field === 'type_description' ? 'Tipo' : 'Sector'];
    }

    private function backWithMessage(string $message)
    {
        return redirect()->route('aca_course_options')->with('message', $message);
    }

    private function backWithError(string $message)
    {
        return redirect()
            ->route('aca_course_options')
            ->with('error', $message)
            ->withInput();
    }
}
