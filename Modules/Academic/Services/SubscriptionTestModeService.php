<?php

namespace Modules\Academic\Services;

use App\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Academic\Emails\SubscriptionRenewed;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionTestLog;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Entities\MercadoPagoEvent;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Services\TreasuryIntegrationService;
use RuntimeException;

/**
 * Modo prueba de los cobros recurrentes de un plan.
 *
 * Flujo que reproduce el panel:
 * 1. start(): crea un alumno de prueba y una suscripción (preapproval) en el
 *    ambiente de PRUEBA de MercadoPago con una tarjeta de prueba. El primer
 *    cobro queda pendiente de conciliar y lo acredita el webhook real.
 * 2. renewal(): simula el cobro del periodo siguiente con el mismo servicio que usa
 *    el webhook, para ver el registro en Tesorería, la extensión del acceso y
 *    el correo de renovación.
 * 3. cancel(): cancela la suscripción en MercadoPago y desactiva el acceso.
 * 4. purge(): borra todo lo marcado como prueba (pagos, ventas, ingresos,
 *    suscripciones y alumno de prueba).
 *
 * Nunca usa credenciales de producción: si el token de prueba coincide con el
 * de producción, se bloquea.
 */
class SubscriptionTestModeService
{
    private const MP_BASE = 'https://api.mercadopago.com';

    public function __construct(
        private readonly SubscriptionChargeService $charges,
        private readonly SubscriptionPreapprovalService $preapprovals,
    ) {
    }

    /**
     * Estado de las credenciales y de los datos de prueba del plan.
     *
     * @return array<string, mixed>
     */
    public function state(AcaSubscriptionType $plan, ?string $defaultEmail = null): array
    {
        $credenciales = $this->credentials();

        $preapprovals = AcaSubscriptionPreapproval::query()
            ->where('subscription_id', $plan->id)
            ->where('is_test', true)
            ->latest('id')
            ->take(5)
            ->get();

        $pagos = AcaSubscriptionPayment::query()
            ->where('subscription_id', $plan->id)
            ->where('is_test', true)
            ->latest('id')
            ->take(10)
            ->get();

        return [
            'enabled' => (bool) $plan->test_mode,
            'credentials' => $credenciales,
            'default_email' => $defaultEmail ?: (string) (Auth::user()?->email ?? ''),
            'preapprovals' => $preapprovals,
            'payments' => $pagos,
            'logs' => AcaSubscriptionTestLog::query()
                ->where('subscription_id', $plan->id)
                ->latest('id')
                ->take(20)
                ->get(),
            'events' => MercadoPagoEvent::query()
                ->latest('id')
                ->take(10)
                ->get(['id', 'topic', 'data_id', 'processed_at', 'error', 'created_at']),
        ];
    }

    /**
     * Credenciales de prueba disponibles (sin exponer los valores).
     *
     * @return array{ready: bool, missing: array<int, string>, same_as_production: bool, source: string|null, edit_url: string}
     */
    public function credentials(): array
    {
        $tokenProd = trim((string) config('services.mercadopago.token'));
        ['token' => $token, 'public_key' => $publicKey] = $this->testCredentials();

        $faltantes = [];
        if ($token === '') {
            $faltantes[] = 'test_access_token';
        }
        if ($publicKey === '') {
            $faltantes[] = 'test_public_key';
        }

        $mismoQueProduccion = $token !== '' && $tokenProd !== '' && hash_equals($tokenProd, $token);

        return [
            'ready' => $faltantes === [] && ! $mismoQueProduccion,
            'missing' => $faltantes,
            'same_as_production' => $mismoQueProduccion,
            'source' => $this->hubValue('test_access_token') ? 'integrationhub' : ($token !== '' ? 'env' : null),
            'edit_url' => $this->integrationEditUrl(),
        ];
    }

