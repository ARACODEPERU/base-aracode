<?php

namespace Modules\Sales\Services\Sire;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Sales\Entities\SireSubmission;
use Throwable;

/**
 * Descarga de la propuesta del SIRE (solo lectura).
 *
 * SUNAT no devuelve el archivo en la primera llamada: entrega un `numTicket`,
 * hay que consultar su estado hasta `06` (terminado) y recién entonces pedir
 * el archivo. Todo el proceso queda en `sire_submissions` con el TXT guardado y
 * su hash, y cada llamada en la bitácora.
 */
class SirePropuestaService
{
    /** Rutas de exportación de la propuesta por tipo de registro. */
    private const EXPORTAR = [
        'ventas' => '/v1/contribuyente/migeigv/libros/rvie/propuesta/web/propuesta/%s/exportapropuesta',
        'compras' => '/v1/contribuyente/migeigv/libros/rce/propuesta/web/propuesta/%s/exportacioncomprobantepropuesta',
    ];

    private const CONSULTA_TICKET = '/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/consultaestadotickets';

    private const DESCARGA = '/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/archivoreporte';

    private const LIBRO = [
        'ventas' => '140000',
        'compras' => '080000',
    ];

    /** Código de proceso de la exportación, usado si el ticket no lo informa. */
    private const PROCESO = [
        'ventas' => '3',
        'compras' => '61',
    ];

    private const ETIQUETAS_ESTADO = [
        '01' => 'Solicitado',
        '05' => 'En proceso',
        '06' => 'Terminado',
        'EXP' => 'Expirado',
    ];

    public function __construct(private SireApiClient $client)
    {
    }

    /**
     * Exporta la propuesta, espera el ticket y guarda el TXT descargado.
     *
     * @return array<string, mixed>
     */
    public function descargar(
        Company $company,
        string $tipo,
        string $period,
        ?int $userId = null,
        int $intentos = 5,
        int $esperaMs = 1500
    ): array {
        $tipo = $tipo === 'compras' ? 'compras' : 'ventas';

        if (! $this->client->credencialesCompletas($company)) {
            throw new SireApiException(
                'Faltan las credenciales del SIRE. Carga el client_id, el client_secret y el usuario y clave SOL en Configuración → SIRE.'
            );
        }

        $periodo = str_replace('-', '', $period);

        $submission = SireSubmission::create([
            'company_id' => $company->getKey(),
            'user_id' => $userId,
            'tipo' => $tipo,
            'period' => $periodo,
            'action' => 'exportar',
            'status' => 'solicitado',
            'started_at' => now(),
        ]);

        try {
            $ticket = $this->solicitar($company, $tipo, $periodo, $submission);

            $submission->update(['ticket' => $ticket, 'status' => 'en_proceso']);

            [$estado, $registro] = $this->esperar($company, $tipo, $periodo, $ticket, $submission, $intentos, $esperaMs);

            if ($estado !== '06') {
                return [
                    'ok' => true,
                    'descargado' => false,
                    'ticket' => $ticket,
                    'estado' => $estado,
                    'estado_etiqueta' => self::ETIQUETAS_ESTADO[$estado] ?? 'En proceso',
                    'archivo' => null,
                    'lineas' => 0,
                    'hash' => null,
                    'submission_id' => $submission->id,
                    'message' => 'SUNAT todavía está generando la propuesta (ticket ' . $ticket
                        . '). Vuelve a consultar en unos minutos.',
                ];
            }

            $body = $this->descargarArchivo($company, $tipo, $periodo, $ticket, $registro, $submission);
            $texto = $this->extraerTexto($body);
            $lineas = $this->contarLineas($texto);
            $archivo = (string) ($registro['nomArchivoReporte'] ?? $registro['nomArchivo'] ?? '');
            $ruta = $this->ruta($company, $periodo, $tipo, $ticket);

            Storage::disk('local')->put($ruta, $texto);

            $submission->update([
                'status' => 'terminado',
                'file_name' => $archivo !== '' ? $archivo : basename($ruta),
                'file_path' => $ruta,
                'file_hash' => hash('sha256', $texto),
                'lines' => $lineas,
                'response' => [
                    'ticket' => $ticket,
                    'estado' => $estado,
                    'archivo' => $archivo,
                    'nomArchivoReporte' => $archivo,
                ],
                'finished_at' => now(),
            ]);

            return [
                'ok' => true,
                'descargado' => true,
                'ticket' => $ticket,
                'estado' => $estado,
                'estado_etiqueta' => self::ETIQUETAS_ESTADO[$estado] ?? 'Terminado',
                'archivo' => $submission->file_name,
                'lineas' => $lineas,
                'hash' => $submission->file_hash,
                'submission_id' => $submission->id,
                'message' => 'Propuesta de SUNAT descargada: ' . $lineas . ' línea(s) en el archivo del período.',
            ];
        } catch (SireApiException $e) {
            $submission->refresh();

            if (! in_array($submission->status, ['expirado', 'error'], true)) {
                $submission->update([
                    'status' => 'error',
                    'response' => ['error' => Str::limit($e->getMessage(), 900)],
                    'finished_at' => now(),
                ]);
            }

            throw $e;
        } catch (Throwable $e) {
            $submission->update([
                'status' => 'error',
                'response' => ['error' => Str::limit($e->getMessage(), 900)],
                'finished_at' => now(),
            ]);

            throw new SireApiException('No se pudo descargar la propuesta: ' . $e->getMessage());
        }
    }

