<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\StudentElectronicTicket;
use App\Helpers\Invoice\Documents\Boleta;
use App\Helpers\NumberLetter;
use App\Models\Parameter;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleDocumentType;
use App\Models\SaleProduct;
use App\Models\Serie;
use Carbon\Carbon;
use Dom\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudentSubscription;

class SendBoletaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    private $ubl;
    private $igv;
    private $top;
    private $icbper;
    private $userId;

    public function __construct($data, $userId)
    {
        $this->data = $data;
        $this->ubl = Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = Parameter::where('parameter_code', 'P000002')->value('value_default');
        $this->icbper = Parameter::where('parameter_code', 'P000004')->value('value_default');
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Aquí puedes generar la boleta antes de enviar
        $person_email = $this->data['student']['person']['email'];
        $person_name = $this->data['student']['person']['full_name'];

        try {
            $resDocument = $this->generateBoleta();

            if ($resDocument['success']) {
                $document = $resDocument['document'];

                $dataFile = $this->generateBoletaPDF($document->id);

                $emailData = [
                    'from_mail' => env('MAIL_FROM_ADDRESS'),
                    'from_name' => env('MAIL_FROM_NAME'),
                    'title' => 'Hola! Llegó tu comprobante electrónico',
                    'for_mail' => $person_email,
                    'for_name' => $person_name,
                    'file_path' => $dataFile['filePath'],
                    'file_name' => $dataFile['fileName']
                ];
                //dd($emailData);
                Mail::to(trim($person_email))->send(new StudentElectronicTicket($emailData));
            }
        } catch (\Exception $e) {
            // Lanzar una excepción con un mensaje descriptivo
            throw new \Exception("Error al generar la boleta: " . $e->getMessage());
        }
    }

    public function generateBoleta()
    {

        $student = $this->data['student'];
        $person = $this->data['student']['person'];
        $course_id = $this->data['course_id'];
        $student_id = $this->data['student_id'];
        $course = AcaCourse::find($course_id);

        if ($course->price && $course->price > 0) {
            //01 descuento a todos
            //02 descuento solo suscriptores
            $pDescuento = 0;
            $price = $course->price;

            if ($course->discount_applies == '01') {
                $pDescuento = $course->discount;
            } else if ($course->discount_applies == '02') {
                $activeSub = AcaStudentSubscription::where('student_id', $student_id)
                    ->where('status', true)
                    ->exists();
                if ($activeSub) {
                    $pDescuento = $course->discount;
                } else {
                    $pDescuento = 0; // O cualquier valor por defecto
                }
            } else {
                $pDescuento = 0; // O cualquier valor por defecto si no aplica
            }


            if (is_null($pDescuento) || $pDescuento <= 0) {
                $descuento = 0;
                $amount = $price; // El precio final es igual al precio original
            } else {
                // Calcular el monto del descuento
                $descuento = ($price * $pDescuento) / 100;
                // Calcular el precio final
                $amount = $price - $descuento;
            }

            $payments = [array("type" => 1, "reference" => null, "amount" => $amount)];

            $saleNote = Sale::create([
                'sale_date' => Carbon::now()->format('Y-m-d'),
                'user_id' => $this->userId ?? Auth::id(),
                'client_id' => $person['id'],
                'local_id' => 1,
                'total' => $amount,
                'advancement' => $amount,
                'total_discount' => $descuento,
                'payments' => json_encode($payments),
                'petty_cash_id' => null,
                'physical' => 1
            ]);

            SaleProduct::create([
                'sale_id' => $saleNote->id,
                'product_id' => $course->id,
                'product' => json_encode($course),
                'saleProduct' => json_encode($course),
                'price' => $course->price,
                'discount' => $course->descuento,
                'quantity' => 1,
                'total' => round($course->price, 2),
                'entity_name_product' => AcaCourse::class
            ]);



            try {
                $res = DB::transaction(function () use ($person, $saleNote, $student, $course) {

                    $localId = 1;
                    $serieid = null;
                    $dtype = 2;
                    $userId = Auth::id();

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
                        'sale_id'                       => $saleNote->id,
                        'serie_id'                      => $serieid,
                        'number'                        => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
                        'status'                        => true,
                        'client_type_doc'               => $person['document_type_id'],
                        'client_number'                 => $person['number'],
                        'client_rzn_social'             => $person['full_name'],
                        'client_address'                => $person['address'],
                        'client_ubigeo_code'            => $person['ubigeo'] ?? null,
                        'client_ubigeo_description'     => $person['ubigeo_description'] ?? null,
                        'client_phone'                  => $person['telephone'],
                        'client_email'                  => $person['email'],
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
                        'invoice_legend_description'    => $numberletters->convertToLetter($saleNote->total),
                        'invoice_status'                => 'registrado',
                        'user_id'                       => $this->userId ?? Auth::id(),
                        'additional_description'        => null,
                        'overall_total'                 => $saleNote->total
                    ]);

                    ///totales de la cabecera
                    $mto_oper_taxed = 0;
                    $mto_igv = 0;
                    $total_icbper = 0;
                    $porcentage_icbper = 0.20;
                    $total_discount = 0;
                    $total = 0;

                    $des = null;

                    $des = $course->description;

                    //actualizamos a la matricula para saber que ya le enviaron su boleta
                    $registration = AcaCapRegistration::where('student_id', $student['id'])
                        ->where('course_id', $course->id)
                        ->first();

                    $registration->update([
                        'document_id' => $document->id,
                        'sale_note_id' => $saleNote->id
                    ]);
                    ////fin de actualizacion a la matricula

                    $percentage_igv = $this->igv;
                    $mto_base_igv = 0;
                    $price_sale = $course->price;
                    $nfactorIGV = round(($percentage_igv / 100) + 1, 2);
                    $ifactorIGV = round($percentage_igv / 100, 2);
                    $quantity = 1;
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
                        'product_id'            => $course->id,
                        'cod_product'           => 'ACA' . $course->id,
                        'decription_product'    => $des,
                        'unit_type'             => 'ZZ',
                        'quantity'              => 1,
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
                        'entity_name_product'   => AcaCourse::class
                    ]);



                    $mto_igv = $mto_igv + $igv; //total del igv
                    $total_icbper = $total_icbper + $icbper; //total del impuesto a la bolsa plastica
                    $mto_oper_taxed = $mto_oper_taxed + $value_sale; // total operaciones gravadas
                    $total = $total + $total_item; // total de la venta general

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

                    return ['document' => $document];
                });

                return [
                    'success' => true,
                    'document' => $res['document'],
                    'message' => 'Boleta generada correctamente'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
                // Devuelve una respuesta de error
            }
        } else {
            return [
                'success' => false,
                'message' => 'El curso es gratuito'
            ];
        }
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

            // Intentar obtener la boleta
            $res = $boleta->getBoletatDomPdf($id, $format);

            // Verificar si se obtuvo un resultado válido
            if (!$res) {
                throw new \Exception("No se pudo generar la boleta.");
            }

            return $res;
        } catch (\Exception $e) {
            // Lanzar una excepción con un mensaje descriptivo
            throw new \Exception("Error al generar la boleta: " . $e->getMessage());
        }
    }
}