    /**
     * Crea la suscripción de prueba en MercadoPago (ambiente de prueba).
     *
     * @return array<string, mixed>
     */
    public function start(AcaSubscriptionType $plan, string $email): array
    {
        $this->assertPlan($plan);

        $credenciales = $this->credentials();
        if (! $credenciales['ready']) {
            throw new RuntimeException($this->missingCredentialsMessage($credenciales));
        }

        $email = trim($email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Escribe un correo válido para el alumno de prueba.');
        }

        $tokens = $this->testCredentials();

        return $this->withLock($plan, function () use ($plan, $email, $credenciales, $tokens): array {
            $existente = AcaSubscriptionPreapproval::query()
                ->where('subscription_id', $plan->id)
                ->where('is_test', true)
                ->whereIn('status', ['authorized', 'pending'])
                ->latest('id')
                ->first();

            if ($existente) {
                return [
                    'already' => true,
                    'preapproval_id' => $existente->preapproval_id,
                    'message' => 'Ya hay una suscripción de prueba vigente para este plan. Usa «Simular renovación» o elimínala primero.',
                ];
            }

            $monto = $this->preapprovals->amount($plan);
            if ($monto <= 0) {
                throw new RuntimeException('El plan no tiene un precio en soles configurado para la prueba.');
            }

            [
                'student' => $alumno,
                'created_person' => $personaCreada,
                'created_student' => $alumnoCreado,
            ] = $this->testStudent($plan, $email);

            $token = $this->cardToken($tokens);
            $respuesta = $this->createPreapproval($plan, $tokens, $token, $alumno, $monto);

            $preapproval = AcaSubscriptionPreapproval::create([
                'student_id' => $alumno->id,
                'subscription_id' => $plan->id,
                'preapproval_id' => $respuesta['id'],
                'status' => $respuesta['status'] ?: 'pending',
                'amount' => round($monto, 2),
                'currency_id' => 'PEN',
                'frequency' => $plan->recurringMonths() ?? 1,
                'frequency_type' => 'months',
                'billing_day' => min(28, (int) now()->format('j')),
                'start_date' => now()->toDateString(),
                'next_payment_date' => $this->nextPaymentDate($respuesta['next_payment_date'] ?? null, (int) $plan->recurringMonths()),
                'payer_email' => $email,
                'external_reference' => 'aca:test:student:' . $alumno->id . ':plan:' . $plan->id,
                'invoice_data' => [
                    'document_type' => 'DNI',
                    'ruc' => '12345678',
                    'payer_email' => $email,
                    'origen' => 'modo_prueba',
                    'capturado_at' => now()->toDateTimeString(),
                    'person_id' => $alumno->person_id,
                    'created_person' => $personaCreada,
                    'created_student' => $alumnoCreado,
                ],
                'raw' => $respuesta['raw'] ?? [],
                'last_synced_at' => now(),
                'is_test' => true,
            ]);

            // Primer cobro pendiente: lo concilia el webhook real cuando llega.
            AcaSubscriptionPayment::create([
                'student_id' => $alumno->id,
                'subscription_id' => $plan->id,
                'date_start' => now()->toDateString(),
                'date_end' => now()->copy()->addMonths((int) ($plan->recurringMonths() ?? 1))->toDateString(),
                'amount' => round($monto, 2),
                'preapproval_id' => $preapproval->preapproval_id,
                'status' => 'pending',
                'source' => 'recurring',
                'is_test' => true,
            ]);

            AcaSubscriptionTestLog::record(
                $plan->id,
                'created',
                'Suscripción de prueba creada en MercadoPago. Esperando el webhook del primer cobro.',
                [
                    'preapproval_id' => $preapproval->preapproval_id,
                    'email' => $email,
                    'amount' => round($monto, 2),
                ],
                'success',
                $preapproval->preapproval_id,
            );

            return [
                'already' => false,
                'preapproval_id' => $preapproval->preapproval_id,
                'status' => $preapproval->status,
                'email' => $email,
                'amount' => round($monto, 2),
                'message' => 'Suscripción de prueba creada (' . $preapproval->preapproval_id . '). El primer cobro se acreditará cuando llegue el webhook.',
            ];
        });
    }

