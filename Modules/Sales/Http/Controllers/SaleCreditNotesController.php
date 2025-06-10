<?php

namespace Modules\Sales\Http\Controllers;

use App\Helpers\Invoice\Documents\NotaCredito;
use App\Helpers\Invoice\Documents\NotaDebito;
use App\Helpers\NumberLetter;
use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleDocumentType;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use DataTables;
use Exception;
use Greenter\Model\Sale\Note;
use Illuminate\Foundation\Validation\ValidatesRequests;

class SaleCreditNotesController extends Controller
{
    private $ubl;
    private $igv;
    private $top;
    private $icbper;
    use ValidatesRequests;

    public function __construct()
    {
        $this->ubl = Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = Parameter::where('parameter_code', 'P000002')->value('value_default');
        $this->icbper = Parameter::where('parameter_code', 'P000004')->value('value_default');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $affectations = DB::table('sunat_affectation_igv_types')->get();
        $unitTypes = DB::table('sunat_unit_types')->get();
        return Inertia::render('Sales::Documents/CreditNotes', [
            'affectations'  => $affectations,
            'unitTypes'     => $unitTypes,
            'taxes'         => array(
                'igv' => $this->igv,
                'icbper' => $this->icbper
            )
        ]);
    }
    public function tableDocument()
    {
        $sales = (new Sale())->newQuery();

        $isAdmin = Auth::user()->hasRole('admin');

        $sales = $sales->join('people', 'client_id', 'people.id')
            ->join('sale_documents', 'sale_documents.sale_id', 'sales.id')
            ->join('series', 'sale_documents.serie_id', 'series.id')
            ->select(
                'sales.id',
                'sales.client_id',
                'sale_documents.id AS document_id',
                'people.full_name',
                'total',
                'advancement',
                'total_discount',
                'payments',
                'sales.created_at',
                'sales.local_id',
                'sale_documents.invoice_status',
                'sale_documents.invoice_response_description',
                'sale_documents.invoice_response_code',
                'sale_documents.invoice_notes',
                'sale_documents.status',
                'series.description AS serie',
                'sale_documents.number',
                'sale_documents.invoice_correlative',
                'sale_documents.invoice_type_doc',
                'sale_documents.client_number',
                'sale_documents.client_rzn_social',
                'sale_documents.client_address',
                'sale_documents.client_ubigeo_code',
                'sale_documents.client_ubigeo_description',
                'sale_documents.client_phone',
                'sale_documents.client_email',
                'sale_documents.invoice_broadcast_date',
                'sale_documents.invoice_due_date',
                'sale_documents.reason_cancellation'
            )
            ->whereIn('series.document_type_id', [3, 4])
            ->when(!$isAdmin, function ($q) {
                return $q->where('sales.user_id', Auth::id());
            })
            ->with('documents.items')
            ->orderBy('sales.id', 'DESC');
        //dd($sales);
        return DataTables::of($sales)->toJson();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $saleDocumentTypes = SaleDocumentType::whereIn('id', [3, 4])->get();
        $typeCreditNote = DB::table('sunat_note_credit_types')->whereIn('id', ['01', '02'])->get();
        $typeDebitNote = DB::table('sunat_note_debit_types')->get();
        $series = Serie::where('document_type_id', 1)->get();
        $noteSeries = Serie::where('document_type_id', 3)->get();
        $unitTypes = DB::table('sunat_unit_types')->get();

        return Inertia::render('Sales::Documents/CreditNotesCreateForm', [
            'saleDocumentTypes' => $saleDocumentTypes,
            'series' => $series,
            'noteSeries' => $noteSeries,
            'typeCreditNote' => $typeCreditNote,
            'typeDebitNote' => $typeDebitNote,
            'unitTypes' => $unitTypes,
            'taxes' => array(
                'igv' => $this->igv,
                'icbper' => $this->icbper
            )
        ]);
    }

