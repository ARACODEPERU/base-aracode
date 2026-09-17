<?php

namespace Modules\Commercial\Entities;

use App\Models\CompanyBilletera;
use App\Models\Person;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommercialNegotiation extends Model
{
    use HasFactory;

    protected $table = 'commercial_negotiations';

    protected $fillable = [
        'token',
        'title',
        'body',
        'total_price',
        'currency',
        'payment_type',
        'initial_amount',
        'schedule',
        'single_payment_days',
        'contact_channel',
        'contact_detail',
        'email',
        'payment_method',
        'payment_link',
        'mercado_payment_id',
        'mercado_payment_status',
        'mercado_payment_data',
        'status',
        'link_days',
        'link_expires_at',
        'client_id',
        'client_data',
        'voucher_path',
        'sale_id',
        'sale_document_id',
        'email_sent_at',
        'process_progress',
        'rejected_reason',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'schedule' => 'array',
        'client_data' => 'array',
        'mercado_payment_data' => 'array',
        'verified_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'process_progress' => 'array',
        'link_expires_at' => 'datetime',
    ];

    protected $appends = ['contact_channel_label'];

    /**
     * Opciones del campo "Fuente de Contacto" (antes "Canal de contacto").
     * Los canales antiguos se mantienen en el mapa para que los registros previos
     * sigan mostrando una etiqueta legible.
     */
    public static function contactChannelOptions(): array
    {
        return [
            ['value' => 'invitado_cpa', 'label' => 'Invitado CPA'],
            ['value' => 'masivo_api', 'label' => 'Masivo API'],
            ['value' => 'organico', 'label' => 'Orgánico'],
            ['value' => 'personal_cpa', 'label' => 'Personal CPA'],
            ['value' => 'referido', 'label' => 'Referido'],
            ['value' => 'reserva', 'label' => 'Reserva'],
            ['value' => 'web_cpa', 'label' => 'Web CPA'],
            ['value' => 'webinar', 'label' => 'Webinar'],
        ];
    }

    public static function contactChannelLabels(): array
    {
        return array_merge(
            array_column(self::contactChannelOptions(), 'label', 'value'),
            [
                'telefono' => 'Telefono',
                'whatsapp' => 'WhatsApp',
                'instagram' => 'Instagram',
                'facebook_messenger' => 'Facebook Messenger',
                'facebook' => 'Facebook',
                'otro' => 'Otro',
            ]
        );
    }

    public function getContactChannelLabelAttribute(): string
    {
        if (! $this->contact_channel) {
            return '';
        }

        return self::contactChannelLabels()[$this->contact_channel] ?? $this->contact_channel;
    }

    /**
     * Etiqueta legible del medio de pago, para correos y documentos.
     * Se incluyen los valores antiguos ('yape') para que los registros previos
     * sigan mostrando un nombre entendible.
     */
    public static function paymentMethodLabels(): array
    {
        return [
            'billetera_digital' => 'Billetera digital',
            'yape' => 'Yape',
            'mercadopago' => 'Mercado Pago',
            'transferencia' => 'Transferencia bancaria',
            'enlace' => 'Enlace de pago',
        ];
    }

    public static function paymentMethodLabel(?string $value): string
    {
        if (! $value) {
            return '';
        }

        return self::paymentMethodLabels()[$value] ?? $value;
    }

    public function items()
    {
        return $this->hasMany(CommercialNegotiationItem::class, 'negotiation_id');
    }

    public function client()
    {
        return $this->belongsTo(Person::class, 'client_id');
    }

    public function invoice()
    {
        return $this->hasOne(CommercialNegotiationInvoice::class, 'negotiation_id');
    }

    public function saleDocument()
    {
        return $this->belongsTo(SaleDocument::class, 'sale_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Billeteras digitales de la empresa seleccionadas para esta negociacion.
     */
    public function companyBilleteras(): BelongsToMany
    {
        return $this->belongsToMany(CompanyBilletera::class, 'commercial_negotiation_company_billetera', 'negotiation_id', 'company_billetera_id')
            ->withTimestamps();
    }
}
