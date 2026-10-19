<?php

namespace Modules\Socialevents\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class CheckOrganizationBillingAlerts extends Command
{
    protected $signature = 'socialevents:check-organization-billing-alerts {--dry-run : Solo muestra organizaciones candidatas sin enviar avisos}';

    protected $description = 'Envía los avisos configurados de pago próximo o vencido a las organizaciones.';

    public function handle(): int
    {
        $settings = DB::table('socialevent_organization_settings')->first();
        if (! $settings) {
            $this->info('No existe configuración de avisos de organizaciones.');

            return self::SUCCESS;
        }
        $allSettings = json_decode($settings->alert_settings ?? '{}', true) ?: [];
        $config = $allSettings['billing'] ?? [];
        if (empty($config['alerts_enabled'])) {
            $this->info('Los avisos de pago están desactivados.');

            return self::SUCCESS;
        }

        $configuredTime = (string) ($config['time'] ?? '09:00');
        if (now('America/Lima')->format('H:i') !== $configuredTime) {
            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $daysBefore = max(1, (int) ($config['days_before'] ?? 7));
        $overdueAfter = max(0, (int) ($config['overdue_after'] ?? 1));
        $repeatEvery = max(0, (int) ($config['repeat_every'] ?? 7));
        $organizations = DB::table('socialevent_organizations')
            ->where('is_legacy', false)
            ->whereNull('deleted_at')
            ->whereIn('status', ['trial', 'active'])
            ->get();
        $sent = 0;

        foreach ($organizations as $organization) {
            $dueDate = $organization->status === 'active' ? $organization->access_until : $organization->trial_ends_at;
            if (! $dueDate) {
                continue;
            }

            $due = Carbon::parse($dueDate)->startOfDay();
            $daysLeft = (int) round(($due->getTimestamp() - $today->getTimestamp()) / 86400);
            if ($daysLeft > $daysBefore || $daysLeft < -$overdueAfter) {
                continue;
            }

            $type = $daysLeft > 0 ? 'upcoming' : ($daysLeft === 0 ? 'due' : 'overdue');
            $lastSent = DB::table('socialevent_organization_alerts')
                ->where('organization_id', $organization->id)
                ->where('type', $type)
                ->max('created_at');
            if ($lastSent && ($repeatEvery === 0 || now()->diffInDays(Carbon::parse($lastSent)) < $repeatEvery)) {
                continue;
            }

            $dueLabel = $due->format('d/m/Y');
            $message = match ($type) {
                'upcoming' => "El acceso de {$organization->name} vence el {$dueLabel}. Realiza el pago para evitar interrupciones.",
                'due' => "El acceso de {$organization->name} vence hoy ({$dueLabel}). Realiza el pago para mantener el servicio.",
                default => "El acceso de {$organization->name} venció el {$dueLabel}. La cuenta está en modo de solo lectura; realiza el pago para solicitar su reactivación.",
            };
            $recipients = DB::table('socialevent_organization_users')
                ->join('users', 'users.id', '=', 'socialevent_organization_users.user_id')
                ->select('users.id', 'users.email')
                ->where('socialevent_organization_users.organization_id', $organization->id)
                ->get();

            if ($this->option('dry-run')) {
                $this->line("{$organization->id} {$organization->name}: {$type}, vence {$dueLabel}, destinatarios {$recipients->count()}.");
                continue;
            }

            foreach ($recipients as $recipient) {
                if (! empty($config['system_enabled'])) {
                    DB::table('socialevent_organization_alerts')->insert([
                        'organization_id' => $organization->id,
                        'user_id' => $recipient->id,
                        'type' => $type,
                        'message' => $message,
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if (! empty($config['email_enabled']) && $recipient->email) {
                    Mail::raw($message, function ($mail) use ($recipient, $organization, $type) {
                        $mail->to($recipient->email)->subject('Aviso de pago '.$organization->name.' · '.$type);
                    });
                }
            }
            $sent++;
        }

        $this->info("Avisos procesados: {$sent}.");

        return self::SUCCESS;
    }
}
