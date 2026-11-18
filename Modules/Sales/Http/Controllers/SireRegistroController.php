<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Sales\Services\Sire\SireApiClient;
use Modules\Sales\Services\Sire\SireArchivoPlano;

/**
 * Base de las pantallas del SIRE: período tributario, vista previa acotada y
 * descargas del mismo archivo plano en TXT o ZIP.
 *
 * La descarga se bloquea si el validador interno encuentra errores que SUNAT
 * rechazaría (campos obligatorios, documentos inválidos o importes que no
 * cuadran). Las observaciones que no bloquean siguen mostrándose aparte.
 */
abstract class SireRegistroController extends Controller
{
    /**
     * Filas que se envían a la pantalla; el archivo se arma completo solo al
     * descargarlo.
     */
    private const PREVIEW_LIMIT = 200;

    /**
     * @return array<string, mixed>
     */
    abstract protected function build(string $period, ?int $previewLimit = null): array;

    abstract protected function tipo(): string;

    /**
     * Ruta de la pantalla, usada cuando la descarga se bloquea.
     */
    abstract protected function indexRoute(): string;

    public function index(Request $request)
    {
        $period = $this->period($request);
        $company = Company::query()->first();

        return Inertia::render('Sales::Sire/Registro', array_merge(
            $this->build($period, self::PREVIEW_LIMIT),
            [
                'period' => $period,
                'tipo' => $this->tipo(),
                'mode' => $company?->sire_mode === 'api' ? 'api' : 'txt',
                'credentialsReady' => $company
                    ? app(SireApiClient::class)->credencialesCompletas($company)
                    : false,
            ]
        ));
    }

    /**
     * Descarga del TXT que se sube a SOL o al aplicativo cliente SIRE.
     */
    public function txt(Request $request)
    {
        $period = $this->period($request);
        $data = $this->build($period);

        if ($data['errors'] !== []) {
            return $this->descargaBloqueada($data, $period);
        }

        return response($data['content'], 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $data['fileName'] . '"',
        ]);
    }

    /**
     * Descarga del mismo TXT comprimido, que es el formato que acepta la carga
     * de archivos del portal.
     */
    public function zip(Request $request)
    {
        $period = $this->period($request);
        $data = $this->build($period);

        if ($data['errors'] !== []) {
            return $this->descargaBloqueada($data, $period);
        }

        return response()
            ->download(SireArchivoPlano::zip($data['content'], $data['fileName']), $data['fileName'] . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * El validador encontró errores: no se entrega el archivo y se vuelve a la
     * pantalla, donde se listan los motivos.
     *
     * @param  array<string, mixed>  $data
     */
    private function descargaBloqueada(array $data, string $period)
    {
        return redirect()
            ->route($this->indexRoute(), ['period' => $period])
            ->with(
                'error',
                'No se puede descargar el registro: hay ' . count($data['errors'])
                . ' error(es) que SUNAT rechazaría. Corrígelos y vuelve a intentarlo.'
            );
    }

    /**
     * Período tributario en formato YYYY-MM; por defecto el mes en curso.
     */
    private function period(Request $request): string
    {
        $period = (string) $request->get('period');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1
            ? $period
            : Carbon::now()->format('Y-m');
    }
}