    public function searchInvoice(Request $request)
    {
        $document = SaleDocument::with('items')
            ->where('serie_id', $request->get('serie'))
            ->where('invoice_correlative', $request->get('number'))
            ->first();
        if ($document) {
            $document->items = $document->items->map(function ($item) {
                $descuento = 0;
                if (json_decode($item->json_discounts, true)) {
                    $descuento = json_decode($item->json_discounts, true)[0]['value'];
                }
                $item->editar = false;
                $item->descuento = $descuento;
                return $item;
            });
        }

        if ($document) {
            return response()->json([
                'success' => true,
                'document' => $document
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function validateDocument(Request $request)
    {
        $this->validate(
            $request,
            [
                'note_serie' => 'required',
                'note_reason_cancellation' => 'required|string',
                'note_operation_type' => 'required',
                'document_id' => 'required',
            ],
            [
                'note_serie' => 'Serie es obligatorio',
                'note_reason_cancellation' => 'Descripción es obligatorio',
                'note_operation_type' => 'Tipo es obligatorio',
                'document_id' => 'Documento afectado es obligatorio',
                ///'items.*.total.required' => 'Ingrese total',
            ]
        );

        try {
            if ($request->get('note_type') == 3) {
                $result = $this->store($request);
                return response()->json($result);
            } elseif ($request->get('note_type') == 4) {
                if ($request->get('note_overall_total') > $request->get('document_mto_total')) {
                    $result = $this->store($request);
                    return response()->json($result);
                } else {
                    throw new Exception("El monto total de la nota de débito debe ser mayor al de la factura referenciada.");
                }
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function store($request)
    {

        try {
            $res = DB::transaction(function () use ($request) {
                $serie = Serie::find($request->get('note_serie'));

                ///se convierte el total de la venta a letras
                $numberletters = new NumberLetter();
                $tido = SaleDocumentType::find($request->get('note_type'));
                $fechaFormatoSQL = Carbon::createFromFormat('d/m/Y', $request->get('note_issue_date'))->format('Y-m-d');

                ///totales de la cabecera
                $mto_oper_taxed = 0;
                $mto_igv = 0;
                $total_icbper = 0;
                $porcentage_icbper = 0.20;
                $total_discount = 0;
                $total = 0;

                $document = SaleDocument::create([
                    'sale_id'                       => $request->get('document_sale_id'),
                    'serie_id'                      => $request->get('note_serie'),
                    'number'                        => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
                    'status'                        => true,
                    'client_type_doc'               => $request->get('document_client_type'),
                    'client_number'                 => $request->get('document_client_ruc'),
                    'client_rzn_social'             => $request->get('document_client'),
                    'client_address'                => $request->get('document_client_address'),
                    'client_email'                  => $request->get('document_client_email'),
                    'invoice_ubl_version'           => $this->ubl, ///para notas de credito
                    'note_type_operation_id'        => $request->get('note_operation_type'),
                    'invoice_type_doc'              => $tido->sunat_id,
                    'invoice_serie'                 => $serie->description,
                    'invoice_correlative'           => $serie->number,
                    'invoice_type_currency'         => 'PEN',
                    'invoice_broadcast_date'        => $fechaFormatoSQL,
                    'invoice_due_date'              => $request->get('note_due_date') ?? Carbon::now()->format('Y-m-d'),
                    'invoice_send_date'             => Carbon::now()->format('Y-m-d'),
                    'invoice_legend_code'           => '1000',
                    'invoice_legend_description'    => $numberletters->convertToLetter($request->get('document_mto_total')),
                    'user_id'                       => Auth::id(),
                    'reason_cancellation'           => $request->get('note_reason_cancellation'),
                    'overall_total'                 => $request->get('document_overall_total'),
                    'document_id'                   => $request->get('document_id'),
                ]);

                foreach ($request->get('document_items') as $item) {

                    /// imiciamos las variables para hacer los calculos por item;
                    $percentage_igv = $this->igv;
                    $mto_base_igv = 0;
                    $price_sale = $item['price_sale'];
                    $nfactorIGV = round(($percentage_igv / 100) + 1, 2);
                    $ifactorIGV = round($percentage_igv / 100, 2);
                    $quantity = $item['quantity'];
                    $value_unit = 0;
                    $igv = 0;
                    $total_tax = 0;
                    $icbper = 0;
                    $value_sale = 0;
                    $total_item = 0;
                    $mto_discount = 0;
                    $array_discounts = [];

                    $afe_igv = $item['type_afe_igv'];
                    //dd($item['json_discounts']);
                    if ($item['mto_discount'] > 0) {
                        $mtoDiscount = json_decode($item['json_discounts'], true)[0]['value'];
                    } else {
                        $mtoDiscount = 0;
                    }


                    if ($afe_igv == '10') {
                        //valor unitario presio de venta / 1.IGV para quitarle el igv
                        //se tiene que quitar el igv porque el sistema trabaja con los precios
                        //incluido el igv
                        $value_unit = round($price_sale / $nfactorIGV, 2);
                        //la base para hacer el descuento
                        $base = round($value_unit * $quantity, 2);
                        //el sistema resive un monto fijo como descuento y lo convierte a un porcentaje
                        $factor = (($mtoDiscount * 100) / $price_sale) / 100;
                        //el descuento se aplica por unidad vendida
                        $descuento_monto = $factor * $value_unit * $quantity;
                        //a la base igv le restamos el descuento
                        $mto_base_igv = ($value_unit * $quantity) - $descuento_monto;
                        //una ves restada la vase lo multiplicamos por el 18% vigente para sacar
                        //el valor total igv
                        $igv = ($mto_base_igv * $ifactorIGV);
                        //total del item
                        $total_item = (($value_unit * $quantity) - $descuento_monto) + $igv;
                        //el valor de la venta
                        $value_sale = ($value_unit * $quantity) - $descuento_monto;
                        //si tiene descuento creamos el array de descuento
                        //2023-07-20 el sistema solo trabaja con un descuento
                        if ($mtoDiscount > 0) {
                            //el precio unitario se calcula
                            //(Valor venta + Total Impuestos) / Cantidad
                            $unit_price = round(($value_sale + $igv) / $quantity, 2);
                            $array_discounts[0] = array(
                                'value'     => $mtoDiscount,
                                'type'      => '00',
                                'base'      => round($base, 2),
                                'factor'    => $factor,
                                'monto'     => round($descuento_monto, 2)
                            );
                        } else {
                            //el precio unitario es el mismo
                            $unit_price = $price_sale;
                        }

                        $mto_discount = round($descuento_monto, 2);
                    }
                    if ($afe_igv == '20') { //Exonerated

                    }
                    if ($afe_igv == '30') { //Unaffected

                    }

                    $porcentage_item_icbper = 0;
                    $icbper = 0;

                    if ($item['entity_name_product'] == Product::class) {
                        $product = Product::find($item['product_id']);
                        if ($product->icbper && $product->icbper == 1) {
                            $porcentage_item_icbper = $porcentage_icbper;
                            $icbper = ($quantity * $porcentage_item_icbper);
                        } else {
                            $porcentage_item_icbper = 0;
                            $icbper = 0;
                        }
                    }

                    $total_tax = $igv + $icbper;

                    //se inserta los datos al detalle del documento
                    SaleDocumentItem::create([
                        'document_id'           => $document->id,
                        'product_id'            => $item['product_id'],
                        'cod_product'           => $item['cod_product'],
                        'decription_product'    => $item['decription_product'],
                        'unit_type'             => $item['unit_type'],
                        'quantity'              => $item['quantity'],
                        'mto_base_igv'          => $mto_base_igv,
                        'percentage_igv'        => $this->igv,
                        'igv'                   => $igv,
                        'total_tax'             => $total_tax,
                        'type_afe_igv'          => $item['type_afe_igv'],
                        'icbper'                => $icbper,
                        'factor_icbper'         => $porcentage_item_icbper,
                        'mto_value_sale'        => $value_sale,
                        'mto_value_unit'        => $value_unit,
                        'mto_price_unit'        => $unit_price,
                        'price_sale'            => $price_sale,
                        'mto_total'             => round($unit_price * $item['quantity'], 2),
                        'mto_discount'          => $mto_discount ?? 0,
                        'json_discounts'        => json_encode($array_discounts),
                        'entity_name_product'   => $item['entity_name_product']
                    ]);

                    $mto_igv = $mto_igv + $igv; //total del igv
                    $total_icbper = $total_icbper + $icbper; //total del impuesto a la bolsa plastica
                    $mto_oper_taxed = $mto_oper_taxed + $value_sale; // total operaciones gravadas
                    $total = $total + $total_item; // total de la venta general
                }

                //totales de la cabesera del documento
                $total_taxes = $mto_igv + $total_icbper;
                $subtotal = $total_taxes + $mto_oper_taxed;
                $ttotal = round($total, 1);
                $difference = abs($ttotal - $subtotal);
                $rounding = number_format($difference, 2);

                $document->update([
                    'invoice_mto_oper_taxed'    => $mto_oper_taxed,
                    'invoice_mto_igv'           => $mto_igv,
                    'invoice_icbper'            => $total_icbper,
                    'invoice_total_taxes'       => $total_taxes,
                    'invoice_value_sale'        => $mto_oper_taxed,
                    'invoice_subtotal'          => $subtotal,
                    'invoice_rounding'          => $rounding,
                    'invoice_mto_imp_sale'      => $ttotal,
                    'invoice_sunat_points'      => null,
                    'invoice_status'            => 'Pendiente',
                ]);

                $serie->increment('number', 1);
                return $document;
            });
            $noteResponse = $this->sendSunatDocument($res);
            return $noteResponse;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
            // Devuelve una respuesta de error
        }
    }


    public function sendSunatDocument($document)
    {
        $result = array();
        $type = $document->invoice_type_doc;

        switch ($type) {
            case '07':
                $notaCredito = new NotaCredito();
                $result = $notaCredito->create($document);
                break;
            case '08':
                $notaDebito = new NotaDebito();
                $result = $notaDebito->create($document);
                break;
            case 2:
                echo "i es igual a 2";
                break;
        }


        return [
            'success' => $result['success'],
            'code'  => $result['code'],
            'message'   => $result['message'],
            'notes'   => $result['notes']
        ];
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sales::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
