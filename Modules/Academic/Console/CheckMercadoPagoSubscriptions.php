<?php

namespace Modules\Academic\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;

/**
 * Diagnóstico de Fase 0 para cobros recurrentes con MercadoPago.
 *
 * Comprueba, sin escribir nada en la base ni cobrar, que el token configurado
 * puede consultar la cuenta y las APIs de Suscripciones (preapproval y
 * preapproval_plan). Solo con --create-plan se crea un plan de prueba real en
 * la cuenta de MercadoPago.
 */
class CheckMercadoPagoSubscriptions extends Command
{
    protected $signature = 'academic:mercadopago-subscriptions-check
                            {--create-plan : Crea un plan mensual de prueba (PEN 5.00) en la cuenta de MercadoPago}
                            {--plan= : Consulta un preapproval_plan existente por su id}';

    protected $description = 'Verifica la cuenta y las APIs de Suscripciones de MercadoPago para los cobros recurrentes mensuales (Fase 0).';

    private const API = 'https://api.mercadopago.com';

    public function handle(): int
    {
        $token = trim((string) config('services.mercadopago.token'));

        if ($token === '') {
            $this->error('❌ MERCADOPAGO_TOKEN no está configurado: no se puede consultar la cuenta.');
            $this->line('   Agrega un token de la cuenta (idealmente de prueba) en el .env y vuelve a ejecutar.');

            return self::FAILURE;
        }

        $this->info('🔎 Consultando MercadoPago (solo lectura)…');

        $me = Http::withToken($token)->acceptJson()->timeout(20)->get(self::API . '/users/me');

        if (! $me->successful()) {
            $this->error('❌ El token no pudo consultar la cuenta (HTTP ' . $me->status() . ').');
            $this->line('   Revisa que MERCADOPAGO_TOKEN sea el access token vigente de la cuenta.');

            return self::FAILURE;
        }

        $account = (array) $me->json();

        $this->line(sprintf(
            '   Cuenta: #%s · %s · sitio %s · %s',
            $account['id'] ?? '-',
            $account['nickname'] ?? '-',
            $account['site_id'] ?? '-',
            $account['email'] ?? '-'
        ));

        if (($account['site_id'] ?? null) !== 'MPE') {
            $this->warn('⚠️  La cuenta no declara sitio MPE (Perú). Los montos y medios disponibles pueden diferir.');
        }

        $this->newLine();
        $this->info('📡 APIs de Suscripciones');

        $preapprovalOk = $this->checkEndpoint($token, '/preapproval/search?limit=1', 'suscripciones (preapproval)');
        $planOk = $this->checkEndpoint($token, '/preapproval_plan/search?limit=1', 'planes de suscripción (preapproval_plan)');

        $this->newLine();
        $this->info('🔐 Webhook');

        $configSecret = trim((string) config('services.mercadopago.webhook_secret'));
        $secret = app(IntegrationCredentialResolver::class)
            ->value('MercadoPago', 'webhook_secret', $configSecret);

        if ($secret === null || $secret === '') {
            $this->warn('⚠️  Falta el secreto del webhook: el webhook rechazará toda notificación con 401.');
            $this->line('   Regístralo en IntegrationHub → MercadoPago → campo webhook_secret.');
            $this->line('   (MERCADOPAGO_WEBHOOK_SECRET en el .env queda como respaldo para desarrollo).');
        } else {
            $origen = $secret !== $configSecret
                ? 'IntegrationHub · MercadoPago → webhook_secret'
                : '.env (MERCADOPAGO_WEBHOOK_SECRET)';

            $this->line('   ✅ Secreto de webhook configurado desde ' . $origen . '.');
        }

        if ($this->option('plan')) {
            $this->newLine();
            $this->showPlan($token, (string) $this->option('plan'));
        }

        if ($this->option('create-plan')) {
            $this->newLine();
            $this->createTestPlan($token);
        }

        $this->newLine();

        if ($preapprovalOk && $planOk) {
            $this->info('✅ Fase 0 (conectividad): el token puede consultar Suscripciones.');
        } else {
            $this->warn('⚠️  Fase 0 (conectividad): alguna API de Suscripciones no respondió 200; revisa la cuenta o los permisos de la aplicación.');
        }

        $this->line('   Pendiente para cerrar la fase: autorizar una tarjeta de prueba y crear un preapproval mensual con su card_token (Fase 2).');

        return $preapprovalOk && $planOk ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Consulta un endpoint de búsqueda y reporta si está disponible.
     */
    private function checkEndpoint(string $token, string $endpoint, string $label): bool
    {
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get(self::API . $endpoint);

        if ($response->successful()) {
            $total = data_get($response->json(), 'paging.total');
            $this->line(sprintf(
                '   ✅ %s: disponible%s',
                $label,
                is_numeric($total) ? ' (registros actuales: ' . (int) $total . ')' : ''
            ));

            return true;
        }

        $this->warn(sprintf('   ⚠️  %s: HTTP %d', $label, $response->status()));

        return false;
    }

    /**
     * Muestra la configuración de un plan mensual existente.
     */
    private function showPlan(string $token, string $planId): void
    {
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get(self::API . '/preapproval_plan/' . $planId);

        if (! $response->successful()) {
            $this->warn('   ⚠️  No se pudo leer el plan ' . $planId . ' (HTTP ' . $response->status() . ').');

            return;
        }

        $plan = (array) $response->json();
        $recurring = (array) ($plan['auto_recurring'] ?? []);

        $this->info('   Plan ' . $planId);
        $this->line(sprintf(
            '   %s · %s %s cada %s %s · estado %s',
            $plan['reason'] ?? '-',
            $recurring['transaction_amount'] ?? '-',
            $recurring['currency_id'] ?? '-',
            $recurring['frequency'] ?? '-',
            $recurring['frequency_type'] ?? '-',
            $plan['status'] ?? '-'
        ));
    }

    /**
     * Crea un plan mensual de prueba (PEN 5.00). No crea suscripciones ni cobra.
     */
    private function createTestPlan(string $token): void
    {
        $hoy = (int) now()->format('j');
        $billingDay = $hoy <= 28 ? $hoy : 28;

        $response = Http::withToken($token)->acceptJson()->timeout(30)->post(self::API . '/preapproval_plan', [
            'reason' => 'Plan mensual de prueba - Aracode',
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => 5,
                'currency_id' => 'PEN',
                'billing_day' => $billingDay,
            ],
            'back_url' => rtrim((string) config('app.url'), '/') . '/academic',
        ]);

        if (! $response->successful()) {
            $this->warn('   ⚠️  No se pudo crear el plan de prueba (HTTP ' . $response->status() . ').');
            $this->line('   ' . mb_substr((string) $response->body(), 0, 300));

            return;
        }

        $plan = (array) $response->json();

        $this->info('   ✅ Plan de prueba creado (no cobra por sí solo):');
        $this->line('   id: ' . ($plan['id'] ?? '-'));
        $this->line('   estado: ' . ($plan['status'] ?? '-'));
        $this->line('   monto: PEN 5.00 cada mes · día de cobro ' . $billingDay);
    }
}
