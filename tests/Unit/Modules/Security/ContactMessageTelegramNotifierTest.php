<?php

namespace Tests\Unit\Modules\Security;

use App\Models\ContactMessage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Services\ContactMessageTelegramNotifier;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Aviso del formulario de contacto por el bot de alertas.
 *
 * Contrato:
 *   - llega solo a los destinatarios activos de Seguridad → Alertas;
 *   - lleva los datos del contacto y el texto escapado (una etiqueta escrita
 *     por la persona no puede romper ni dar formato al mensaje);
 *   - sin destinatarios activos no se envía nada;
 *   - respeta el interruptor general y el propio de los mensajes de contacto,
 *     sin depender del de errores.
 */
class ContactMessageTelegramNotifierTest extends TestCase
{
    use BuildsSecurityAlertSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSecurityAlertSchema();
        $this->createSecurityAlertSchema();

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('service')->nullable();
            $table->text('message');
            $table->enum('status', ['pending', 'read', 'replied'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contact_messages');
        $this->dropSecurityAlertSchema();

        parent::tearDown();
    }

    public function test_avisa_a_los_destinatarios_activos_con_los_datos_del_contacto(): void
    {
        SecurityAlertRecipient::create(['name' => 'Equipo', 'chat_id' => '111', 'is_active' => true]);
        SecurityAlertRecipient::create(['name' => 'Silenciado', 'chat_id' => '222', 'is_active' => false]);

        $enviados = [];

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendFormatted')
            ->once()
            ->andReturnUsing(function (string $chatId, string $text, bool $html) use (&$enviados) {
                $enviados[] = compact('chatId', 'text', 'html');

                return [];
            });

        $this->app->instance(TelegramBotService::class, $bot);

        $message = ContactMessage::create([
            'name' => 'Ana Quispe',
            'email' => 'ana@empresa.test',
            'phone' => '+51 999 111 222',
            'company' => 'Empresa Uno',
            'service' => 'automatizacion',
            'message' => 'Quiero <b>automatizar</b> & ordenar compras.',
        ]);

        app(ContactMessageTelegramNotifier::class)->notify($message);

        $this->assertCount(1, $enviados);
        $this->assertSame('111', $enviados[0]['chatId']);
        $this->assertTrue($enviados[0]['html']);

        $text = $enviados[0]['text'];

        $this->assertStringContainsString('Nuevo mensaje de contacto', $text);
        $this->assertStringContainsString('Ana Quispe', $text);
        $this->assertStringContainsString('ana@empresa.test', $text);
        $this->assertStringContainsString('+51 999 111 222', $text);
        $this->assertStringContainsString('Empresa Uno', $text);
        $this->assertStringContainsString('Automatizacion', $text);

        // El texto de la persona viaja escapado: se ve literal en Telegram.
        $this->assertStringContainsString(
            'Quiero &lt;b&gt;automatizar&lt;/b&gt; &amp; ordenar compras.',
            $text
        );
    }

    public function test_omite_telefono_y_empresa_cuando_no_llegaron(): void
    {
        $message = ContactMessage::create([
            'name' => 'Ana Quispe',
            'email' => 'ana@empresa.test',
            'service' => 'otro',
            'message' => 'Consulta sin datos opcionales.',
        ]);

        $text = app(ContactMessageTelegramNotifier::class)->text($message);

        $this->assertStringNotContainsString('Teléfono:', $text);
        $this->assertStringNotContainsString('Empresa:', $text);
    }

    public function test_no_envia_nada_sin_destinatarios_activos(): void
    {
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldNotReceive('sendFormatted');

        $this->app->instance(TelegramBotService::class, $bot);

        $message = ContactMessage::create([
            'name' => 'Ana Quispe',
            'email' => 'ana@empresa.test',
            'service' => 'otro',
            'message' => 'Consulta.',
        ]);

        app(ContactMessageTelegramNotifier::class)->notify($message);

        $this->assertTrue(true);
    }

    public function test_no_avisa_cuando_el_interruptor_general_esta_apagado(): void
    {
        SecurityAlertRecipient::create(['name' => 'Equipo', 'chat_id' => '111', 'is_active' => true]);
        SecurityAlertSetting::current()->update(['enabled' => false]);

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldNotReceive('sendFormatted');

        $this->app->instance(TelegramBotService::class, $bot);

        app(ContactMessageTelegramNotifier::class)->notify($this->contactMessage());

        $this->assertTrue(true);
    }

    public function test_no_avisa_cuando_los_mensajes_de_contacto_estan_apagados(): void
    {
        SecurityAlertRecipient::create(['name' => 'Equipo', 'chat_id' => '111', 'is_active' => true]);
        SecurityAlertSetting::current()->update(['contacts_enabled' => false]);

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldNotReceive('sendFormatted');

        $this->app->instance(TelegramBotService::class, $bot);

        app(ContactMessageTelegramNotifier::class)->notify($this->contactMessage());

        $this->assertTrue(true);
    }

    public function test_apagar_los_errores_no_silencia_los_contactos(): void
    {
        SecurityAlertRecipient::create(['name' => 'Equipo', 'chat_id' => '111', 'is_active' => true]);
        SecurityAlertSetting::current()->update(['errors_enabled' => false]);

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendFormatted')->once()->andReturn([]);

        $this->app->instance(TelegramBotService::class, $bot);

        app(ContactMessageTelegramNotifier::class)->notify($this->contactMessage());

        $this->assertTrue(true);
    }

    private function contactMessage(): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Ana Quispe',
            'email' => 'ana@empresa.test',
            'service' => 'otro',
            'message' => 'Consulta de prueba.',
        ]);
    }
}
