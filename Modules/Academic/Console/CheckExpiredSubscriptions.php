<?php

namespace Modules\Academic\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionExpired;
use Modules\Academic\Entities\AcaStudent;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'academic:check-expired-subscriptions
                            {--dry-run : Lista las suscripciones vencidas sin actualizar estados ni enviar correos}';

    /**
     * The console command description.
     */
    protected $description = 'Verifica suscripciones estudiantiles vencidas en el módulo Académico, actualiza su estado y envía notificaciones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $this->info('🚀 Iniciando verificación de suscripciones vencidas del módulo Académico...' . ($dryRun ? ' (modo simulación --dry-run)' : ''));

            $today = now()->format('Y-m-d'); // Obtiene la fecha actual en formato 'AAAA-MM-DD'

            // Busca suscripciones activas cuya fecha de fin ya pasó
            $expiredSubscriptions = DB::table('aca_student_subscriptions')
                                    ->where('status', true) // Asumimos 'true' es el estado de suscripción activa
                                    ->whereDate('date_end', '<', $today) // La fecha de fin es anterior a hoy
                                    ->get();

            if ($expiredSubscriptions->isEmpty()) {
                $this->info('✅ No se encontraron suscripciones vencidas para actualizar.');
                return 0;
            }

            // Modo simulación: solo informa qué haría, sin tocar datos ni enviar correos.
            // Sirve para diagnosticar en el servidor sin efectos secundarios.
            if ($dryRun) {
                $this->warn("🔎 Simulación: se encontraron {$expiredSubscriptions->count()} suscripciones vencidas (no se actualiza nada).");

                foreach ($expiredSubscriptions as $subscription) {
                    $studentEmail = $this->getStudentEmail($subscription->student_id);

                    $this->line(sprintf(
                        '   - Suscripción #%s / estudiante #%s (venció el %s) → correo: %s',
                        $subscription->subscription_id,
                        $subscription->student_id,
                        $subscription->date_end,
                        $studentEmail ?: 'sin correo'
                    ));
                }

                $this->info('✨ Simulación completada. No se actualizaron suscripciones ni se enviaron correos.');
                return 0;
            }

            $updatedCount = 0;
            $notifiedCount = 0;

            foreach ($expiredSubscriptions as $subscription) {
                try {
                    // 1. Actualiza el estado de la suscripción a 'false' (inactiva/vencida)
                    DB::table('aca_student_subscriptions')
                        ->where('subscription_id', $subscription->subscription_id)
                        ->where('student_id', $subscription->student_id)
                        ->update(['status' => false]);

                    $updatedCount++;

                    $this->info("🔄 Suscripción ID {$subscription->subscription_id} actualizada a 'false'.");

                    // 2. Prepara y envía el correo electrónico al estudiante
                    $studentEmail = $this->getStudentEmail($subscription->student_id);
                    $studentName = $this->getStudentName($subscription->student_id);

                    if ($studentEmail) {
                        Mail::to($studentEmail)->queue(new SubscriptionExpired($subscription, $studentName));
                        $notifiedCount++;
                        $this->info("✉️ Correo de notificación enviado a {$studentEmail}.");
                    } else {
                        $this->warn("⚠️ No se pudo encontrar el correo para el estudiante ID {$subscription->student_id}. No se envió notificación.");
                    }

                } catch (\Throwable $e) {
                    // Se captura Throwable (y no solo Exception) para que un Error de PHP
                    // (clase no encontrada, tipo inválido, etc.) no aborte toda la tarea.
                    // report() deja el stack en laravel.log: antes el detalle solo iba al
                    // stdout del cron y se perdía.
                    $this->error("❌ Error al procesar la suscripción ID {$subscription->subscription_id}: " . $e->getMessage());
                    report($e);
                }
            }

            $this->info("✨ Proceso completado para el módulo Académico. {$updatedCount} suscripciones actualizadas y {$notifiedCount} notificaciones enviadas.");

            return 0; // 0 indica que el comando se ejecutó con éxito
        } catch (\Throwable $e) {
            // Antes, un fallo aquí (tabla inexistente, BD rechazando conexiones, lock de
            // backup a medianoche, etc.) terminaba con exit code 1 y sin causa visible.
            // Ahora la excepción y su stack quedan registrados en laravel.log.
            report($e);
            $this->error('❌ La verificación de suscripciones vencidas falló: ' . $e->getMessage());

            return 1;
        }
    }

    protected function getStudentEmail($studentId)
    {
        $student = AcaStudent::with('person')->where('id', $studentId)->first();

        // Acceso null-safe: evita el warning de PHP cuando el estudiante no tiene persona asociada.
        return $student?->person?->email;
    }

    protected function getStudentName($studentId)
    {
        $student = AcaStudent::with('person')->where('id', $studentId)->first();

        return $student?->person?->full_name ?? 'Estimado estudiante';
    }
}
