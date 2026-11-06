<?php

namespace Modules\Sales\Entities;

use App\Models\SaleDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuiaRemision extends Model
{
    use SoftDeletes;

    protected $table = 'guias_remision';

    public const STATUS_REGISTRADO = 'registrado';

    public const STATUS_ENVIADO = 'enviado';

    public const STATUS_ACEPTADO = 'aceptado';

    public const STATUS_RECHAZADO = 'rechazado';

    public const STATUS_CONTINGENCIA = 'contingencia';

    /** Motivos de traslado del catálogo SUNAT (catálogo 20). */
    public const MOTIVOS_TRASLADO = [
        '01' => 'Venta',
        '02' => 'Compra',
        '04' => 'Traslado entre establecimientos de la misma empresa',
        '09' => 'Importación',
        '13' => 'Otros',
        '14' => 'Venta sujeta a confirmación del comprador',
        '17' => 'Traslado de bienes para transformación',
        '18' => 'Traslado emisor itinerante de comprobantes de pago',
        '19' => 'Recojo de bienes transformados',
    ];

    /** Modalidad de traslado: 01 transporte público, 02 transporte privado. */
    public const MODALIDADES = [
        '01' => 'Transporte público',
        '02' => 'Transporte privado',
    ];

    protected $fillable = [
        'tipo_doc',
        'serie',
        'correlativo',
        'fecha_emision',
        'cod_traslado',
        'des_traslado',
        'mod_traslado',
        'peso_total',
        'und_peso_total',
        'num_bultos',
        'indicadores',
        'sustento_peso',
        'partida_ubigeo',
        'partida_direccion',
        'partida_cod_local',
        'partida_ruc',
        'llegada_ubigeo',
        'llegada_direccion',
        'llegada_cod_local',
        'llegada_ruc',
        'destinatario_tipo_doc',
        'destinatario_num_doc',
        'destinatario_razon_social',
        'destinatario_email',
        'tercero_tipo_doc',
        'tercero_num_doc',
        'tercero_razon_social',
        'comprador_tipo_doc',
        'comprador_num_doc',
        'comprador_razon_social',
        'transportista_tipo_doc',
        'transportista_num_doc',
        'transportista_razon_social',
        'transportista_nro_mtc',
        'vehiculo_placa',
        'vehiculo_nro_circulacion',
        'vehiculo_cod_emisor',
        'vehiculo_nro_autorizacion',
        'conductor_tipo_doc',
        'conductor_num_doc',
        'conductor_nombres',
        'conductor_apellidos',
        'conductor_licencia',
        'sale_id',
        'sale_document_id',
        'doc_relacionado_tipo',
        'doc_relacionado_numero',
        'observacion',
        'status',
        'sunat_send_status',
        'ticket',
        'cdr',
        'xml',
        'hash',
        'response_code',
        'response_description',
        'notes',
        'user_id',
        'anio',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'indicadores' => 'array',
        'peso_total' => 'float',
        'num_bultos' => 'integer',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(GuiaRemisionDetalle::class, 'guia_remision_id')->orderBy('orden');
    }

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'sale_document_id');
    }

    public function motivoTraslado(): string
    {
        return $this->des_traslado ?: (self::MOTIVOS_TRASLADO[$this->cod_traslado] ?? 'Otros');
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACEPTADO;
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, [
            self::STATUS_REGISTRADO,
            self::STATUS_RECHAZADO,
            self::STATUS_CONTINGENCIA,
        ], true) && empty($this->ticket);
    }
}
