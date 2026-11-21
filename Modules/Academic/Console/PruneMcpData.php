<?php

namespace Modules\Academic\Console;

use Illuminate\Console\Command;
use Modules\Academic\Entities\McpAuditLog;
use Modules\Academic\Entities\McpOAuthCode;

/**
 * Limpieza de los datos que genera el servidor MCP del Académico: códigos de
 * autorización usados o vencidos y registros de auditoría antiguos. Los
 * clientes OAuth se conservan: borrarlos obligaría a reconectar el conector.
 */
class PruneMcpData extends Command
{
    protected $signature = 'academic:prune-mcp-data
                            {--days=30 : Días que se conservan los códigos OAuth usados o vencidos}
                            {--audit-days=90 : Días que se conservan los registros de auditoría}';

    protected $description = 'Limpia códigos OAuth y auditoría antigua del servidor MCP del Académico';

    public function handle(): int
    {
        $diasCodigos = max(1, (int) $this->option('days'));
        $diasAuditoria = max(1, (int) $this->option('audit-days'));

        $limiteCodigos = now()->subDays($diasCodigos);
        $limiteAuditoria = now()->subDays($diasAuditoria);

        $codigos = McpOAuthCode::query()
            ->where(function ($consulta) use ($limiteCodigos) {
                $consulta->where('expires_at', '<', $limiteCodigos)
                    ->orWhere(function ($consulta) use ($limiteCodigos) {
                        $consulta->whereNotNull('used_at')->where('used_at', '<', $limiteCodigos);
                    });
            })
            ->delete();

        $auditoria = McpAuditLog::query()
            ->where('created_at', '<', $limiteAuditoria)
            ->delete();

        $this->info("Códigos OAuth eliminados: {$codigos}. Registros de auditoría eliminados: {$auditoria}.");

        return self::SUCCESS;
    }
}
