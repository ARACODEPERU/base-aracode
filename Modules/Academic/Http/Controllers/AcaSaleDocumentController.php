<?php

namespace Modules\Academic\Http\Controllers;

use App\Helpers\NumberLetter;
use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleDocumentType;
use App\Models\SaleProduct;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Onlineshop\Entities\OnliSaleDetail;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\StudentElectronicTicket;
use App\Helpers\Invoice\Documents\Boleta;
use App\Helpers\Invoice\Documents\Factura;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Jobs\SendBoletaJob;

class AcaSaleDocumentController extends Controller
{

    private $ubl;
    private $igv;
    private $top;
    private $icbper;

    public function __construct()
    {
        $this->ubl = Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = Parameter::where('parameter_code', 'P000002')->value('value_default');
        $this->icbper = Parameter::where('parameter_code', 'P000004')->value('value_default');
    }

    public function generateBoleta(Request $request)
    {

        $pedido = $request->get('pedido');

        try {
            $res = DB::transaction(function () use ($pedido) {

                $venta = $pedido['venta'];
                $localId = $pedido['local'];
                $serieid = $pedido['serie'];
                $dtype = $pedido['documenttypeId'];
                $userId = $pedido['userId'];
                $enline = $pedido['enline'];
                $onlisale_id = $venta['id'];

                $saleId = $venta['nota_sale_id'];

                $sale = Sale::find($saleId);
                $person = Person::find($sale->client_id);

                if ($sale->invoice_type == 1) {
                    $dtype =  $sale->invoice_type;

                    $client_type_doc = 6;
                    $client_number = $sale->invoice_ruc;
                    $client_rzn_social  = $sale->invoice_razon_social;
                    $client_address = $sale->invoice_direccion;
                    $client_ubigeo_code = $sale->invoice_ubigeo;
                    $client_ubigeo_description = $sale->invoice_ruc;
                } else {
                    $client_type_doc = $person->document_type_id;
                    $client_number = $person->number;
                    $client_rzn_social  = $person->full_name;
                    $client_address = $person->address;
                    $client_ubigeo_code = $person->ubigeo ?? null;
                    $client_ubigeo_description = $person->ubigeo_description ?? null;
                }


                $student = AcaStudent::where('person_id', $person->id)->first();
                ///obtenemos la serie elejida para hacer la venta
                ///para traer tambien su numero correlativo
                $serie = null;
                if ($serieid) {
                    $serie = Serie::find($serieid);
                } else {
                    $serie = Serie::where('local_id', $localId)
                        ->where('document_type_id', $dtype)
                        ->first();
                    $serieid = $serie->id;
                }

                if (!$serie) {
                    throw new \Exception("No se encontró la serie con los parámetros proporcionados.");
                }
                ///se convierte el total de la venta a letras
                $numberletters = new NumberLetter();
                $tido = SaleDocumentType::find($dtype);

                ///creamos el documento de la venta para enviar a sunat

                $document = SaleDocument::create([
                    'sale_id'                       => $sale->id,
                    'serie_id'                      => $serieid,
                    'number'                        => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
                    'status'                        => true,
                    'client_type_doc'               => $client_type_doc,
                    'client_number'                 => $client_number,
                    'client_rzn_social'             => $client_rzn_social,
                    'client_address'                => $client_address,
                    'client_ubigeo_code'            => $client_ubigeo_code,
                    'client_ubigeo_description'     => $client_ubigeo_description,
                    'client_phone'                  => $person->telephone,
                    'client_email'                  => $person->email,
                    'invoice_ubl_version'           => $this->ubl,
                    'invoice_type_operation'        => '0101',
                    'invoice_type_doc'              => $tido->sunat_id,
                    'invoice_serie'                 => $serie->description,
                    'invoice_correlative'           => $serie->number,
                    'invoice_type_currency'         => 'PEN',
                    'invoice_broadcast_date'        => Carbon::now()->format('Y-m-d'),
                    'invoice_due_date'              => Carbon::now()->format('Y-m-d'),
                    'invoice_send_date'             => Carbon::now()->format('Y-m-d'),
                    'invoice_legend_code'           => '1000',
                    'invoice_legend_description'    => $numberletters->convertToLetter($sale->total),
                    'invoice_status'                => 'registrado',
                    'user_id'                       => $userId,
                    'additional_description'        => null,
                    'overall_total'                 => $sale->total
                ]);

                ///obtenemos los productos o servicios para insertar en los
                ///detalles de la venta y el documento
                $products = SaleProduct::where('sale_id', $sale->id)->get();

                ///totales de la cabecera
                $mto_oper_taxed = 0;
                $mto_igv = 0;
                $total_icbper = 0;
                $porcentage_icbper = 0.20;
                $total_discount = 0;
                $total = 0;

                foreach ($products as $product) {
                    $curPro = null;
                    $des = null;


                    if ($product->entity_name_product == AcaCourse::class) {
                        $curPro  = AcaCourse::where('id', $product->product_id)
                            ->first();
                        $origin = 'ACA';
                        $des = $curPro->description;

                        //actualizamos a la matricula para saber que ya le enviaron su boleta
                        $registration = AcaCapRegistration::where('student_id', $student->id)
                            ->where('course_id', $curPro->id)
                            ->first();

                        $registration->update([
                            'document_id' => $document->id
                        ]);
                        ////fin de actualizacion a la matricula

                    } else if ($product->entity_name_product == AcaSubscriptionType::class) {
                        $curPro  = AcaSubscriptionType::where('id', $product->product_id)->first();
                        $origin = 'ACA';
                        $des =  $curPro->title . ' - ' . $curPro->description;
                    } else if ($product->entity_name_product == Product::class) {
                        $curPro  = Product::where('id', $product->product_id)->first();
                        $origin = 'PRO';
                        $des = $curPro->description;
                    } else if ($product->entity_name_product == AcaCourse::class) {
                        $curPro  = AcaCourse::where('id', $product->product_id)->first();
                        $origin = 'ACA';
                        $des = $curPro->description;
                    }


                    $percentage_igv = $this->igv;
                    $mto_base_igv = 0;
                    $price_sale = $product->price;
                    $nfactorIGV = round(($percentage_igv / 100) + 1, 2);
                    $ifactorIGV = round($percentage_igv / 100, 2);
                    $quantity = $product->quantity;
                    $value_unit = 0;
                    $igv = 0;
                    $total_tax = 0;
                    $icbper = 0;
                    $value_sale = 0;
                    $total_item = 0;
                    $mto_discount = 0;
                    $array_discounts = [];

                    $pafe_igv = '10';
                    $pdiscount = 0;
                    $picbper = 1;

                    if ($pafe_igv == '10') {
                        //valor unitario presio de venta / 1.IGV para quitarle el igv
                        //se tiene que quitar el igv porque el sistema trabaja con los precios
                        // Asegurar que los valores clave no sean nulos
                        $pdiscount = $pdiscount ?? 0;
                        //$price_sale = $price_sale ?: 0.01; // Evita división por cero si por alguna razón viene 0

                        // Precio unitario sin IGV
                        $value_unit = round($price_sale / $nfactorIGV, 2);

                        // Base para aplicar descuento
                        $base = round($value_unit * $quantity, 2);

                        // Calcular factor de descuento (evita división por 0)
                        $factor = ($price_sale > 0 && $pdiscount > 0)
                            ? round((($pdiscount * 100) / $price_sale) / 100, 4)
                            : 0;

                        // Descuento en monto total
                        $descuento_monto = round($factor * $value_unit * $quantity, 2);

                        // Base imponible menos descuento
                        $mto_base_igv = round(($value_unit * $quantity) - $descuento_monto, 2);

                        // IGV sobre la base restante
                        $igv = round($mto_base_igv * $ifactorIGV, 2);

                        // Total del ítem (con IGV y descuento aplicado)
                        $total_item = round((($value_unit * $quantity) - $descuento_monto) + $igv, 2);

                        // Valor de venta neto (sin IGV, con descuento)
                        $value_sale = round(($value_unit * $quantity) - $descuento_monto, 2);
                        //si tiene descuento creamos el array de descuento
                        //2023-07-20 el sistema solo trabaja con un descuento
                        if ($pdiscount > 0) {
                            //el precio unitario se calcula
                            //(Valor venta + Total Impuestos) / Cantidad
                            $unit_price = round(($value_sale + $igv) / $quantity, 2);
                            $array_discounts[0] = array(
                                'value'     => $pdiscount,
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
                    if ($pafe_igv == '20') { //Exonerated

                    }
                    if ($pafe_igv == '30') { //Unaffected

                    }

                    if ($picbper == 1) {
                        $porcentage_item_icbper = $porcentage_icbper;
                        $icbper = ($quantity * $porcentage_item_icbper);
                    } else {
                        $porcentage_item_icbper = 0;
                        $icbper = 0;
                    }
                    $total_tax = $igv + $icbper;


                    //se inserta los datos al detalle del documento
                    SaleDocumentItem::create([
                        'document_id'           => $document->id,
                        'product_id'            => $curPro->id,
                        'cod_product'           => $origin == 'ACA' ? 'ACA' . $curPro->id : $curPro->interne,
                        'decription_product'    => $des,
                        'unit_type'             => 'ZZ',
                        'quantity'              => $product->quantity,
                        'mto_base_igv'          => $mto_base_igv,
                        'percentage_igv'        => $this->igv,
                        'igv'                   => $igv,
                        'total_tax'             => $total_tax,
                        'type_afe_igv'          => $pafe_igv,
                        'icbper'                => $icbper,
                        'factor_icbper'         => $porcentage_item_icbper,
                        'mto_value_sale'        => $value_sale,
                        'mto_value_unit'        => $value_unit,
                        'mto_price_unit'        => $unit_price,
                        'price_sale'            => $price_sale,
                        'mto_total'             => round($total_item, 2),
                        'mto_discount'          => $mto_discount ?? 0,
                        'json_discounts'        => json_encode($array_discounts),
                        'entity_name_product'   => $product->entity_name_product
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

                return ['document' => $document, 'onlisale_id' => $onlisale_id];
            });

            return response()->json([
                'success' => true,
                'document' => $res['document'],
                'onlisaleId' => $res['onlisale_id'],
                'message' => 'Boleta generada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            // Devuelve una respuesta de error
        }
    }

    public function sendEmailBoleta(Request $request)
    {

        $person_email = $request->get('person_email');
        $person_name = $request->get('person_name');
        $document_id = $request->get('document_id');
        $onlisale_id = $request->get('onlisaleId');

        $success = false;
        $correosMessage = [];

        $dataFile = $this->generateBoletaPDF($document_id);


        $data = [
            'from_mail' => env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME'),
            'title' => 'Hola! Llegó tu comprobante electrónico',
            'for_mail' => $person_email,
            'for_name' => $person_name,
            'file_path' => $dataFile["pdf"]['filePath'],
            'file_name' => $dataFile["pdf"]['fileName'],
            'xml_file_path' => $dataFile["xml"]['filePath'],
            'xml_file_name' => $dataFile["xml"]['fileName'],
        ];

        try {

            //dd($data);
            Mail::to(trim($person_email))->send(new StudentElectronicTicket($data));

            $success = true;

            if ($onlisale_id) {
                $onlisale = OnliSale::findOrFail($onlisale_id);

                if ($onlisale) {
                    $onlisale->update([
                        'email_sent' => true
                    ]);
                }
            }

            $correosMessage = [
                'email' => $person_email,
                'message' => 'Correo enviado correctamente'
            ];
        } catch (\Exception $e) {
            $success = false;
            $correosMessage = [
                'email' => $person_email,
                'message' => $e->getMessage() // Guarda el mensaje de error
            ];
        }

        // Devuelve la respuesta con totales y detalles de errores
        return response()->json([
            'success' => $success,
            'correosMessage' => $correosMessage
        ]);
    }

    public function generateBoletaPDF($id)
    {
        // Validar que el ID no esté vacío o sea nulo
        if (empty($id)) {
            throw new \InvalidArgumentException("El ID no puede estar vacío.");
        }

        try {
            $format = 'A4';
            $boleta = new Boleta();
            $factura = new Factura();
            // Intentar obtener la boleta
            $resb = $boleta->getBoletatDomPdf($id, $format); //metodo para generar pdf
            $resF = $factura->getFacturaXML($id); // para generar el xml
            // Verificar si se obtuvo un resultado válido
            if (!$resb) {
                throw new \Exception("No se pudo generar la boleta.");
            }

            return array(
                "pdf" => $resb,
                "xml" => $resF
            );
        } catch (\Exception $e) {
            // Lanzar una excepción con un mensaje descriptivo
            throw new \Exception("Error al generar la boleta: " . $e->getMessage());
        }
    }

    public function generateAndSendInvoices(Request $request)
    {

        $registration = $request->get('registration');
        $userId = $request->get('userId');
        $error = [];
        $procesado = [];
        $success = true;
        // foreach ($registrations as $registration) {
        if ($registration['checkbox']) {
            //dd($registration);
            try {
                // Puedes validar campos requeridos aquí
                $person = $registration['student']['person'];
                $regis = AcaCapRegistration::find($registration['id']);
                if (!$regis->document_id && !$regis->sale_note_id) {

                    if (!$person['email']) {
                        $error = [
                            'full_name' => $person['full_name'],
                            'razon' => 'Estudiante sin correo electrónico',
                        ];
                        //continue;
                    }

                    // Genera la boleta si aplica aquí (si no lo haces dentro del job)
                    // generarBoleta($student);

                    // Enviar usando colas
                    dispatch(new SendBoletaJob($registration, $userId));
                    $success = true;
                    $procesado = [
                        'full_name' => $person['full_name'],
                        'razon' => 'Boleta creada y enviada correctamente',
                    ];
                } else {
                    $error = [
                        'full_name' => $person['full_name'],
                        'razon' => 'Ya se le envió su boleta',
                    ];
                    $success = false;
                }
            } catch (\Throwable $e) {
                $error = [
                    'full_name' => $person['full_name'],
                    'razon' => $e->getMessage(),
                ];
                $success = false;
            }
        }


        return response()->json([
            'success' => $success,
            'procesado' => $procesado,
            'error' => $error,
            'status' => 'Fin del proceso'
        ]);
    }
}