    /**
     * Simula la renovación: concilia el primer cobro si quedó pendiente y
     * registra el periodo siguiente con el mismo flujo del webhook.
     *
     * @return array<string, mixed>
     */
    public function simulateRenewal(AcaSubscriptionType $plan): array
    {
        $this->assertPlan($plan);

        return $this->withLock($plan, function () use ($plan): array {
            $preapproval = $this->latestTestPreapproval($plan);

            if (! $preapproval) {
                throw new RuntimeException('Primero crea una suscripción de prueba.');
            }

            if (in_array($preapproval->status, ['cancelled'], true)) {
                throw new RuntimeException('La suscripción de prueba está cancelada. Elimínala y crea otra.');
            }

            $resultados = [];
            $pendiente = AcaSubscriptionPayment::query()
                ->where('preapproval_id', $preapproval->preapproval_id)
                ->where('is_test', true)
                ->where('status', '!=', 'approved')
                ->latest('id')
                ->first();

            if ($pendiente) {
                $resultados[] = $this->charges->handleAuthorizedPayment(
                    $this->syntheticInvoice($preapproval, 'TEST-FIRST-' . $preapproval->id)
                );
            }

            $resultados[] = $this->charges->handleAuthorizedPayment(
                $this->syntheticInvoice($preapproval, 'TEST-RENEW-' . $preapproval->id . '-' . now()->format('YmdHis'))
            );

            $preapproval->refresh();

            $mensaje = in_array('charged', $resultados, true)
                ? 'Renovación simulada: se registró el periodo en Tesorería y se envió el correo de renovación.'
                : 'Se procesó el cobro de prueba: ' . implode(', ', $resultados) . '.';

            AcaSubscriptionTestLog::record(
                $plan->id,
                'renewal',
                $mensaje,
                [
                    'results' => $resultados,
                    'email' => $preapproval->student?->person?->email,
                    'next_payment_date' => $preapproval->next_payment_date?->toDateString(),
                ],
                'success',
                $preapproval->preapproval_id,
            );

            return [
                'preapproval_id' => $preapproval->preapproval_id,
                'results' => $resultados,
                'email' => $preapproval->student?->person?->email,
                'message' => $mensaje . ' Revisa también la carpeta de spam.',
            ];
        });
    }

