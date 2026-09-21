<?php

namespace Modules\Academic\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionExpired;
use Modules\Academic\Entities\AcaStudent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'academic:check-expired-subscriptions';

    /**
     * The console command description.
     */
    protected $description = 'Verifica suscripciones estudiantiles vencidas en el módulo Académico, actualiza su estado y envía notificaciones.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando verificación de suscripciones vencidas del módulo Académico...');

        $today = now()->format('Y-m-d'); // Obtiene la fecha actual en formato 'AAAA-MM-DD'

        // Busca suscripciones activas cuya fecha de fin ya pasó
        $expiredSubscriptions = DB::table('aca_student_subscriptions')
                                ->where('status', true) // Asumimos 'true' es el estado de suscripción activa
                                ->whereDate('date_end', '<', $today) // La fecha de fin es anterior a hoy
                                ->get();

        $updatedCount = 0;
        $notifiedCount = 0;

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('✅ No se encontraron suscripciones vencidas para actualizar.');
            return 0;
        }

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
                // *** IMPORTANTE: Debes implementar getStudentEmail y getStudentName ***
                $studentEmail = $this->getStudentEmail($subscription->student_id);
                $studentName = $this->getStudentName($subscription->student_id);

                if ($studentEmail) {
                    Mail::to($studentEmail)->queue(new SubscriptionExpired($subscription, $studentName));
                    $notifiedCount++;
                    $this->info("✉️ Correo de notificación enviado a {$studentEmail}.");
                } else {
                    $this->warn("⚠️ No se pudo encontrar el correo para el estudiante ID {$subscription->student_id}. No se envió notificación.");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error al procesar la suscripción ID {$subscription->subscription_id}: " . $e->getMessage());
            }
        }

        $this->info("✨ Proceso completado para el módulo Académico. {$updatedCount} suscripciones actualizadas y {$notifiedCount} notificaciones enviadas.");

        return 0; // 0 indica que el comando se ejecutó con éxito
    }

    protected function getStudentEmail($studentId)
    {
        // Ejemplo: Asumiendo que tienes una tabla 'users' o 'students' donde el 'id' corresponde al student_id
        // y tienen un campo 'email'. Ajusta la tabla y los campos según tu base de datos.
        $student = AcaStudent::with('person')->where('id', $studentId)->first();
        return $student ? $student->person->email : null;
    }

    protected function getStudentName($studentId)
    {
        // Ejemplo: Asumiendo que tienes una tabla 'users' o 'students' con un campo 'name'.
        $student = AcaStudent::with('person')->where('id', $studentId)->first();
        return $student ? $student->person->full_name : 'Estimado estudiante';
    }
}
