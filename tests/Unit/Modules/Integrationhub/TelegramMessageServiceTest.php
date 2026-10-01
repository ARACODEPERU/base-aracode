<?php

namespace Tests\Unit\Modules\Integrationhub;

use Modules\Integrationhub\Entities\IntegrationTelegramMessage;
use Modules\Integrationhub\Services\TelegramMessageService;
use Modules\Integrationhub\Support\TelegramMessages;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Textos del bot de Telegram: catalogo, render y reemplazos.
 *
 * La promesa: cada mensaje tiene texto de fabrica, las variables se reemplazan y
 * una linea que depende de una variable vacia no se envia, y lo que el
 * administrador guarda se respeta hasta que lo restaure.
 */
class TelegramMessageServiceTest extends TestCase
{
    use BuildsTelegramBotSchema;

    private TelegramMessageService $messages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramSchema();
        $this->createTelegramSchema();

        $this->messages = app(TelegramMessageService::class);
    }

    protected function tearDown(): void
    {
        $this->dropTelegramSchema();

        parent::tearDown();
    }

    public function test_todos_los_mensajes_del_catalogo_tienen_texto_de_fabrica(): void
    {
        foreach (TelegramMessages::all() as $code => $definition) {
            $this->assertNotSame('', trim($this->messages->body($code)), "El mensaje {$code} no tiene texto.");
            $this->assertNotSame('', trim((string) $definition['name']));
            $this->assertNotSame('', trim((string) $definition['description']));
            $this->assertContains($definition['format'], [TelegramMessages::FORMAT_HTML, TelegramMessages::FORMAT_TEXT]);
        }

        // El catalogo completo queda disponible para la pantalla de configuracion.
        $this->assertCount(count(TelegramMessages::codes()), $this->messages->catalog());
    }

    public function test_reemplaza_las_variables_del_mensaje(): void
    {
        $texto = $this->messages->render(TelegramMessages::REGISTERED, [
            'nombre' => 'Ana Pérez',
            'programas' => 'Especialización en NIIF',
            'suscripcion' => 'Suscripción activa',
        ]);

        $this->assertStringContainsString('¡Listo, Ana Pérez!', $texto);
        $this->assertStringContainsString('📚 Programas: Especialización en NIIF', $texto);
        $this->assertStringContainsString('💳 Suscripción activa', $texto);
    }

    public function test_una_linea_con_variable_vacia_no_se_envia(): void
    {
        // Quien se registro por suscripcion no tiene programas que listar, y el
        // aviso no debe mostrar la etiqueta ni una linea vacia.
        $texto = $this->messages->render(TelegramMessages::REGISTERED, [
            'nombre' => 'Luis',
            'programas' => '',
            'suscripcion' => '',
        ]);

        $this->assertStringNotContainsString('Programas:', $texto);
        $this->assertStringNotContainsString('💳', $texto);
        $this->assertStringContainsString('¡Listo, Luis!', $texto);
    }

    public function test_la_plantilla_de_las_campanas_usa_el_curso_y_el_tiempo_del_formulario(): void
    {
        $texto = $this->messages->render(TelegramMessages::CAMPAIGN, [
            'mensaje' => 'La clase de hoy empieza 15 minutos después.',
            'curso' => 'Especialización en NIIF',
            'tiempo' => '15 minutos',
            'nombre' => 'Ana',
        ]);

        $this->assertStringContainsString('👋 Hola Ana:', $texto);
        $this->assertStringContainsString('La clase de hoy empieza', $texto);
        $this->assertStringContainsString('Curso: Especialización en NIIF', $texto);
        $this->assertStringContainsString('Tiempo: 15 minutos', $texto);
    }

    public function test_sin_tiempo_la_linea_del_tiempo_desaparece(): void
    {
        $texto = $this->messages->render(TelegramMessages::CAMPAIGN, [
            'mensaje' => 'Ya está disponible el material.',
            'curso' => 'Especialización en NIIF',
            'tiempo' => null,
            'nombre' => 'Ana',
        ]);

        $this->assertStringContainsString('Curso: Especialización en NIIF', $texto);
        $this->assertStringNotContainsString('Tiempo:', $texto);
        $this->assertStringNotContainsString('⏰', $texto);
    }

    public function test_una_variable_desconocida_queda_escrita(): void
    {
        $this->messages->save(TelegramMessages::HELP, 'Hola {apellido}', TelegramMessages::FORMAT_HTML);

        $this->assertSame('Hola {apellido}', $this->messages->render(TelegramMessages::HELP));
    }

    public function test_guardar_un_texto_reemplaza_el_de_fabrica(): void
    {
        $default = $this->messages->body(TelegramMessages::START_HINT);

        $this->messages->save(TelegramMessages::START_HINT, '✍️ Escribe /start para activar los avisos.', TelegramMessages::FORMAT_HTML, 7);

        $this->assertNotSame($default, $this->messages->body(TelegramMessages::START_HINT));
        $this->assertSame('✍️ Escribe /start para activar los avisos.', $this->messages->body(TelegramMessages::START_HINT));
        $this->assertSame(7, (int) IntegrationTelegramMessage::where('code', TelegramMessages::START_HINT)->value('updated_by'));
    }

    public function test_un_texto_vacio_vuelve_al_de_fabrica(): void
    {
        $default = $this->messages->body(TelegramMessages::HELP);

        $this->messages->save(TelegramMessages::HELP, 'Texto temporal');
        $this->messages->save(TelegramMessages::HELP, '   ');

        $this->assertSame($default, $this->messages->body(TelegramMessages::HELP));
    }

    public function test_restaurar_devuelve_el_texto_de_fabrica(): void
    {
        $default = $this->messages->body(TelegramMessages::OPTOUT_DONE);

        $this->messages->save(TelegramMessages::OPTOUT_DONE, 'Chau');
        $this->messages->reset(TelegramMessages::OPTOUT_DONE);

        $this->assertSame($default, $this->messages->body(TelegramMessages::OPTOUT_DONE));
        $this->assertSame(0, IntegrationTelegramMessage::count());
    }

    public function test_restaurar_todos_borra_los_reemplazos(): void
    {
        $this->messages->save(TelegramMessages::HELP, 'Uno');
        $this->messages->save(TelegramMessages::START_HINT, 'Dos');

        $this->messages->resetAll();

        $this->assertSame(0, IntegrationTelegramMessage::count());
        $this->assertStringContainsString('bot de avisos', $this->messages->body(TelegramMessages::HELP));
    }

    public function test_el_formato_sale_de_la_configuracion(): void
    {
        $this->assertTrue($this->messages->isHtml(TelegramMessages::CAMPAIGN));

        $this->messages->save(TelegramMessages::CAMPAIGN, 'Sin formato', TelegramMessages::FORMAT_TEXT);

        $this->assertFalse($this->messages->isHtml(TelegramMessages::CAMPAIGN));

        $this->messages->reset(TelegramMessages::CAMPAIGN);

        $this->assertTrue($this->messages->isHtml(TelegramMessages::CAMPAIGN));
    }

    public function test_el_catalogo_marca_los_textos_modificados(): void
    {
        $this->messages->save(TelegramMessages::HELP, 'Otro texto');

        $catalogo = collect($this->messages->catalog())->keyBy('code');

        $this->assertTrue($catalogo[TelegramMessages::HELP]['is_customized']);
        $this->assertFalse($catalogo[TelegramMessages::START_HINT]['is_customized']);
        $this->assertSame('Otro texto', $catalogo[TelegramMessages::HELP]['body']);
        $this->assertNotSame($catalogo[TelegramMessages::HELP]['body'], $catalogo[TelegramMessages::HELP]['default_body']);
    }

    public function test_un_codigo_desconocido_no_se_puede_guardar(): void
    {
        $this->expectException(RuntimeException::class);

        $this->messages->save('mensaje_inventado', 'Hola');
    }
}
