<?php

namespace Tests\Unit\Modules\CRM;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\CRM\Entities\CrmMessage;
use Modules\CRM\Http\Controllers\CrmMessagesController;
use Tests\TestCase;

/**
 * Edición de mensajes del chat.
 *
 * Contrato:
 *   - solo el autor puede editar su mensaje;
 *   - solo durante los primeros 90 segundos;
 *   - solo mensajes de texto;
 *   - el contenido se actualiza y se avisa por socket como una edición.
 */
class ChatMessageEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSchema();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSchema();

        parent::tearDown();
    }

    public function test_la_ruta_de_edicion_esta_disponible_para_los_tres_chats(): void
    {
        $route = app('router')->getRoutes()->getByName('crm_chat_message_update');

        $this->assertNotNull($route);
        $this->assertContains('PUT', $route->methods());
        $this->assertStringContainsString(
            'crm_chat_messages|crm_chat_asistente|crm_clientes_preguntas_ia',
            implode(' ', $route->getAction('middleware') ?? [])
        );
    }

    public function test_el_autor_puede_editar_su_mensaje_dentro_de_los_90_segundos(): void
    {
        $autor = $this->usuario(10);
        $mensaje = $this->mensaje(10);

        $response = $this->editar($autor, $mensaje, '<p>Respuesta corregida</p>');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('<p>Respuesta corregida</p>', $mensaje->fresh()->content);
    }

    public function test_no_puede_editar_el_mensaje_de_otra_persona(): void
    {
        $otraPersona = $this->usuario(11);
        $mensaje = $this->mensaje(10);

        $response = $this->editar($otraPersona, $mensaje, 'Intento de edición');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('tus propios mensajes', $response->getData(true)['message']);
        $this->assertSame('Hola', $mensaje->fresh()->content);
    }

    public function test_no_puede_editar_despues_de_90_segundos(): void
    {
        $autor = $this->usuario(10);
        $mensaje = $this->mensaje(10);

        DB::table('crm_messages')->where('id', $mensaje->id)->update([
            'created_at' => now()->subSeconds(91),
            'updated_at' => now()->subSeconds(91),
        ]);

        $response = $this->editar($autor, $mensaje, 'Demasiado tarde');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('90 segundos', $response->getData(true)['message']);
        $this->assertSame('Hola', $mensaje->fresh()->content);
    }

    public function test_no_puede_editar_mensajes_que_no_son_de_texto(): void
    {
        $autor = $this->usuario(10);
        $mensaje = $this->mensaje(10, ['type' => 'audio']);

        $response = $this->editar($autor, $mensaje, 'No aplica');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('mensajes de texto', $response->getData(true)['message']);
    }

    public function test_devuelve_404_si_el_mensaje_ya_no_existe(): void
    {
        $autor = $this->usuario(10);
        $request = Request::create('/crm/conversations/messages/update', 'PUT', [
            'message_id' => 999999,
            'text' => 'Sin mensaje',
        ]);
        $request->headers->set('Accept', 'application/json');

        $this->actingAs($autor);

        $response = $this->controlador()->updateMessage($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('ya no existe', $response->getData(true)['message']);
    }

    private function editar(User $usuario, CrmMessage $mensaje, string $texto): JsonResponse
    {
        $request = Request::create('/crm/conversations/messages/update', 'PUT', [
            'message_id' => $mensaje->id,
            'text' => $texto,
        ]);
        $request->headers->set('Accept', 'application/json');

        $this->actingAs($usuario);

        return $this->controlador()->updateMessage($request);
    }

    /**
     * El controlador real, sin salir a la red al avisar por socket.
     */
    private function controlador(): CrmMessagesController
    {
        return new class extends CrmMessagesController {
            public function broadcastSend($participants, $message, $personId)
            {
                // La edición se verifica en la base; el socket no es parte del test.
            }
        };
    }

    private function usuario(int $personId): User
    {
        return User::create([
            'name' => 'Usuario ' . $personId,
            'email' => "persona{$personId}@example.test",
            'email_verified_at' => now(),
            'password' => Hash::make('secreto-de-prueba'),
            'person_id' => $personId,
        ]);
    }

    private function mensaje(int $personId, array $atributos = []): CrmMessage
    {
        return CrmMessage::create(array_merge([
            'conversation_id' => 1,
            'person_id' => $personId,
            'content' => 'Hola',
            'type' => 'text',
            'answer_ai' => false,
        ], $atributos));
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('name');
            $tabla->string('email')->unique();
            $tabla->timestamp('email_verified_at')->nullable();
            $tabla->string('password');
            $tabla->unsignedBigInteger('person_id')->nullable();
            $tabla->rememberToken();
            $tabla->timestamps();
        });

        // Spatie consulta la tabla de roles al resolver los avisos adicionales
        // del broadcast, aunque no haya ningún rol creado.
        Schema::create('roles', function (Blueprint $tabla) {
            $tabla->bigIncrements('id');
            $tabla->string('name');
            $tabla->string('guard_name');
            $tabla->timestamps();
            $tabla->unique(['name', 'guard_name']);
        });

        Schema::create('crm_messages', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->unsignedBigInteger('conversation_id');
            $tabla->unsignedBigInteger('person_id');
            $tabla->unsignedBigInteger('sent_by_user_id')->nullable();
            $tabla->text('content');
            $tabla->string('type')->nullable();
            $tabla->boolean('answer_ai')->default(false);
            $tabla->timestamps();
        });

        Schema::create('crm_participants', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->unsignedBigInteger('conversation_id');
            $tabla->unsignedBigInteger('user_id')->nullable();
            $tabla->unsignedBigInteger('person_id')->nullable();
        });
    }

    private function dropSchema(): void
    {
        foreach (['crm_participants', 'crm_messages', 'roles', 'users'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
}
