<?php

namespace Tests\Unit\Database;

use Dotenv\Parser\Parser;
use Tests\TestCase;

/**
 * `.env.example` es la primera cosa que se copia en un despliegue.
 *
 * Si deja de ser un `.env` válido — por ejemplo con un encabezado entre
 * corchetes como `[TEMPLATE]`, que phpdotenv rechaza por no ser un nombre de
 * variable — Laravel lo ignora COMPLETO en silencio (`safeLoad()` se traga la
 * excepción) y la instalación falla con "No application encryption key has been
 * specified", que no da ninguna pista del motivo real.
 */
class EnvExampleFileTest extends TestCase
{
    private function content(): string
    {
        $content = file_get_contents(base_path('.env.example'));

        $this->assertIsString($content, 'No se pudo leer .env.example.');

        return $content;
    }

    public function test_es_un_dotenv_valido_para_phpdotenv(): void
    {
        $content = $this->content();

        try {
            $entries = (new Parser())->parse($content);
        } catch (\Throwable $e) {
            $this->fail('.env.example no es un .env válido: '.$e->getMessage());
        }

        $this->assertNotEmpty($entries, '.env.example no declara ninguna variable.');
    }

    public function test_todas_sus_lineas_son_asignaciones_o_comentarios(): void
    {
        $lines = file(base_path('.env.example'), FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($lines);

        foreach ($lines as $index => $line) {
            $trimmed = trim((string) $line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_.]+=/',
                $trimmed,
                'La línea '.($index + 1).' de .env.example no es una asignación ni un comentario: '.$trimmed
            );
        }
    }

    public function test_declara_las_claves_que_necesita_una_instalacion_nueva(): void
    {
        $content = $this->content();

        foreach ([
            'APP_KEY=',
            'DB_CONNECTION=',
            'ADMIN_EMAIL=',
            'ADMIN_PASSWORD=',
            'SUPER_EDITOR_ENABLED=',
        ] as $key) {
            $this->assertStringContainsString($key, $content, "Falta {$key} en .env.example.");
        }
    }
}
