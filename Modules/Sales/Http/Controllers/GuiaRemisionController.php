<?php

namespace Modules\Sales\Http\Controllers;

use App\Helpers\Invoice\Documents\GuiaRemision as GuiaRemisionService;
use App\Helpers\Invoice\Sunat\SunatFileStorage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Sales\Entities\GuiaRemision;
use Symfony\Component\HttpFoundation\Response;

class GuiaRemisionController extends Controller
{
    public function index()
    {
        $guias = GuiaRemision::query()
            ->with('details')
            ->when(request()->filled('search'), function ($query) {
                $search = '%'.request()->input('search').'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('serie', 'like', $search)
                        ->orWhere('correlativo', 'like', $search)
                        ->orWhere('destinatario_razon_social', 'like', $search)
                        ->orWhere('destinatario_num_doc', 'like', $search);
                });
            })
            ->when(request()->filled('status'), fn ($query) => $query->where('status', request()->input('status')))
            ->latest('id')
            ->paginate(10)
            ->onEachSide(2);

        return Inertia::render('Sales::Documents/GuiaRemision', [
            'guias' => $guias,
            'filters' => request()->all('search', 'status'),
            'motivos' => GuiaRemision::MOTIVOS_TRASLADO,
            'modalidades' => GuiaRemision::MODALIDADES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'serie' => ['required', 'string', 'max:10'],
            'correlativo' => ['nullable', 'string', 'max:20'],
            'fecha_emision' => ['required', 'date'],
            'cod_traslado' => ['required', 'in:01,02,04,09,13,14,17,18,19'],
            'des_traslado' => ['nullable', 'string', 'max:255'],
            'mod_traslado' => ['required', 'in:01,02'],
            'peso_total' => ['required', 'numeric', 'min:0.001'],
            'und_peso_total' => ['nullable', 'string', 'max:5'],
            'num_bultos' => ['nullable', 'integer', 'min:1'],
            'partida_ubigeo' => ['required', 'digits:6'],
            'partida_direccion' => ['required', 'string', 'max:255'],
            'llegada_ubigeo' => ['required', 'digits:6'],
            'llegada_direccion' => ['required', 'string', 'max:255'],
            'destinatario_tipo_doc' => ['required', 'in:0,1,4,6,7'],
            'destinatario_num_doc' => ['required', 'string', 'max:15'],
            'destinatario_razon_social' => ['required', 'string', 'max:255'],
            'destinatario_email' => ['nullable', 'email', 'max:150'],
            'transportista_tipo_doc' => ['required', 'in:4,6,1'],
            'transportista_num_doc' => ['required', 'string', 'max:15'],
            'transportista_razon_social' => ['required', 'string', 'max:255'],
            'transportista_nro_mtc' => ['nullable', 'string', 'max:20'],
            'vehiculo_placa' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:15'],
            'conductor_tipo_doc' => ['nullable', 'in:1,4,6'],
            'conductor_num_doc' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:15'],
            'conductor_nombres' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:150'],
            'conductor_apellidos' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:150'],
            'conductor_licencia' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:20'],
            'sale_document_id' => ['nullable', 'exists:sale_documents,id'],
            'doc_relacionado_tipo' => ['nullable', 'in:01,03'],
            'doc_relacionado_numero' => ['nullable', 'string', 'max:20'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.codigo' => ['nullable', 'string', 'max:50'],
            'details.*.descripcion' => ['required', 'string', 'max:255'],
            'details.*.unidad' => ['required', 'string', 'max:5'],
            'details.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'details.*.cod_prod_sunat' => ['nullable', 'string', 'max:20'],
        ], [
            'details.required' => 'Agrega al menos un bien a trasladar.',
            'vehiculo_placa.required_if' => 'El transporte privado exige la placa del vehículo.',
            'conductor_num_doc.required_if' => 'El transporte privado exige el documento del conductor.',
        ]);

        try {
            $guia = DB::transaction(function () use ($data) {
                $correlativo = $data['correlativo'] ?? null;

                if (empty($correlativo)) {
                    $ultimo = (int) GuiaRemision::where('serie', $data['serie'])->max('correlativo');
                    $correlativo = str_pad($ultimo + 1, 8, '0', STR_PAD_LEFT);
                }

                unset($data['details'], $data['correlativo']);

                $guia = GuiaRemision::create(array_merge($data, [
                    'correlativo' => $correlativo,
                    'tipo_doc' => '09',
                    'status' => GuiaRemision::STATUS_REGISTRADO,
                    'user_id' => Auth::id(),
                    'anio' => now()->format('Y'),
                ]));

                foreach (request()->input('details', []) as $index => $detail) {
                    $guia->details()->create([
                        'codigo' => $detail['codigo'] ?? null,
                        'descripcion' => $detail['descripcion'],
                        'unidad' => $detail['unidad'],
                        'cantidad' => $detail['cantidad'],
                        'cod_prod_sunat' => $detail['cod_prod_sunat'] ?? null,
                        'orden' => $index + 1,
                    ]);
                }

                return $guia;
            });

            $result = (new GuiaRemisionService)->create($guia->fresh('details'));

            return response()->json(array_merge($result, ['guia_id' => $guia->id, 'serie' => $guia->serie, 'correlativo' => $guia->correlativo]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la guía: '.$e->getMessage(),
            ], 500);
        }
    }

    public function send(int $id)
    {
        $guia = GuiaRemision::find($id);

        if (! $guia) {
            return response()->json(['success' => false, 'message' => 'La guía no existe.'], 404);
        }

        if ($guia->status === GuiaRemision::STATUS_ACEPTADO) {
            return response()->json(['success' => false, 'message' => 'La guía ya fue aceptada por SUNAT.'], 422);
        }

        $result = (new GuiaRemisionService)->create($guia);

        return response()->json($result);
    }

    public function check(int $id)
    {
        $guia = GuiaRemision::find($id);

        if (! $guia) {
            return response()->json(['success' => false, 'message' => 'La guía no existe.'], 404);
        }

        return response()->json((new GuiaRemisionService)->checkTicket($guia));
    }

    public function download(int $id, string $type)
    {
        $guia = GuiaRemision::find($id);

        if (! $guia) {
            return response()->json(['success' => false, 'message' => 'La guía no existe.'], 404);
        }

        $files = app(SunatFileStorage::class);
        $base = trim($guia->serie.'-'.$guia->correlativo, '-');
        $type = strtolower($type);

        $stored = match ($type) {
            'xml' => $guia->xml,
            'cdr' => $guia->cdr,
            'cdr-xml' => $files->cdrXmlPath($guia->cdr),
            default => null,
        };

        if ($type === 'cdr-xml' && $stored === null) {
            $contents = $files->cdrContents($guia->cdr);
            $stored = $contents['xml'] ?? null;

            if ($stored === null) {
                return $this->missing('El XML del CDR no está disponible. Consulta el ticket en SUNAT.');
            }

            return response($stored, 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="R-'.$base.'.xml"',
            ]);
        }

        if ($stored === null) {
            return $this->missing($type === 'xml'
                ? 'El XML de la guía todavía no está disponible.'
                : 'SUNAT todavía no devolvió el CDR de esta guía.');
        }

        $absolute = $files->absolutePath($stored);

        if ($absolute === null) {
            return $this->missing('El archivo ya no existe en el almacenamiento.');
        }

        return match ($type) {
            'xml' => response()->download($absolute, $base.'.xml', ['content-type' => 'application/xml']),
            'cdr' => response()->download($absolute, $base.'.zip', ['content-type' => 'application/zip']),
        };
    }

    public function destroy(int $id)
    {
        $guia = GuiaRemision::find($id);

        if (! $guia) {
            return response()->json(['success' => false, 'message' => 'La guía no existe.'], 404);
        }

        if ($guia->status === GuiaRemision::STATUS_ACEPTADO) {
            return response()->json([
                'success' => false,
                'message' => 'Una guía aceptada por SUNAT no se puede eliminar: la mercadería ya está respaldada con ese documento.',
            ], 422);
        }

        $guia->delete();

        return response()->json(['success' => true, 'message' => 'Guía eliminada.']);
    }

    private function missing(string $message, int $status = 404): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
