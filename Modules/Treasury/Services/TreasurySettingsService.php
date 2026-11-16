<?php

namespace Modules\Treasury\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Treasury\Entities\TreasurySetting;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Configuración crítica del módulo de Tesorería.
 *
 * - `enabled`: si está apagado, ninguna pantalla ni operación del módulo
 *   responde (salvo esta configuración).
 * - `starts_on`: fecha de corte. Los movimientos anteriores no cuentan para
 *   saldos, libro, proyecciones ni reportes.
 *
 * Alcance decidido con el negocio: el corte NO filtra conciliaciones ni
 * periodos ya cerrados (son actas contables) y NO borra la auditoría. El saldo
 * inicial de cada cuenta tampoco se reinicia: si se quiere arrancar en cero,
 * hay que ajustarlo al valor real a la fecha de inicio.
 *
 * Todo cambio se audita con el usuario que lo hizo.
 */
class TreasurySettingsService
{
    private ?TreasurySetting $settings = null;

    public function __construct(private TreasuryAuditService $audit)
    {
    }

    /**
     * Configuración vigente (se crea activa si falta).
     */
    public function current(): TreasurySetting
    {
        if ($this->settings) {
            return $this->settings;
        }

        $this->settings = TreasurySetting::query()->orderBy('id')->first();

        if (! $this->settings) {
            $this->settings = TreasurySetting::create([
                'enabled' => true,
                'starts_on' => null,
            ]);
        }

        return $this->settings;
    }

    /**
     * ¿Tesorería está activa? Si la tabla no existe (instalación a medias) se
     * asume activa para no bloquear el módulo.
     */
    public function enabled(): bool
    {
        if (! Schema::hasTable('treasury_settings')) {
            return true;
        }

        return (bool) $this->current()->enabled;
    }

    /**
     * Fecha de corte en formato Y-m-d, o null si no hay corte.
     */
    public function startsOn(): ?string
    {
        if (! Schema::hasTable('treasury_settings')) {
            return null;
        }

        return $this->current()->starts_on?->toDateString();
    }

    /**
     * Recorta una consulta al periodo en que Tesorería cuenta.
     *
     * @template T of Builder|Relation
     *
     * @param  T  $query
     * @return T
     */
    public function applyCutoff($query, string $column = 'transaction_date')
    {
        $startsOn = $this->startsOn();

        if ($startsOn) {
            $query->whereDate($column, '>=', $startsOn);
        }

        return $query;
    }

    /**
     * Resumen para la pantalla de configuración y los avisos del módulo.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $settings = $this->current();

        return [
            'enabled' => (bool) $settings->enabled,
            'starts_on' => $settings->starts_on?->toDateString(),
            'updated_at' => $settings->updated_at?->toDateTimeString(),
            'updated_by' => $settings->updater?->name,
        ];
    }

    /**
     * Activa o desactiva el módulo y fija su fecha de inicio. Crítico.
     */
    public function update(bool $enabled, ?string $startsOn, ?int $userId = null): TreasurySetting
    {
        $settings = $this->current();

        $fecha = $startsOn ? Carbon::parse($startsOn)->toDateString() : null;

        // Una fecha futura dejaría el módulo sin datos hasta ese día: es válido
        // para programar el arranque, pero se avisa en la pantalla.
        if ($fecha && Carbon::parse($fecha)->isAfter(Carbon::today()->addYear())) {
            throw new TreasuryOperationException('La fecha de inicio no puede estar a más de un año de hoy.');
        }

        $old = [
            'enabled' => (bool) $settings->enabled,
            'starts_on' => $settings->starts_on?->toDateString(),
        ];

        $settings->fill([
            'enabled' => $enabled,
            'starts_on' => $fecha,
            'updated_by' => $userId,
        ])->save();

        $this->settings = $settings;

        $this->audit->log('treasury_settings', $settings->id, 'updated', $old, [
            'enabled' => (bool) $settings->enabled,
            'starts_on' => $settings->starts_on?->toDateString(),
        ], [
            'critical' => true,
            'note' => 'Configuración de puesta en marcha de Tesorería',
        ]);

        return $settings;
    }
}
