<?php

namespace Modules\Integrationhub\Support;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaStudent;
use Modules\Onlineshop\Entities\OnliSale;

/**
 * JSON que se envía a n8n (endpoint n8n_post_negociacion) cuando una persona
 * que ya tiene cuenta compra un curso (gratis o de pago). A diferencia del
 * evento de perfil, una compra web no tiene un registro de negociación: el
 * bloque `negociacion` se arma con los datos de la venta, pero mantiene las
 * mismas claves que Modules\Commercial\Support\NegotiationWebhookPayload para
 * que n8n reciba siempre el mismo contrato y solo ramifique por `evento`.
 */
class PurchaseWebhookPayload
{
    public const EVENT = 'compra_cuenta_existente';

    /**
     * @return array<string, mixed>|null null si la venta o su persona no existen.
     */
    public static function forSale(int $onliSaleId): ?array
    {
        $sale = OnliSale::with(['person', 'details.course', 'details.subscription', 'details.item'])
            ->find($onliSaleId);

        if (! $sale || ! $sale->person) {
            return null;
        }

        $person = $sale->person;
        $user = User::where('person_id', $person->id)->first();
        $student = AcaStudent::where('person_id', $person->id)->first();
        $saleNote = $sale->nota_sale_id ? Sale::find($sale->nota_sale_id) : null;
        $document = $saleNote?->document;

        return [
            'evento' => self::EVENT,
            'fecha' => Carbon::now()->toIso8601String(),
            'origen' => 'onli_sales',
            'negociacion' => self::negotiation($sale, $document),
            'persona' => WebhookPersonPayload::person($person),
            'usuario' => [
                'id' => $user?->id,
                'email' => $user?->email,
            ],
            'estudiante' => [
                'id' => $student?->id,
                'codigo' => $student?->student_code,
                'carrera' => WebhookPersonPayload::career($student),
            ],
            'comprobante' => self::receipt($saleNote, $document),
            'items' => self::items($sale),
        ];
    }

    /**
     * Bloque `negociacion` con la misma forma que el de una negociación
     * aprobada, pero alimentado por la venta en línea.
     *
     * @return array<string, mixed>
     */
    private static function negotiation(OnliSale $sale, ?SaleDocument $document): array
    {
        return [
            'id' => $sale->id,
            'titulo' => null,
            'descripcion' => null,
            'moneda' => 'PEN',
            'total' => (float) ($sale->total ?? 0),
            // La venta usa 'not' para pago único o un número de cuotas.
            'tipo_pago' => ($sale->installments === null || $sale->installments === 'not') ? 'single' : 'installments',
            'monto_inicial' => null,
            'cuotas' => [],
            'estado' => $sale->response_status,
            'canal_contacto' => null,
            'detalle_contacto' => null,
            'metodo_pago' => $sale->response_payment_method_id,
            'sale_id' => $sale->nota_sale_id,
            'sale_document_id' => $document?->id,
            'creado_por' => null,
            'creado_por_id' => null,
            'creado_por_email' => null,
            'aprobado_por' => null,
            'aprobado_por_id' => null,
            'aprobado_por_email' => null,
            'aprobado_en' => $sale->response_date_approved,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function receipt(?Sale $saleNote, ?SaleDocument $document): array
    {
        return [
            'tipo' => $saleNote ? ($saleNote->invoice_type == 1 ? 'factura' : 'boleta') : null,
            'ruc' => $saleNote?->invoice_ruc,
            'razon_social' => $saleNote?->invoice_razon_social,
            'direccion' => $saleNote?->invoice_direccion,
            'distrito' => null,
            'provincia' => null,
            'departamento' => null,
            'serie' => $document?->invoice_serie,
            'correlativo' => $document?->invoice_correlative,
            'numero' => $document?->invoice_document_name,
            'estado' => $document?->invoice_status,
            'pdf' => WebhookPersonPayload::publicInvoiceUrl($document?->invoice_pdf),
            'total' => $document?->overall_total !== null ? (float) $document->overall_total : null,
        ];
    }

    /**
     * Solo los productos de esta compra (onli_sale_details), que es literalmente
     * lo que la persona acaba de comprar.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function items(OnliSale $sale): array
    {
        return $sale->details
            ->map(function ($detail) {
                $tipo = 'producto';
                $titulo = null;

                if ($detail->course) {
                    $tipo = 'curso';
                    $titulo = $detail->course->description ?? $detail->course->title ?? null;
                } elseif ($detail->subscription) {
                    $tipo = 'suscripcion';
                    $titulo = $detail->subscription->description ?? $detail->subscription->title ?? null;
                } elseif ($detail->item) {
                    $tipo = 'producto';
                    $titulo = $detail->item->name ?? null;
                }

                return [
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'producto_id' => $detail->item_id,
                    'precio' => (float) $detail->price,
                    'entidad' => $detail->entitie,
                ];
            })
            ->values()
            ->all();
    }
}
