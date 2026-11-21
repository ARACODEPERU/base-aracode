<?php

namespace Modules\Academic\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Academic\Support\McpTokenLifetime;

/**
 * Genera el token Bearer que usa un agente de IA para entrar al servidor MCP
 * del Académico. El valor se muestra una sola vez.
 */
class CreateMcpToken extends Command
{
    protected $signature = 'mcp:token
                            {email : Correo del usuario del ERP}
                            {--abilities=academic:read,academic:write : Abilities separadas por coma}
                            {--name=mcp : Nombre del token (cliente o equipo)}
                            {--expires= : Vigencia: 1_semana, 2_semanas, 1_mes, 2_meses, 3_meses, 6_meses, 1_anio, 2_anios o indefinido (por defecto 3_meses)}';

    protected $description = 'Crea un token Sanctum para el servidor MCP del Académico';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if (! $usuario) {
            $this->error('No existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $abilities = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->option('abilities'))
        )));

        if ($abilities === []) {
            $abilities = ['academic:read'];
        }

        $permitidas = ['academic:read', 'academic:write', 'academic:write_active', 'academic:autonomous'];
        $invalidas = array_values(array_diff($abilities, $permitidas));

        if ($invalidas !== []) {
            $this->error('Ability no válida: ' . implode(', ', $invalidas) . '. Usa academic:read, academic:write, academic:write_active y/o academic:autonomous.');

            return self::FAILURE;
        }

        if (in_array('academic:autonomous', $abilities, true) && ! in_array('academic:write', $abilities, true)) {
            $this->error('academic:autonomous requiere también academic:write.');

            return self::FAILURE;
        }

        if (in_array('academic:write_active', $abilities, true) && ! in_array('academic:read', $abilities, true)) {
            $this->error('academic:write_active requiere también academic:read.');

            return self::FAILURE;
        }

        // Vigencia: sin `--expires` se aplica la de por defecto (3 meses).
        $vigencia = (string) ($this->option('expires') ?: McpTokenLifetime::POR_DEFECTO);

        if (! McpTokenLifetime::existe($vigencia)) {
            $this->error('Vigencia no válida: ' . $vigencia . '. Usa ' . implode(', ', McpTokenLifetime::claves()) . '.');

            return self::FAILURE;
        }

        $vence = McpTokenLifetime::vencimiento($vigencia);

        $token = $usuario->createToken((string) $this->option('name'), $abilities, $vence);

        $this->info('Token creado para ' . $usuario->email . ' con abilities: ' . implode(', ', $abilities));
        $this->line('Vigencia: ' . McpTokenLifetime::etiqueta($vigencia) . ($vence ? ' (vence el ' . $vence->format('Y-m-d') . ')' : ' (no vence)'));
        $this->newLine();
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->comment('Guárdalo ahora: no se vuelve a mostrar. Úsalo como "Authorization: Bearer <token>".');
        $this->comment('El usuario además necesita los permisos del Académico (por ejemplo aca_cursos_nuevo y aca_cursos_editar).');

        return self::SUCCESS;
    }
}