    /**
     * Pide la exportación y devuelve el ticket de SUNAT.
     */
    private function solicitar(Company $company, string $tipo, string $periodo, SireSubmission $submission): string
    {
        $query = ['codTipoArchivo' => 0];

        if ($tipo === 'compras') {
            $query['codOrigenEnvio'] = 2;
        }

        $json = $this->client->get(
            $company,
            sprintf(self::EXPORTAR[$tipo], $periodo),
            $query,
            'exportar',
            $submission
        );

        $ticket = $this->buscar($json, ['numTicket', 'numeroTicket', 'nroTicket', 'ticket']);

        if ($ticket === '') {
            throw new SireApiException('SUNAT no devolvió el ticket de la exportación de la propuesta.');
        }

        return $ticket;
    }

    /**
     * Consulta el ticket hasta que SUNAT lo marca como terminado.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function esperar(
        Company $company,
        string $tipo,
        string $periodo,
        string $ticket,
        SireSubmission $submission,
        int $intentos,
        int $esperaMs
    ): array {
        $estado = '';
        $registro = [];

        $intentos = max(1, $intentos);

        for ($i = 1; $i <= $intentos; $i++) {
            $json = $this->client->get($company, self::CONSULTA_TICKET, [
                'perIni' => $periodo,
                'perFin' => $periodo,
                'page' => 1,
                'perPage' => 100,
                'numTicket' => $ticket,
            ], 'ticket', $submission);

            $registro = $this->registroEstado($json);
            $estado = $this->normalizarEstado($registro);

            $submission->update([
                'status' => $estado === '06' ? 'terminado' : 'en_proceso',
                'attempts' => $i,
            ]);

            if ($estado === '06') {
                return [$estado, $registro];
            }

            if ($estado === 'EXP') {
                $submission->update([
                    'status' => 'expirado',
                    'response' => ['ticket' => $ticket, 'estado' => $estado],
                    'finished_at' => now(),
                ]);

                throw new SireApiException(
                    'El ticket ' . $ticket . ' expiró antes de generar la propuesta. Solicítala de nuevo.',
                    null,
                    $registro,
                    $ticket
                );
            }

            if ($i < $intentos && $esperaMs > 0) {
                usleep($esperaMs * 1000);
            }
        }

        return [$estado, $registro];
    }

    /**
     * Descarga el archivo de la propuesta ya generada.
     *
     * @param  array<string, mixed>  $registro
     */
    private function descargarArchivo(
        Company $company,
        string $tipo,
        string $periodo,
        string $ticket,
        array $registro,
        SireSubmission $submission
    ): string {
        $archivo = (string) ($registro['nomArchivoReporte'] ?? $registro['nomArchivo'] ?? '');

        if ($archivo === '') {
            throw new SireApiException(
                'SUNAT marcó el ticket como terminado pero no informó el nombre del archivo de la propuesta.',
                null,
                $registro,
                $ticket
            );
        }

        return $this->client->descargar($company, self::DESCARGA, [
            'nomArchivoReporte' => $archivo,
            'codTipoArchivoReporte' => (string) ($registro['codTipoArchivoReporte'] ?? '0'),
            'perTributario' => $periodo,
            'codProceso' => (string) ($registro['codProceso'] ?? self::PROCESO[$tipo]),
            'numTicket' => $ticket,
            'codLibro' => self::LIBRO[$tipo],
        ], 'descarga', $submission);
    }

