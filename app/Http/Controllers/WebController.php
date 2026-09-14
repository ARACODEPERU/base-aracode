<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaCertificateParameter;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Intervention\Image\Facades\Image;
use Intervention\Image\Font;
use Illuminate\Support\Facades\Response;
use App\Helpers\Invoice\QrCodeGenerator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Modules\Onlineshop\Entities\OnliSale;
use App\Mail\ConfirmPurchaseMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\SaleProduct;
use Illuminate\Support\Facades\Auth;


class WebController extends Controller
{

    public $certificates_param;
    public function testimage($student_id, $course_id)
    {
        $register = AcaCapRegistration::where('student_id', $student_id)->where('course_id', $course_id)->first();

        if ($register) {
            if ($register->certificate_date != null) {

                $student = AcaStudent::with('person')->find($student_id);
                //dd($student->person->full_name);
                $this->certificates_param = AcaCertificateParameter::where('course_id', $course_id)->first();

                // Verificar si se obtuvieron resultados en la consulta si no se obtiene se crea certificado por defecto con ID 1
                if (!$this->certificates_param) $this->certificates_param = AcaCertificateParameter::find(1);
                $this->certificates_param->Course = AcaCourse::find($course_id);


                //dd($this->certificates_param);
                // create Image from file
                $img = Image::make($this->certificates_param->certificate_img);
                $fecha = $register->certificate_date; //Esta fecha debe obtenerse del registro de la matricula del estudiante al curso respectivo donde se obtiene la fecha de entrega del certificado si es null entonces no tiene certificado

                //las fuentes deben estar en la carpeta public/fonts y en la base de datos debe registrarse el nombre de la fuente y su extensión
                //recomiendo usar fuentes de google fonts porque son gratis y puedes descargarlas

                $img->text($student->person->full_name, $this->certificates_param->position_names_x, $this->certificates_param->position_names_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_names);
                    $font->size($this->certificates_param->font_size_names);
                    $font->color('#0d0603');
                    $font->align($this->certificates_param->font_align_names);
                    $font->valign($this->certificates_param->font_vertical_align_names);
                    $font->angle(0);
                });



                $img->text("Lima, " . $fecha, $this->certificates_param->position_date_x, $this->certificates_param->position_date_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_date);
                    $font->size($this->certificates_param->font_size_date);
                    $font->color('#0d0603');
                    $font->align($this->certificates_param->font_align_date);
                    $font->valign($this->certificates_param->font_vertical_align_date);
                    $font->angle(0);
                });
                $max_width = $this->certificates_param->max_width_title;
                $img->text($this->wrapText($this->certificates_param->Course->description, $max_width), $this->certificates_param->position_title_x, $this->certificates_param->position_title_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_title);
                    $font->size($this->certificates_param->font_size_title);
                    $font->color('#0d0603');
                    $font->align($this->certificates_param->font_align_title);
                    $font->valign($this->certificates_param->font_vertical_align_title);
                    $font->angle(0);
                });

                //descripcion del certificado
                $max_width = $this->certificates_param->max_width_description;
                $img->text($this->wrapText($this->certificates_param->Course->certificate_description, $max_width, $this->certificates_param->interspace_description), $this->certificates_param->position_description_x, $this->certificates_param->position_description_y, function ($font) {
                    $font->file($this->certificates_param->fontfamily_description);
                    $font->size($this->certificates_param->font_size_description);
                    $font->color('#0d0603');
                    $font->align($this->certificates_param->font_align_description);
                    $font->valign($this->certificates_param->font_vertical_align_description);
                    $font->angle(0);
                });

                // //QR GENERATOR
                $generator = new QrCodeGenerator(300);
                $dir = public_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp_qr';
                $cadenaqr = env('APP_URL') . '/test-image/' . $student_id . '/' . $course_id;

                $qr_path = $generator->generateQR($cadenaqr, $dir, Str::random(10) . '.png', 8, 2);
                $qr = Image::make($qr_path);
                //$qr = Image::make('https://borealtech.com/wp-content/uploads/2018/10/codigo-qr-1024x1024-1.jpg');
                $qr->fit($this->certificates_param->size_qr, $this->certificates_param->size_qr); //ajustar tamaño del qr
                $img->insert($qr, $this->certificates_param->font_align_qr, $this->certificates_param->position_qr_x, $this->certificates_param->position_qr_y); //insertar qr en la imagen


                // Ejemplo de Redimensionar la imagen manteniendo la proporción para avatares y similares
                // Establecer el ancho máximo y la altura máxima deseados
                $maxWidth = 1550;
                $maxHeight = 1550;
                $img->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });



                // Obtener el contenido binario de la imagen
                $imageContent = $img->encode('png');


                // Generar la respuesta HTTP con la imagen
                $response = Response::make($imageContent);

                // Establecer el tipo de contenido de la respuesta como imagen PNG
                $response->header('Content-Type', 'image/png');

                //ELIMINAR el EL ARCHIVO QR generado
                if (File::exists($qr_path)) File::delete($qr_path);

                //Retornar la respuesta
                return $response;
            } else {
                echo 'El estudiante fue registrado en el ' . $register->Course->description . 'pero no se le ha entregado el certificado aún';
            }
        } else {
            echo "No se encontraron registros";
        }
    }

    public function wrapText($text, $maxWidth, $lineSpacing = 2.3)
    {
        // Envolver el texto
        $wrappedText = wordwrap($text, $maxWidth, PHP_EOL, true);

        // Dividir el texto envuelto en líneas
        $lines = explode(PHP_EOL, $wrappedText);

        // Calcular la longitud máxima de las líneas envueltas
        $maxLineLength = max(array_map('strlen', $lines));

        // Centrar horizontalmente las líneas
        $centeredLines = array_map(function ($line) use ($maxLineLength) {
            $spacesToAdd = max(0, ($maxLineLength - strlen($line)) / 2);
            $centeredLine = str_repeat(' ', $spacesToAdd) . $line;
            return $centeredLine;
        }, $lines);

        // Agregar espacio entre líneas
        $spacing = str_repeat(PHP_EOL, $lineSpacing); // Crear el espacio entre líneas
        $centeredText = implode($spacing, $centeredLines); // Unir las líneas con el espacio

        return $centeredText;
    }

    public function processPayment(Request $request, $id, $student_id)
    {
        $data = $request->input('cardFormData');
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
        $products = $data['products'];
        $data = (array)$data;


        $client = new PaymentClient();
        $sale = OnliSale::find($id);


        if ($sale->response_status == 'approved') {
            return response()->json(['error' => 'el pedido ya fue procesado, ya no puede volver a pagar'], 412);
        } else {
            try {

                $payment = $client->create([
                    "token" => $data['token'],
                    "issuer_id" => $data['issuer_id'],
                    "payment_method_id" => $data['payment_method_id'],
                    "transaction_amount" => (float) $data['transaction_amount'],
                    "installments" => $data['installments'],
                    "payer" => $data['payer'],
                ]);



                if ($payment->status == 'approved') {

                    $sale->email = $data['payer']['email'];
                    $sale->total = $data['transaction_amount'];
                    $sale->identification_type = $data['payer']['identification']['type'];
                    $sale->identification_number = $data['payer']['identification']['number'];
                    $sale->response_status = $payment->status;
                    $sale->response_id = null; //$data['collection_id']; de donde sale collection_id???
                    $sale->response_date_approved = Carbon::now()->format('Y-m-d');
                    $sale->response_payer = json_encode($request->all());
                    $sale->response_payment_method_id = null; //$data['payment_type'];
                    $sale->mercado_payment_id = $payment->id;
                    $sale->mercado_payment = json_encode($payment);


                    // creando nota de venta

                    $personInvoice = $request->input('personInvoice');
                    $personInvoice = json_decode($personInvoice);
                    $payments = [array("type" => 6, "reference" => null, "amount" => $sale->total)];
                    $sale_note = Sale::create([
                        'sale_date' => Carbon::now()->format('Y-m-d'),
                        'user_id' => Auth::id(),
                        'client_id' => Auth::user()->person_id,
                        'local_id' => 1,
                        'total' => $data['transaction_amount'],
                        'advancement' => $data['transaction_amount'],
                        'total_discount' => 0,
                        'payments' => json_encode($payments),
                        'petty_cash_id' => null,
                        'physical' => 1,
                        'invoice_razon_social' => $personInvoice ? $personInvoice->razonSocial : null,
                        'invoice_ruc' => $personInvoice ? $personInvoice->ruc : null,
                        'invoice_direccion' => null,
                        'invoice_ubigeo' => null,
                        'invoice_type' => $personInvoice ? $personInvoice->document_type : null
                    ]);

                    $sale->nota_sale_id = $sale_note->id;


                    foreach ($products as $product) {
                        $this->matricular_curso($product, $product['student_id']);
                        //llenando los detalles de la nota de venta
                        $xpro = AcaCourse::find($product['item_id']);
                        SaleProduct::create([
                            'sale_id' => $sale_note->id,
                            'product_id' => $xpro->id,
                            'product' => json_encode($xpro),
                            'saleProduct' => json_encode($product),
                            'price' => $product['price'],
                            'discount' => 0,
                            'quantity' => 1,
                            'total' => round($product['price'], 2),
                            'entity_name_product' => AcaCourse::class
                        ]);
                    }
                    $sale->save();

                    ///enviar correo
                    Mail::to($sale->email)
                        ->send(new ConfirmPurchaseMail(OnliSale::with('details.item')->where('id', $id)->first()));


                    return response()->json([
                        'status' => $payment->status,
                        'message' => $payment->status_detail,
                        'url' => route('web_thanks', $sale->id)
                    ]);
                } else {

                    $sale->delete();
                    return response()->json([
                        'status' => $payment->status,
                        'message' => $payment->status_detail,
                        'url' => route('web_carrito')
                    ]);
                }
            } catch (\MercadoPago\Exceptions\MPApiException $e) {
                // Manejar la excepción
                $response = $e->getApiResponse();
                $content  = $response->getContent();

                $message = $content['message'];
                return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
            }
        }
    }

    public function graciasCompra($id)
    {
        $products[0] = null;
        $sale = OnliSale::where('id', $id)->with('details.item')->first();
        return view('pages/gracias-compra', [
            'products' => $products,
            'sale' => $sale
        ]);
    }

    public function errorCompra($id)
    {
        dd($id);
    }

    private function matricular_curso($producto, $student_id)
    {
        $course_id = $producto['item_id'];

        $registration = AcaCapRegistration::create([
            'student_id' => $student_id,
            'course_id' => $course_id,
            'status' => true,
            'modality_id' => 3,
            'unlimited' => true
        ]);
    }
}
