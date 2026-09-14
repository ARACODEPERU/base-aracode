<?php

namespace Modules\Onlineshop\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Onlineshop\Entities\OnliSale;

class ProcessCompra implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $onliSaleId;
    public string $origen;

    /**
     * @param int    $onliSaleId  ID de la venta en onli_sales
     * @param string $origen      Origen de la compra (carrito_web, carrito_autenticado, "Administrador encargado: Nombre")
     */
    public function __construct(int $onliSaleId, string $origen = 'carrito_web')
    {
        $this->onliSaleId = $onliSaleId;
        $this->origen = $origen;
    }

    public function handle(): void
    {
        try {
            $hub = app(IntegrationhubController::class);

            $sale = OnliSale::with(['person', 'details.course', 'details.subscription', 'details.item'])
                ->findOrFail($this->onliSaleId);

            $person = $sale->person;

            // ── Persona ──
            $persona = [
                'id'               => $person?->id,
                'nombre_completo'  => $sale->clie_full_name ?: ($person?->full_name ?? null),
                'email'            => $sale->email ?: ($person?->email ?? null),
                'telefono'         => $person?->telephone ?? null,
                'tipo_documento'   => $sale->identification_type,
                'numero_documento' => $sale->identification_number,
            ];

            // ── Venta ──
            $esGratuito = in_array((float) ($sale->total ?? 0), [0, 0.0], true)
                || $sale->response_status_detail === 'free_checkout';

            $venta = [
                'id'                => $sale->id,
                'sale_note_id'      => $sale->nota_sale_id,
                'total'             => (float) ($sale->total ?? 0),
                'estado_pago'       => $sale->response_status,
                'metodo_pago'       => $sale->response_payment_method_id,
                'es_gratuito'       => $esGratuito,
                'cuotas'            => $sale->installments === 'yes' ? true : ($sale->installments !== 'not' ? (int) $sale->installments : 1),
                'fecha_aprobacion'  => $sale->response_date_approved,
                'payment_id_mp'     => $sale->mercado_payment_id ?? null,
            ];

            // ── Productos ──
            $productos = $sale->details->map(function ($detail) {
                $tipo = 'producto';
                $nombre = null;

                if ($detail->course) {
                    $tipo = 'curso';
                    $nombre = $detail->course->description ?? $detail->course->title ?? null;
                } elseif ($detail->subscription) {
                    $tipo = 'suscripcion';
                    $nombre = $detail->subscription->description ?? $detail->subscription->title ?? null;
                } elseif ($detail->item) {
                    $tipo = 'producto';
                    $nombre = $detail->item->name ?? null;
                }

                return [
                    'nombre'   => $nombre,
                    'precio'   => (float) $detail->price,
                    'cantidad' => (int) ($detail->quantity ?? 1),
                    'tipo'     => $tipo,
                    'entidad'  => $detail->entitie,
                ];
            })->toArray();

            // ── Factura (si existe nota_sale_id) ──
            $factura = null;

            if ($sale->nota_sale_id) {
                $saleNote = \App\Models\Sale::find($sale->nota_sale_id);

                if ($saleNote) {
                    $factura = [
                        'tipo'      => $saleNote->invoice_type == 1 ? 'factura' : 'boleta',
                        'nombre'    => $saleNote->invoice_razon_social,
                        'documento' => $saleNote->invoice_ruc,
                        'ruc'       => $saleNote->invoice_type == 1 ? $saleNote->invoice_ruc : null,
                        'direccion' => $saleNote->invoice_direccion,
                    ];
                }
            }

            // ── Tracking ──
            $tracking = [
                'utm_source'     => $sale->utm_source,
                'utm_medium'     => $sale->utm_medium,
                'utm_campaign'   => $sale->utm_campaign,
                'utm_term'       => $sale->utm_term,
                'utm_content'    => $sale->utm_content,
                'utm_id'         => $sale->utm_id,
                'fbclid'         => $sale->fbclid,
                'gclid'          => $sale->gclid,
                'referer'        => $sale->referer,
                'landing_url'    => $sale->landing_url,
                'traffic_source' => $sale->traffic_source,
            ];

            // ── Payload completo ──
            $payload = [
                'evento'   => 'compra_completada',
                'fecha'    => Carbon::now()->toIso8601String(),
                'origen'   => $this->origen,
                'persona'  => $persona,
                'venta'    => $venta,
                'productos' => $productos,
                'factura'  => $factura,
                'tracking' => $tracking,
            ];

            $hub->runEndpoint('n8n_post_compra', [], ['body' => $payload], true);
        } catch (\Throwable $e) {
            IntegrationError::create([
                'message' => 'ProcessCompra (sale_id=' . $this->onliSaleId . '): ' . $e->getMessage(),
                'source'  => 'ProcessCompra',
            ]);
        }
    }
}
