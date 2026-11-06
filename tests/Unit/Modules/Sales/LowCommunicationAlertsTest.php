<?php

namespace Tests\Unit\Modules\Sales;

use App\Helpers\Invoice\Documents\LowCommunicationAlerts;
use PHPUnit\Framework\TestCase;

class LowCommunicationAlertsTest extends TestCase
{
    public function test_plazo_vencido_sugiere_nota_de_credito(): void
    {
        $alert = LowCommunicationAlerts::failure(
            '2335',
            'El plazo para dar de baja el comprobante ha vencido (7 dias habiles).'
        );

        $this->assertSame(LowCommunicationAlerts::RESULT_PLAZO_VENCIDO, $alert['result']);
        $this->assertSame(LowCommunicationAlerts::ACTION_CREDIT_NOTE, $alert['action']);
        $this->assertSame('Rechazado', $alert['status']);
        $this->assertStringContainsString('Nota de Crédito', $alert['message']);
        $this->assertStringContainsString('2335', (string) $alert['detail']);
    }

    public function test_sunat_no_disponible_es_reintentable(): void
    {
        $alert = LowCommunicationAlerts::failure('0109', 'El servicio de SUNAT no se encuentra disponible.');

        $this->assertSame(LowCommunicationAlerts::RESULT_NO_DISPONIBLE, $alert['result']);
        $this->assertSame('sunat_disponible', $alert['status']);
        $this->assertSame(LowCommunicationAlerts::ACTION_RETRY, $alert['action']);
    }

    public function test_error_http_se_trata_como_sunat_no_disponible(): void
    {
        $alert = LowCommunicationAlerts::failure('HTTP', 'Bad Request');

        $this->assertSame(LowCommunicationAlerts::RESULT_NO_DISPONIBLE, $alert['result']);
        $this->assertSame(LowCommunicationAlerts::ACTION_RETRY, $alert['action']);
    }

    public function test_ticket_inexistente_pide_generar_nueva_baja(): void
    {
        $alert = LowCommunicationAlerts::failure('0127', 'El ticket no existe');

        $this->assertSame(LowCommunicationAlerts::RESULT_RECHAZADO, $alert['result']);
        $this->assertSame('ticket_inexistente', $alert['kind']);
        $this->assertSame(LowCommunicationAlerts::ACTION_NEW_VOID, $alert['action']);
    }

    public function test_en_proceso_mantiene_estado_enviado(): void
    {
        $alert = LowCommunicationAlerts::failure('0098', 'El proceso no ha terminado');

        $this->assertSame(LowCommunicationAlerts::RESULT_EN_PROCESO, $alert['result']);
        $this->assertSame('Enviado', $alert['status']);
        $this->assertSame(LowCommunicationAlerts::ACTION_RECHECK, $alert['action']);
    }

    public function test_ya_presentado_permite_reconsultar(): void
    {
        $alert = LowCommunicationAlerts::failure('2223', 'El archivo ya fue presentado');

        $this->assertSame(LowCommunicationAlerts::RESULT_YA_PRESENTADO, $alert['result']);
        $this->assertSame('fue_enviado', $alert['status']);
        $this->assertSame(LowCommunicationAlerts::ACTION_RECHECK, $alert['action']);
    }

    public function test_rechazo_generico_conserva_observaciones(): void
    {
        $alert = LowCommunicationAlerts::failure(
            '2017',
            'El numero de documento de identidad del receptor debe ser RUC.',
            ['Revisa el cliente'],
            'check'
        );

        $this->assertSame(LowCommunicationAlerts::RESULT_RECHAZADO, $alert['result']);
        $this->assertSame('rechazo_cdr', $alert['kind']);
        $this->assertSame(LowCommunicationAlerts::ACTION_FIX, $alert['action']);
        $this->assertStringContainsString('Revisa el cliente', $alert['message']);
        $this->assertSame('2017', $alert['code']);
    }

    public function test_baja_aceptada_devuelve_estado_aceptado(): void
    {
        $alert = LowCommunicationAlerts::accepted('0', 'La Comunicacion de baja ha sido aceptada', [], true);

        $this->assertSame(LowCommunicationAlerts::RESULT_ACEPTADO, $alert['result']);
        $this->assertSame('Aceptado', $alert['status']);
        $this->assertTrue($alert['has_cdr']);
        $this->assertSame('success', $alert['level']);
    }

    public function test_envio_con_ticket_queda_pendiente_de_confirmacion(): void
    {
        $alert = LowCommunicationAlerts::sentToSunat('1234567890');

        $this->assertSame(LowCommunicationAlerts::RESULT_EN_PROCESO, $alert['result']);
        $this->assertSame('Enviado', $alert['status']);
        $this->assertStringContainsString('1234567890', $alert['message']);
    }

    public function test_error_local_no_afirma_anulacion(): void
    {
        $alert = LowCommunicationAlerts::localError('No se pudo cargar el certificado');

        $this->assertSame(LowCommunicationAlerts::RESULT_ERROR_LOCAL, $alert['result']);
        $this->assertSame(LowCommunicationAlerts::ACTION_SUPPORT, $alert['action']);
        $this->assertStringContainsString('No se confirmó', $alert['message']);
    }
}