    /**
     * Envía el aviso de pago exitoso (correo de renovación) sin esperar un
     * cobro real, para comprobar que el correo llega al alumno de prueba.
     *
     * No cobra ni extiende periodos: solo reenvía la plantilla con el último
     * pago de prueba registrado.
     *
     * @return array<string, mixed>
     */
    public function sendSuccessfulPaymentNotice(AcaSubscriptionType $plan): array
    {
        $this->assertPlan($plan);

        return $this->withLock($plan, function () use ($plan): array {
            $preapproval = $this->latestTestPreapproval($plan);

            if (! $preapproval) {
                throw new RuntimeException('Primero crea una suscripción de prueba.');
            }

            $payment = AcaSubscriptionPayment::query()
                ->where('preapproval_id', $preapproval->preapproval_id)
                ->where('is_test', true)
                ->latest('id')
                ->first();

            if (! $payment) {
                throw new RuntimeException('No hay un pago de prueba para usar en el correo. Simula una renovación primero.');
            }

            $email = trim((string) ($preapproval->student?->person?->email ?: $preapproval->payer_email));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('La suscripción de prueba no tiene un correo válido para enviar el aviso.');
            }

            Mail::to($email)->queue(new SubscriptionRenewed(
                $preapproval,
                $payment,
                $preapproval->student?->person?->full_name ?: 'Estimado estudiante',
            ));

            AcaSubscriptionTestLog::record(
                $plan->id,
                'notice',
                'Aviso de pago exitoso enviado a ' . $email . '.',
                ['email' => $email, 'payment_id' => $payment->id],
                'success',
                $preapproval->preapproval_id,
            );

            return [
                'email' => $email,
                'preapproval_id' => $preapproval->preapproval_id,
                'message' => 'Aviso de pago exitoso encolado para ' . $email . '. Revisa la bandeja y spam; el envío corre en la cola.',
            ];
        });
    }

    /**
     * Cancela la suscripción de prueba en MercadoPago y desactiva el acceso.
     *
     * @return array<string, mixed>
     */
    public function cancel(AcaSubscriptionType $plan): array
    {
        $this->assertPlan($plan);

        return $this->withLock($plan, function () use ($plan): array {
            $preapproval = $this->latestTestPreapproval($plan);

            if (! $preapproval) {
                throw new RuntimeException('No hay una suscripción de prueba para eliminar.');
            }

            $aviso = null;
            try {
                $this->cancelRemote($preapproval);
            } catch (\Throwable $e) {
                // Se cancela igual localmente: lo importante es cortar el acceso.
                $aviso = ' En MercadoPago no se pudo confirmar: ' . $e->getMessage();
            }

            $preapproval->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ])->save();

            AcaStudentSubscription::query()
                ->where('student_id', $preapproval->student_id)
                ->where('subscription_id', $plan->id)
                ->update(['status' => false]);

            AcaSubscriptionTestLog::record(
                $plan->id,
                'cancelled',
                'Suscripción de prueba eliminada y acceso desactivado.' . ($aviso ?? ''),
                ['preapproval_id' => $preapproval->preapproval_id],
                $aviso ? 'warning' : 'success',
                $preapproval->preapproval_id,
            );

            return [
                'preapproval_id' => $preapproval->preapproval_id,
                'message' => 'Suscripción de prueba eliminada. El acceso del alumno de prueba quedó desactivado.' . ($aviso ?? ''),
            ];
        });
    }

    /**
     * Borra todo lo generado por el modo prueba para este plan.
     *
     * @return array<string, mixed>
     */
    public function purge(AcaSubscriptionType $plan): array
    {
        $this->assertPlan($plan);

        return $this->withLock($plan, function () use ($plan): array {
            $preapprovals = AcaSubscriptionPreapproval::query()
                ->where('subscription_id', $plan->id)
                ->where('is_test', true)
                ->get();

            foreach ($preapprovals as $preapproval) {
                try {
                    if ($preapproval->status !== 'cancelled') {
                        $this->cancelRemote($preapproval);
                    }
                } catch (\Throwable $e) {
                    // Si MercadoPago no responde, igual se limpian los datos locales.
                }
            }

            $preapprovalIds = $preapprovals->pluck('preapproval_id')->filter()->unique()->values();

            $studentIds = $preapprovals->pluck('student_id')->filter()->unique()->values();

            // Solo se borran el alumno y la persona que nacieron con la prueba.
            $createdStudentIds = $preapprovals
                ->filter(fn (AcaSubscriptionPreapproval $item): bool => $this->studentWasCreatedByTest($item))
                ->pluck('student_id')->filter()->unique()->values();

            $createdPersonIds = $preapprovals
                ->filter(fn (AcaSubscriptionPreapproval $item): bool => $this->personWasCreatedByTest($item))
                ->map(fn (AcaSubscriptionPreapproval $item): int => $this->personIdOf($item))
                ->filter()
                ->unique()
                ->values();

            $paymentIds = AcaSubscriptionPayment::query()
                ->where('subscription_id', $plan->id)
                ->where('is_test', true)
                ->pluck('payment_id')
                ->filter()
                ->unique()
                ->values();

            $saleIds = $paymentIds->isEmpty()
                ? collect()
                : OnliSale::query()
                    ->where('module_name', 'Academic')
                    ->whereIn('mercado_payment_id', $paymentIds->all())
                    ->pluck('id');

            $resumen = [
                'preapprovals' => 0,
                'payments' => 0,
                'sales' => 0,
                'treasury_reversals' => 0,
                'students' => 0,
                'persons' => 0,
                'events' => 0,
            ];

            DB::transaction(function () use ($plan, $preapprovals, $preapprovalIds, $studentIds, $createdStudentIds, $createdPersonIds, $saleIds, &$resumen): void {
                $tesoreria = app(TreasuryIntegrationService::class);

                foreach ($saleIds as $saleId) {
                    $resumen['treasury_reversals'] += $tesoreria->reverseOrigin(
                        TreasurySourceMovement::MODULE_SALES,
                        'onli_sale',
                        (int) $saleId,
                        'Datos de prueba eliminados · Suscripciones',
                        Auth::id(),
                    );
                }

                if ($saleIds->isNotEmpty()) {
                    $resumen['sales'] = OnliSale::query()->whereIn('id', $saleIds->all())->delete();
                }

                $resumen['payments'] = AcaSubscriptionPayment::query()
                    ->where('subscription_id', $plan->id)
                    ->where('is_test', true)
                    ->delete();

                // Alumno de prueba: se borra su periodo. Alumno real usado en
                // una prueba: solo se le quita el acceso que dejó la prueba.
                if ($createdStudentIds->isNotEmpty()) {
                    AcaStudentSubscription::query()
                        ->whereIn('student_id', $createdStudentIds->all())
                        ->where('subscription_id', $plan->id)
                        ->delete();
                }

                $otrosStudents = $studentIds->diff($createdStudentIds);
                if ($otrosStudents->isNotEmpty()) {
                    AcaStudentSubscription::query()
                        ->whereIn('student_id', $otrosStudents->all())
                        ->where('subscription_id', $plan->id)
                        ->update(['status' => false]);
                }

                $resumen['preapprovals'] = AcaSubscriptionPreapproval::query()
                    ->where('subscription_id', $plan->id)
                    ->where('is_test', true)
                    ->delete();

                AcaSubscriptionTestLog::query()->where('subscription_id', $plan->id)->delete();

                // Notificaciones de MercadoPago de estas suscripciones de prueba.
                if ($preapprovalIds->isNotEmpty()) {
                    $resumen['events'] = MercadoPagoEvent::query()
                        ->whereIn('data_id', $preapprovalIds->all())
                        ->delete();
                }

                if ($createdStudentIds->isNotEmpty()) {
                    $resumen['students'] = AcaStudent::query()
                        ->whereIn('id', $createdStudentIds->all())
                        ->delete();
                }

                foreach ($createdPersonIds as $personId) {
                    // Nunca se borra una persona que quedó ligada a otro alumno.
                    if (AcaStudent::query()->where('person_id', $personId)->exists()) {
                        continue;
                    }

                    try {
                        $resumen['persons'] += Person::query()->whereKey($personId)->delete();
                    } catch (\Throwable $e) {
                        // Si otra tabla la referencia, se conserva: no se rompe la BD.
                    }
                }
            });

            return [
                'message' => sprintf(
                    'Datos de prueba borrados: %d suscripciones, %d pagos, %d ventas, %d ingresos revertidos, %d alumnos, %d personas y %d notificaciones.',
                    $resumen['preapprovals'],
                    $resumen['payments'],
                    $resumen['sales'],
                    $resumen['treasury_reversals'],
                    $resumen['students'],
                    $resumen['persons'],
                    $resumen['events'],
                ),
                'summary' => $resumen,
            ];
        });
    }

    /**
     * Alumno de prueba: persona y alumno reutilizables por correo.
     *
     * Informa además si la persona y el alumno se crearon ahora: el borrado
     * solo elimina lo que nació con la prueba, nunca un alumno real que se
     * haya usado para probar el flujo.
     *
     * @return array{student: AcaStudent, created_person: bool, created_student: bool}
     */
    private function testStudent(AcaSubscriptionType $plan, string $email): array
    {
        $persona = Person::query()->where('email', $email)->first();
        $personaCreada = false;

        if (! $persona) {
            $numero = 'PRUEBA-SUS-' . $plan->id . '-' . now()->format('ymdHis');

            $persona = Person::create([
                'document_type_id' => '1',
                'names' => 'Alumno',
                'father_lastname' => 'Prueba',
                'mother_lastname' => 'Suscripción',
                'full_name' => 'Alumno Prueba Suscripción',
                'email' => $email,
                'number' => $numero,
                'gender' => 'M',
                'country_id' => 1,
                'is_client' => true,
                'status' => true,
            ]);

            $personaCreada = true;
        }

        $alumno = AcaStudent::query()->where('person_id', $persona->id)->first();
        $alumnoCreado = false;

        if (! $alumno) {
            $alumno = AcaStudent::create([
                'person_id' => $persona->id,
                'student_code' => $persona->number ?: 'PRUEBA-' . $persona->id,
            ]);

            $alumnoCreado = true;
        }

        return [
            'student' => $alumno,
            'created_person' => $personaCreada,
            'created_student' => $alumnoCreado,
        ];
    }

    /**
     * ¿El alumno nació con la prueba? En filas antiguas se usa el código con
     * el que el modo prueba crea a sus alumnos.
     */
    private function studentWasCreatedByTest(AcaSubscriptionPreapproval $preapproval): bool
    {
        $datos = (array) $preapproval->invoice_data;

        if (array_key_exists('created_student', $datos)) {
            return (bool) $datos['created_student'];
        }

        $codigo = (string) AcaStudent::query()->whereKey($preapproval->student_id)->value('student_code');

        return str_starts_with($codigo, 'PRUEBA-SUS-');
    }

    /**
     * ¿La persona nació con la prueba? Se apoya en la marca guardada al crear
     * la suscripción y, si falta, en el número de documento de prueba.
     */
    private function personWasCreatedByTest(AcaSubscriptionPreapproval $preapproval): bool
    {
        $datos = (array) $preapproval->invoice_data;

        if (array_key_exists('created_person', $datos)) {
            return (bool) $datos['created_person'];
        }

        $personId = $this->personIdOf($preapproval);

        if ($personId <= 0) {
            return false;
        }

        $numero = (string) Person::query()->whereKey($personId)->value('number');

        return str_starts_with($numero, 'PRUEBA-SUS-');
    }

    /**
     * Persona del alumno de la suscripción de prueba.
     */
    private function personIdOf(AcaSubscriptionPreapproval $preapproval): int
    {
        $personId = (int) data_get($preapproval->invoice_data, 'person_id', 0);

        if ($personId > 0) {
            return $personId;
        }

        return (int) AcaStudent::query()->whereKey($preapproval->student_id)->value('person_id');
    }

    /**
     * Token de la tarjeta de prueba (el token vive pocos minutos).
     */
    private function cardToken(array $credenciales): string
    {
        $tarjeta = (array) config('services.mercadopago.test_card', []);
        $payload = [
            'card_number' => preg_replace('/\D+/', '', (string) ($tarjeta['number'] ?? '')),
            'security_code' => (string) ($tarjeta['cvv'] ?? ''),
            'expiration_month' => (int) ($tarjeta['expiration_month'] ?? 11),
            'expiration_year' => (int) ($tarjeta['expiration_year'] ?? 2030),
            'cardholder' => [
                'name' => (string) ($tarjeta['holder_name'] ?? 'APRO'),
                'identification' => [
                    'type' => (string) ($tarjeta['identification_type'] ?? 'DNI'),
                    'number' => (string) ($tarjeta['identification_number'] ?? '12345678'),
                ],
            ],
        ];

        $url = self::MP_BASE . '/v1/card_tokens?public_key=' . urlencode($credenciales['public_key']);

        try {
            $respuesta = $this->request('post', $url, $payload);
        } catch (RuntimeException $e) {
            // El paso se indica en el mensaje: con un 404 aquí el problema está
            // en la public key (debe ser de la misma cuenta que el access token).
            throw new RuntimeException(
                'No se pudo generar el token de la tarjeta de prueba con test_public_key. ' . $e->getMessage(),
                0,
                $e
            );
        }

        $token = (string) ($respuesta['id'] ?? '');
        if ($token === '') {
            throw new RuntimeException('MercadoPago no devolvió el token de la tarjeta de prueba.');
        }

        return $token;
    }

    /**
     * Crea el preapproval de prueba con el token de la tarjeta.
     *
     * @return array{id: string, status: string, next_payment_date: string|null, raw: array<string, mixed>}
     */
    private function createPreapproval(
        AcaSubscriptionType $plan,
        array $credenciales,
        string $cardToken,
        AcaStudent $alumno,
        float $monto,
    ): array {
        $payload = [
            'reason' => mb_substr('PRUEBA · ' . trim((string) ($plan->title ?: 'Suscripción académica')), 0, 250),
            'external_reference' => 'aca:test:student:' . $alumno->id . ':plan:' . $plan->id,
            'payer_email' => (string) $alumno->person?->email,
            'card_token_id' => $cardToken,
            'back_url' => rtrim((string) config('app.url'), '/'),
            'status' => 'authorized',
            'auto_recurring' => [
                // Cada 1, 3 o 12 meses según el periodo del plan.
                'frequency' => (int) $plan->recurringMonths(),
                'frequency_type' => 'months',
                'transaction_amount' => round($monto, 2),
                'currency_id' => 'PEN',
                'start_date' => now()->copy()->utc()->format('Y-m-d\TH:i:s.000\Z'),
            ],
        ];

        try {
            $respuesta = $this->request('post', self::MP_BASE . '/preapproval', $payload, $credenciales['token']);
        } catch (RuntimeException $e) {
            // El paso se indica en el mensaje: aquí ya se tokenizó la tarjeta y el
            // rechazo viene del alta de la suscripción con test_access_token.
            throw new RuntimeException(
                'No se pudo crear la suscripción de prueba con test_access_token. ' . $e->getMessage()
                . ' Revisa que el correo sea el de un usuario de prueba de MercadoPago (test_user_...@testuser.com),'
                . ' que el pagador no sea el dueño del token y que token y public key sean de la misma cuenta.',
                0,
                $e
            );
        }

        $id = (string) ($respuesta['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('MercadoPago no devolvió el id de la suscripción de prueba.');
        }

        return [
            'id' => $id,
            'status' => (string) ($respuesta['status'] ?? 'pending'),
            'next_payment_date' => $respuesta['next_payment_date'] ?? null,
            'raw' => $respuesta,
        ];
    }

    /**
     * Cancela el preapproval en el ambiente de prueba.
     */
    private function cancelRemote(AcaSubscriptionPreapproval $preapproval): void
    {
        if (! $preapproval->preapproval_id) {
            return;
        }

        if (! $this->credentials()['ready']) {
            throw new RuntimeException('Faltan las credenciales de prueba para cancelar en MercadoPago.');
        }

        $tokens = $this->testCredentials();

        $this->request(
            'put',
            self::MP_BASE . '/preapproval/' . rawurlencode($preapproval->preapproval_id),
            ['status' => 'cancelled'],
            $tokens['token'],
        );
    }

    /**
     * Ejecuta una llamada HTTP a MercadoPago y devuelve el JSON.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, array $payload, ?string $token = null): array
    {
        $http = Http::acceptJson()->asJson();
        if ($token) {
            $http = $http->withToken($token);
        }

        $respuesta = $http->{$method}($url, $payload);
        $cuerpo = (array) $respuesta->json();

        if (! $respuesta->successful()) {
            $mensaje = $cuerpo['message'] ?? $cuerpo['error'] ?? ('HTTP ' . $respuesta->status());
            $causa = collect($cuerpo['cause'] ?? [])
                ->map(fn ($item) => is_array($item) ? ($item['description'] ?? null) : null)
                ->filter()
                ->first();

            throw new RuntimeException(
                'MercadoPago (HTTP ' . $respuesta->status() . '): ' . $mensaje . ($causa ? ' · ' . $causa : '')
            );
        }

        return $cuerpo;
    }

    /**
     * Cobro sintético con la forma que entrega el webhook.
     *
     * @return array<string, mixed>
     */
    private function syntheticInvoice(AcaSubscriptionPreapproval $preapproval, string $id): array
    {
        $monto = (float) $preapproval->amount;

        return [
            'id' => $id,
            'preapproval_id' => $preapproval->preapproval_id,
            'status' => 'approved',
            'transaction_amount' => $monto,
            'debit_date' => now()->toIso8601String(),
            'payment' => [
                'id' => 'TEST-PAY-' . Str::upper(Str::random(10)),
                'status' => 'approved',
                'transaction_amount' => $monto,
            ],
            'raw' => ['modo_prueba' => true, 'synthetic_invoice' => $id],
        ];
    }

    /**
     * Última suscripción de prueba del plan.
     */
    private function latestTestPreapproval(AcaSubscriptionType $plan): ?AcaSubscriptionPreapproval
    {
        return AcaSubscriptionPreapproval::query()
            ->where('subscription_id', $plan->id)
            ->where('is_test', true)
            ->latest('id')
            ->first();
    }

    /**
     * Candado por plan: evita dos operaciones de prueba simultáneas.
     *
     * @return array<string, mixed>
     */
    private function withLock(AcaSubscriptionType $plan, callable $callback): array
    {
        $lock = Cache::lock('aca-subscription-test-' . $plan->id, 120);

        if (! $lock->get()) {
            throw new RuntimeException('Hay otra operación de prueba en curso. Espera unos segundos e inténtalo otra vez.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * Solo planes con periodo recurrente (mensual, trimestral o anual).
     */
    private function assertPlan(AcaSubscriptionType $plan): void
    {
        if (! $plan->test_mode) {
            throw new RuntimeException('Activa primero el modo prueba del plan y guarda los cambios.');
        }

        if (! $plan->supportsRecurring()) {
            throw new RuntimeException('El modo prueba solo está disponible para planes mensuales, trimestrales o anuales.');
        }
    }

    /**
     * @param  array{ready: bool, missing: array<int, string>, same_as_production: bool}  $credenciales
     */
    private function missingCredentialsMessage(array $credenciales): string
    {
        if ($credenciales['same_as_production']) {
            return 'El token configurado como prueba es el mismo de producción. Carga las credenciales de PRUEBA en IntegrationHub → MercadoPago (test_access_token y test_public_key).';
        }

        return 'Faltan las credenciales de prueba en IntegrationHub → MercadoPago: '
            . implode(', ', $credenciales['missing'])
            . '. Carga las de PRUEBA, nunca las de producción.';
    }

    /**
     * Credenciales de prueba reales (token y public key). Solo uso interno:
     * nunca se envían al navegador.
     *
     * @return array{token: string, public_key: string}
     */
    private function testCredentials(): array
    {
        $hubToken = $this->hubValue('test_access_token');
        $hubKey = $this->hubValue('test_public_key');

        return [
            'token' => trim((string) ($hubToken ?: config('services.mercadopago.test_token'))),
            'public_key' => trim((string) ($hubKey ?: config('services.mercadopago.test_public_key'))),
        ];
    }

    /**
     * Valor de un campo de la integración MercadoPago (vacío si no existe).
     */
    private function hubValue(string $field): ?string
    {
        $valor = app(IntegrationCredentialResolver::class)->value('MercadoPago', $field);

        return $valor !== null && trim($valor) !== '' ? trim($valor) : null;
    }

    /**
     * Enlace directo a la integración para cargar las credenciales de prueba.
     */
    private function integrationEditUrl(): string
    {
        $id = Integration::query()
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->value('id');

        return $id
            ? route('integrationhub_editar', $id)
            : route('integrationhub_listado');
    }

    /**
     * Próximo cobro informado por MercadoPago o, si falta, un periodo después.
     */
    private function nextPaymentDate(?string $fecha, int $months = 1): string
    {
        if ($fecha) {
            try {
                return Carbon::parse($fecha)->toDateString();
            } catch (\Throwable $e) {
                // Se usa el cálculo local si la fecha viene inválida.
            }
        }

        return now()->copy()->addMonths(max(1, $months))->toDateString();
    }
}
