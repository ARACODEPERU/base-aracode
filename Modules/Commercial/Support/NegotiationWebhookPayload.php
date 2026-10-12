<?php

namespace Modules\Commercial\Support;

use App\Models\Person;
use App\Models\SaleDocument;
use App\Models\User;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaStudent;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Integrationhub\Support\PersonWebhookPayload;

/**
 * JSON que el ultimo paso del proceso de negociacion envia a n8n
 * (endpoint n8n_post_negociacion). Es el unico lugar donde se define cada clave del
 * contrato, asi que cualquier cambio de forma del payload se hace aqui y no en el
 * controlador que lo dispara.
 */
class NegotiationWebhookPayload
{
    /**
     * Arma el payload de la negociacion aprobada: la negociacion, el cliente, su
     * usuario y ficha de alumno, el comprobante y los items acordados.
     *
     * @return array<string, mixed>
     */
    public static function forNegotiation(CommercialNegotiation $negotiation, Person $person): array
    {
        $negotiation->loadMissing(['items', 'creator', 'verifier', 'invoice']);

        $user = User::where('person_id', $person->id)->first();
        $student = AcaStudent::where('person_id', $person->id)->first();
        $invoice = $negotiation->invoice;
        $document = $negotiation->sale_document_id ? SaleDocument::find($negotiation->sale_document_id) : null;

        return [
            'evento' => 'negociacion_aprobada',
            'fecha' => Carbon::now()->toIso8601String(),
            'origen' => 'commercial_negociations',
            'negociacion' => [
                'id' => $negotiation->id,
                'titulo' => $negotiation->title,
                'descripcion' => $negotiation->body,
                'moneda' => $negotiation->currency,
                'total' => (float) $negotiation->total_price,
                'tipo_pago' => $negotiation->payment_type,
                'monto_inicial' => $negotiation->initial_amount !== null ? (float) $negotiation->initial_amount : null,
                'cuotas' => collect($negotiation->schedule ?? [])->map(fn ($row, $index) => [
                    'numero' => $index + 1,
                    'due_date' => $row['due_date'] ?? null,
                    'amount' => isset($row['amount']) && $row['amount'] !== null ? (float) $row['amount'] : null,
                ])->values()->toArray(),
                'estado' => $negotiation->status,
                'canal_contacto' => $negotiation->contact_channel_label ?: $negotiation->contact_channel,
                'detalle_contacto' => $negotiation->contact_detail,
                'metodo_pago' => $negotiation->payment_method,
                'sale_id' => $negotiation->sale_id,
                'sale_document_id' => $negotiation->sale_document_id,
                'creado_por' => $negotiation->creator?->name,
                'creado_por_id' => $negotiation->creator?->id,
                'creado_por_email' => $negotiation->creator?->email,
                'aprobado_por' => $negotiation->verifier?->name,
                'aprobado_por_id' => $negotiation->verifier?->id,
                'aprobado_por_email' => $negotiation->verifier?->email,
                'aprobado_en' => $negotiation->verified_at?->toIso8601String(),
            ],
            // Los tres bloques los define PersonWebhookPayload: es el mismo contrato
            // que usa Academic, asi que no hay dos listas de campos que se separen.
            'persona' => PersonWebhookPayload::person($person, $negotiation->client_data ?? []),
            'usuario' => PersonWebhookPayload::user($user),
            'estudiante' => PersonWebhookPayload::student($student),
            'comprobante' => [
                'tipo' => $invoice?->invoice_type,
                'ruc' => $invoice?->ruc,
                'razon_social' => $invoice?->razon_social,
                'direccion' => $invoice?->direccion,
                'distrito' => $invoice?->distrito,
                'provincia' => $invoice?->provincia,
                'departamento' => $invoice?->departamento,
                'serie' => $document?->invoice_serie,
                'correlativo' => $document?->invoice_correlative,
                'numero' => $document?->invoice_document_name,
                'estado' => $document?->invoice_status,
                'pdf' => self::publicInvoiceUrl($document?->invoice_pdf),
                'total' => $document?->overall_total !== null ? (float) $document->overall_total : null,
            ],
            'items' => $negotiation->items->map(fn ($item) => [
                'tipo' => $item->item_type,
                'titulo' => $item->title,
                'producto_id' => $item->item_id,
                'precio' => (float) $item->price,
                'entidad' => $item->entity_name_product,
            ])->values()->toArray(),
        ];
    }

    /**
     * Convierte la ruta absoluta con la que se guarda el PDF del comprobante
     * (public/storage/invoice/20613668323-03-B001-247.pdf) en su URL publica
     * (.../storage/invoice/20613668323-03-B001-247.pdf), que es la que un
     * sistema externo como n8n puede descargar.
     */
    private static function publicInvoiceUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Si ya viene una URL (documentos antiguos) se devuelve tal cual.
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $file = basename(str_replace('\\', '/', $path));

        return rtrim(config('app.url') ?: url('/'), '/').'/storage/invoice/'.$file;
    }
}
