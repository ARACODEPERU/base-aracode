<?php

namespace Tests\Unit\Support;

use App\Support\TelegramWebhookGuard;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Freno anti-abuso de los webhooks de Telegram.
 *
 * Contrato:
 *   - un update repetido (mismo update_id) no se procesa dos veces;
 *   - un chat que supera el límite por minuto se descarta;
 *   - el bot completo también tiene un techo, aunque los chats sean distintos;
 *   - el resto de chats no se ve afectado por el abuso de uno.
 */
class TelegramWebhookGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_descarta_el_mismo_update_entregado_dos_veces(): void
    {
        $guard = new TelegramWebhookGuard('prueba', 20, 600);
        $update = ['update_id' => 1001, 'message' => ['chat' => ['id' => 55]]];

        $this->assertTrue($guard->allows($update, '55'));
        $this->assertFalse($guard->allows($update, '55'));
    }

    public function test_descarta_el_exceso_de_un_chat_pero_no_toca_a_los_demas(): void
    {
        $guard = new TelegramWebhookGuard('prueba', 3, 600);

        $this->assertTrue($guard->allows(['update_id' => 1], 'chat-a'));
        $this->assertTrue($guard->allows(['update_id' => 2], 'chat-a'));
        $this->assertTrue($guard->allows(['update_id' => 3], 'chat-a'));

        // El cuarto mensaje del mismo chat en el mismo minuto se descarta.
        $this->assertFalse($guard->allows(['update_id' => 4], 'chat-a'));

        // Otro chat conserva su propio cupo.
        $this->assertTrue($guard->allows(['update_id' => 5], 'chat-b'));
    }

    public function test_el_limite_global_frena_aunque_los_chats_sean_distintos(): void
    {
        $guard = new TelegramWebhookGuard('prueba', 100, 2);

        $this->assertTrue($guard->allows(['update_id' => 1], 'chat-a'));
        $this->assertTrue($guard->allows(['update_id' => 2], 'chat-b'));
        $this->assertFalse($guard->allows(['update_id' => 3], 'chat-c'));
    }

    public function test_los_updates_sin_chat_solo_cuentan_para_el_limite_global(): void
    {
        $guard = new TelegramWebhookGuard('prueba', 1, 5);

        $this->assertTrue($guard->allows(['update_id' => 1], ''));
        $this->assertTrue($guard->allows(['update_id' => 2], ''));
    }

    public function test_cada_bot_lleva_su_propia_cuenta(): void
    {
        $school = new TelegramWebhookGuard('academic', 1, 600);
        $integration = new TelegramWebhookGuard('integrationhub', 1, 600);

        $this->assertTrue($school->allows(['update_id' => 1], 'chat-a'));
        $this->assertFalse($school->allows(['update_id' => 2], 'chat-a'));

        // El cupo del otro bot no se consume con el primero.
        $this->assertTrue($integration->allows(['update_id' => 1], 'chat-a'));
    }
}
