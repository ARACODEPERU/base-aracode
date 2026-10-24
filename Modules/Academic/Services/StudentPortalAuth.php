<?php

namespace Modules\Academic\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Throwable;

/**
 * Acceso del alumnado al aula virtual.
 *
 * El identificador es el código de alumno (student_code) y la contraseña es
 * la del usuario del sistema. Cuando el alumno todavía no tiene cuenta, la
 * primera entrada crea el usuario con su número de documento como contraseña
 * inicial; después puede cambiarla desde «Mi perfil».
 *
 * El servicio recibe los códigos de nivel habilitados para que Inicial,
 * Primaria y Secundaria compartan exactamente la misma validación de
 * matrícula. La pantalla a la que se dirige cada nivel la decide el
 * controlador del aula.
 */
class StudentPortalAuth
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Valida código + contraseña y devuelve la matrícula activa del alumno.
     *
     * @param  array<int, string>  $levelCodes  Niveles habilitados; vacío acepta cualquier matrícula activa.
     * @return array{user?: User, student?: AcaSchoolStudent, enrollment?: AcaSchoolEnrollment, level?: AcaSchoolLevel, error?: string}
     */
    public function authenticate(string $code, string $password, array $levelCodes = []): array
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return ['error' => 'El colegio todavía no está configurado. Inténtalo más tarde.'];
        }

        $student = $this->findStudent($school->id, $code);

        if (! $student) {
            return ['error' => 'Ese código de alumno no existe en este colegio. Revísalo o pide apoyo en secretaría.'];
        }

        $document = $this->documentOf($student);
        $user = User::where('person_id', $student->person_id)->first();

        if ($user) {
            if (! Hash::check($password, $user->password)) {
                return ['error' => 'El código o la contraseña no son correctos. Revisa tus datos e inténtalo otra vez.'];
            }
        } else {
            // Primera entrada: la contraseña inicial es el número de DNI.
            if (! $document || ! hash_equals($document, trim($password))) {
                return ['error' => 'El código o la contraseña no son correctos. Tu contraseña inicial es tu número de DNI.'];
            }
        }

        $membership = $this->membershipForStudent($student, $levelCodes);

        if (isset($membership['error'])) {
            return $membership;
        }

        if (! $user) {
            $created = $this->createAccount($student, $document);

            if (isset($created['error'])) {
                return $created;
            }

            $user = $created['user'];
        }

        return [
            'user' => $user,
            'student' => $student,
            'enrollment' => $membership['enrollment'],
            'level' => $membership['level'],
        ];
    }

    /**
     * Matrícula activa del año vigente para los niveles habilitados.
     *
     * @param  array<int, string>  $levelCodes
     * @return array{student?: AcaSchoolStudent, enrollment?: AcaSchoolEnrollment, level?: AcaSchoolLevel, error?: string}
     */
    public function membership(User $user, array $levelCodes = []): array
    {
        if (! $user->person_id) {
            return ['error' => 'Tu cuenta no tiene una ficha de estudiante asociada. Pide apoyo en secretaría.'];
        }

        $school = $this->context->currentSchool();

        if (! $school) {
            return ['error' => 'El colegio todavía no está configurado. Inténtalo más tarde.'];
        }

        $student = AcaSchoolStudent::with('person')
            ->where('school_id', $school->id)
            ->where('person_id', $user->person_id)
            ->first();

        if (! $student) {
            return ['error' => 'No encontramos tu matrícula en este colegio. Pide apoyo en secretaría.'];
        }

        return $this->membershipForStudent($student, $levelCodes);
    }

    /**
     * Repone la contraseña del alumno a su número de DNI.
     *
     * Es lo que usa el administrador desde la lista de alumnos: si la cuenta
     * todavía no existe, se crea con esa misma contraseña. El alumno puede
     * cambiarla después desde «Mi perfil».
     *
     * @return array{error?: string}
     */
    public function resetPasswordToDocument(AcaSchoolStudent $student): array
    {
        $document = $this->documentOf($student);

        if (! $document) {
            return ['error' => 'El alumno no tiene DNI registrado. Actualiza su ficha antes de reponer la contraseña.'];
        }

        $user = User::where('person_id', $student->person_id)->first();

        if ($user) {
            $user->forceFill(['password' => Hash::make($document)])->save();

            return [];
        }

        $created = $this->createAccount($student, $document);

        return isset($created['error']) ? ['error' => $created['error']] : [];
    }

    /**
     * Cambia la contraseña del alumno autenticado.
     *
     * @return array{error?: string}
     */
    public function changePassword(User $user, string $current, string $new): array
    {
        if (! Hash::check($current, $user->password)) {
            return ['error' => 'Tu contraseña actual no es correcta.'];
        }

        if (Hash::check($new, $user->password)) {
            return ['error' => 'La nueva contraseña debe ser diferente a la que usas ahora.'];
        }

        $user->forceFill(['password' => Hash::make($new)])->save();

        return [];
    }

    /**
     * Crea la cuenta del alumno con la contraseña indicada (por defecto, su
     * DNI). Comparten esta lógica el primer ingreso del alumno y la reposición
     * de contraseña del administrador.
     *
     * @return array{user?: User, error?: string}
     */
    private function createAccount(AcaSchoolStudent $student, ?string $password = null): array
    {
        $student->loadMissing('person');
        $password = $password ?: $this->documentOf($student);

        if (! $password) {
            return ['error' => 'No pudimos crear tu acceso. Pide apoyo en secretaría para revisar tus datos.'];
        }

        try {
            return [
                'user' => User::create([
                    'name' => $student->person?->full_name ?: $student->student_code,
                    'email' => $this->uniqueEmail($student),
                    'password' => Hash::make($password),
                    'person_id' => $student->person_id,
                    'status' => true,
                ]),
            ];
        } catch (Throwable $e) {
            return ['error' => 'No pudimos crear tu acceso. Pide apoyo en secretaría para revisar tus datos.'];
        }
    }

    private function findStudent(int $schoolId, string $code): ?AcaSchoolStudent
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        return AcaSchoolStudent::with('person')
            ->where('school_id', $schoolId)
            ->whereRaw('LOWER(student_code) = ?', [mb_strtolower($code)])
            ->first();
    }

    /**
     * Primera matrícula activa cuyo nivel esté habilitado. Si el alumno tiene
     * matrícula pero su nivel no está habilitado, devuelve el nivel para que
     * el controlador explique qué ocurre.
     *
     * @param  array<int, string>  $levelCodes
     * @return array{student?: AcaSchoolStudent, enrollment?: AcaSchoolEnrollment, level?: AcaSchoolLevel|null, error?: string}
     */
    private function membershipForStudent(AcaSchoolStudent $student, array $levelCodes = []): array
    {
        $activeYear = AcaSchoolYear::where('school_id', $student->school_id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first();

        $enrollments = AcaSchoolEnrollment::with(['section.grade.level', 'year'])
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->when($activeYear, fn ($query) => $query->where('year_id', $activeYear->id))
            ->orderByDesc('id')
            ->get();

        if ($enrollments->isEmpty()) {
            return ['error' => $activeYear
                ? 'Tu matrícula no está activa en el año escolar ' . $activeYear->year . '. Consulta en secretaría.'
                : 'Aún no hay un año escolar activo. Consulta en secretaría.'];
        }

        $enrollment = $enrollments->first(
            fn (AcaSchoolEnrollment $item) => $levelCodes === []
                || in_array($item->section?->grade?->level?->code, $levelCodes, true)
        );

        if (! $enrollment) {
            $level = $enrollments->first()->section?->grade?->level;

            return [
                'error' => 'Tu matrícula pertenece al nivel ' . ($level?->name ?? 'otro nivel') . '.',
                'level' => $level,
            ];
        }

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'level' => $enrollment->section?->grade?->level,
        ];
    }

    private function documentOf(AcaSchoolStudent $student): ?string
    {
        $document = preg_replace('/\D+/', '', (string) $student->person?->number);

        return $document !== '' ? $document : null;
    }

    /**
     * Correo interno de la cuenta: nunca se muestra, solo debe ser único.
     * Se reutiliza el correo de la ficha si existe y está libre.
     */
    private function uniqueEmail(AcaSchoolStudent $student): string
    {
        $personEmail = trim((string) $student->person?->email);

        if ($personEmail !== '' && filter_var($personEmail, FILTER_VALIDATE_EMAIL)
            && ! User::where('email', $personEmail)->exists()) {
            return $personEmail;
        }

        $base = 'alumno.' . Str::slug($student->student_code) . '@aula.local';

        if (! User::where('email', $base)->exists()) {
            return $base;
        }

        return 'alumno.' . Str::slug($student->student_code) . '.' . Str::lower(Str::random(6)) . '@aula.local';
    }
}
