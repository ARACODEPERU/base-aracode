<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Services\SchoolTestDataService;

/**
 * Pantalla Academico > Modos de Prueba.
 *
 * Disponible solo con tipo de negocio Colegio (5) o Colegio y capacitaciones
 * (6), y solo con el permiso aca_school_modo_prueba (concedido unicamente al
 * rol admin). Desde aqui se llena el colegio con datos de prueba, se borran
 * esos datos (y lo generado con ellos) y se deja el sistema en modo produccion.
 *
 * Validaciones de seguridad de cada boton:
 *
 * - Rutas: auth + verified + invalid_updated_information + user_activity_log y
 *   permission:aca_school_modo_prueba (ver Routes/web.php).
 * - Tipo de negocio: si P000009 no es 5 ni 6, cualquier acción responde 403.
 * - Modo produccion: con el bloqueo puesto, llenar/borrar devuelve error y la
 *   pantalla deshabilita los botones (el bloqueo no se revierte desde aqui).
 * - Confirmacion: borrar exige la frase exacta y dejar en producción exige la
 *   frase exacta MAS la contraseña del usuario conectado (current_password).
 * - Concurrencia: un candado de cache impide dos procesos de prueba a la vez.
 * - Atomicidad: el servicio hace todo dentro de una transaccion de base de
 *   datos, y solo borra lo que esta anotado en el registro del modo prueba.
 */
class AcaSchoolTestModeController extends Controller
{
    /** Frase exacta que se debe escribir para borrar los datos de prueba. */
    public const PURGE_CONFIRMATION = 'BORRAR DATOS DE PRUEBA';

    /** Frase exacta (con contraseña) para dejar el sistema en modo produccion. */
    public const PRODUCTION_CONFIRMATION = 'DEJAR EN MODO PRODUCCION';

    /** Segundos del candado que evita dos operaciones simultaneas. */
    private const LOCK_SECONDS = 120;

    public function __construct(private readonly SchoolTestDataService $testData)
    {
    }

    /**
     * Pantalla de modos de prueba (estado y botones).
     */
    public function index()
    {
        $this->ensureAvailable();

        return Inertia::render('Academic::School/TestMode/Index', [
            'summary' => $this->testData->summary(),
            'lock' => $this->testData->lockInfo(),
            'limits' => SchoolTestDataService::limits(),
            'confirmations' => [
                'purge' => self::PURGE_CONFIRMATION,
                'production' => self::PRODUCTION_CONFIRMATION,
            ],
            'businessType' => SchoolTestDataService::businessType(),
        ]);
    }

    /**
     * Llena el colegio con datos de prueba.
     */
    public function fill(Request $request): JsonResponse
    {
        $this->ensureAvailable();
        $this->ensureUnlocked();

        $data = $request->validate([
            'sections_per_grade' => ['nullable', 'integer', 'min:1', 'max:'.SchoolTestDataService::MAX_SECTIONS_PER_GRADE],
            'students_per_section' => ['nullable', 'integer', 'min:1', 'max:'.SchoolTestDataService::MAX_STUDENTS_PER_SECTION],
        ], [
            'sections_per_grade.max' => 'Como máximo '.SchoolTestDataService::MAX_SECTIONS_PER_GRADE.' secciones por grado.',
            'students_per_section.max' => 'Como máximo '.SchoolTestDataService::MAX_STUDENTS_PER_SECTION.' alumnos por sección.',
        ]);

        $result = $this->runExclusive('fill', fn (): array => $this->testData->fill($data));

        return $this->respond('Datos de prueba creados: '.$result['total'].' registros.', $result);
    }

    /**
     * Borra los datos de prueba y todo lo generado con ellos.
     */
    public function purge(Request $request): JsonResponse
    {
        $this->ensureAvailable();
        $this->ensureUnlocked();

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in([self::PURGE_CONFIRMATION])],
        ], [
            'confirmation.required' => 'Para borrar los datos de prueba escribe la confirmación.',
            'confirmation.in' => 'La confirmación no coincide: escribe exactamente "'.self::PURGE_CONFIRMATION.'".',
        ]);

        $result = $this->runExclusive('purge', fn (): array => $this->testData->purge());

        return $this->respond($this->purgeMessage('Datos de prueba eliminados', $result), $result);
    }

    /**
     * Borra los datos de prueba y deja el sistema en modo produccion.
     *
     * Exige la frase de confirmacion y la contraseña del usuario conectado: es
     * la accion mas delicada (borra informacion y bloquea el modo prueba para
     * siempre desde la interfaz).
     */
    public function production(Request $request): JsonResponse
    {
        $this->ensureAvailable();
        $this->ensureUnlocked();

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in([self::PRODUCTION_CONFIRMATION])],
            'password' => ['required', 'string', 'current_password'],
        ], [
            'confirmation.required' => 'Para dejar el sistema en modo producción escribe la confirmación.',
            'confirmation.in' => 'La confirmación no coincide: escribe exactamente "'.self::PRODUCTION_CONFIRMATION.'".',
            'password.required' => 'Escribe tu contraseña para confirmar la operación.',
            'password.current_password' => 'La contraseña no coincide con la de tu usuario.',
        ]);

        $user = $request->user();

        $result = $this->runExclusive(
            'production',
            fn (): array => $this->testData->lockProduction($user?->id, $user?->name)
        );

        return $this->respond(
            $this->purgeMessage('El sistema quedó en modo producción', $result)
                .' Los botones de llenado y borrado quedaron bloqueados definitivamente.',
            $result
        );
    }

    /* ------------------------------------------------------------------
     * Validaciones y respuestas
     * ------------------------------------------------------------------ */

    /** Solo con tipo de negocio Colegio (5) o Colegio y capacitaciones (6). */
    private function ensureAvailable(): void
    {
        if (! $this->testData->isAvailable()) {
            abort(403, 'Los modos de prueba solo están disponibles cuando el tipo de negocio (P000009) es Colegio o Colegio y capacitaciones.');
        }
    }

    /** Con el modo produccion puesto ya no se crea ni se borra nada. */
    private function ensureUnlocked(): void
    {
        if ($this->testData->isLocked()) {
            throw ValidationException::withMessages([
                'confirmation' => 'El sistema está en modo producción: ya no se pueden crear ni borrar datos de prueba.',
            ]);
        }
    }

    /**
     * Ejecuta la operacion bajo un candado: si ya hay otra en curso se avisa en
     * lugar de lanzar dos procesos sobre los mismos datos.
     */
    private function runExclusive(string $action, callable $callback): array
    {
        $lock = Cache::lock('academic-test-mode:'.$action, self::LOCK_SECONDS);

        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'confirmation' => 'Ya hay una operación de modo de prueba en curso: espera a que termine e inténtalo otra vez.',
            ]);
        }

        try {
            return $callback();
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['confirmation' => $exception->getMessage()]);
        } finally {
            $lock->release();
        }
    }

    /** Respuesta JSON del panel (las acciones se envian por axios). */
    private function respond(string $message, array $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'result' => $result,
        ]);
    }

    /** Mensaje final del borrado, avisando lo que se conservo. */
    private function purgeMessage(string $title, array $result): string
    {
        $message = $title.': '.array_sum($result['deleted']).' registros eliminados.';

        if (($result['documents_kept'] ?? 0) > 0) {
            $message .= ' Se conservaron '.$result['documents_kept'].' comprobante(s) enviados a SUNAT.';
        }

        if (($result['people_kept'] ?? 0) > 0) {
            $message .= ' Se conservaron '.$result['people_kept'].' persona(s) de prueba que siguen en uso.';
        }

        return $message;
    }
}