    /**
     * El archivo puede venir como TXT o como ZIP con uno o varios TXT.
     */
    private function extraerTexto(string $body): string
    {
        if (strncmp($body, 'PK', 2) !== 0) {
            return $body;
        }

        $ruta = tempnam(sys_get_temp_dir(), 'sire_propuesta_');

        if ($ruta === false) {
            throw new SireApiException('No se pudo preparar el archivo temporal de la propuesta.');
        }

        file_put_contents($ruta, $body);

        $zip = new \ZipArchive();
        $texto = '';

        if ($zip->open($ruta) !== true) {
            @unlink($ruta);

            throw new SireApiException('El ZIP de la propuesta no se pudo abrir.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = (string) $zip->getNameIndex($i);

            if (strtoupper(substr($nombre, -4)) !== '.TXT') {
                continue;
            }

            $contenido = (string) $zip->getFromIndex($i);

            if ($contenido === '') {
                continue;
            }

            $texto .= rtrim($contenido, "\r\n") . "\r\n";
        }

        $zip->close();
        @unlink($ruta);

        if ($texto === '') {
            throw new SireApiException('El ZIP de la propuesta no contiene ningún archivo TXT.');
        }

        return $texto;
    }

    private function contarLineas(string $texto): int
    {
        $lineas = preg_split('/\r\n|\r|\n/', $texto) ?: [];

        return count(array_filter($lineas, fn ($linea) => trim($linea) !== ''));
    }

    private function ruta(Company $company, string $periodo, string $tipo, string $ticket): string
    {
        return 'sire/' . $company->getKey() . '/' . $periodo . '/' . $tipo . '-propuesta-' . $ticket . '.txt';
    }

    /**
     * Busca un valor escalar en una respuesta que puede venir anidada.
     *
     * @param  array<string|int, mixed>  $datos
     * @param  array<int, string>  $claves
     */
    private function buscar(array $datos, array $claves): string
    {
        foreach ($claves as $clave) {
            if (isset($datos[$clave]) && is_scalar($datos[$clave]) && (string) $datos[$clave] !== '') {
                return (string) $datos[$clave];
            }
        }

        foreach ($datos as $valor) {
            if (is_array($valor)) {
                $encontrado = $this->buscar($valor, $claves);

                if ($encontrado !== '') {
                    return $encontrado;
                }
            }
        }

        return '';
    }

    /**
     * La consulta de tickets puede devolver un objeto o una lista; se toma el
     * primer registro que traiga `estado`.
     *
     * @param  array<string|int, mixed>  $datos
     * @return array<string, mixed>
     */
    private function registroEstado(array $datos): array
    {
        if (isset($datos['estado'])) {
            return $datos;
        }

        foreach ($datos as $valor) {
            if (is_array($valor)) {
                $registro = $this->registroEstado($valor);

                if (isset($registro['estado'])) {
                    return $registro;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $registro
     */
    private function normalizarEstado(array $registro): string
    {
        $valor = strtoupper(trim((string) ($registro['estado'] ?? '')));

        $equivalentes = [
            'TERMINADO' => '06',
            'PROCESO' => '05',
            'EN PROCESO' => '05',
            'SOLICITADO' => '01',
            'EXPIRADO' => 'EXP',
        ];

        if (isset($equivalentes[$valor])) {
            return $equivalentes[$valor];
        }

        if ($valor !== '' && ctype_digit($valor)) {
            return str_pad($valor, 2, '0', STR_PAD_LEFT);
        }

        return $valor;
    }
}
