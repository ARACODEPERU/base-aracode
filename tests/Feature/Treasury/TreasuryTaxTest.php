<?php

namespace Tests\Feature\Treasury;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryForecastService;
use Modules\Treasury\Services\TreasuryTaxService;
use Tests\TestCase;

/**
 * IGV de boletas y facturas: el impuesto se calcula sobre lo emitido (también
 * al crédito) y su pago se registra como un egreso del periodo.
 */
class TreasuryTaxTest extends TestCase
{
    use RefreshDatabase;

    private TreasuryTaxService $service;

    private TreasuryAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->service = app(TreasuryTaxService::class);
        $this->account = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Caja de prueba',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);
    }

    public function test_el_igv_se_calcula_sobre_boletas_y_facturas_emitidas_incluso_al_credito(): void
    {
        $this->document(['invoice_mto_igv' => 18, 'overall_total' => 118, 'forma_pago' => 'Credito']);
        $this->document([
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B001',
            'invoice_mto_igv' => 36,
            'overall_total' => 236,
            'forma_pago' => 'Contado',
        ]);

        // Estos no deben contar: anulada por estado y rechazada por SUNAT.
        $this->document(['status' => 3, 'invoice_status' => 'Anulada', 'invoice_mto_igv' => 90]);
        $this->document(['invoice_status' => 'Rechazada', 'invoice_mto_igv' => 90]);

        $data = $this->service->monthSummary(now()->year, now()->month);

        $this->assertSame(2, $data['documents_count']);
        $this->assertSame(54.0, $data['sales_igv']);
        $this->assertSame(54.0, $data['net_igv']);
        $this->assertSame(0.0, $data['paid']);

        // El comprobante al crédito entra al cálculo: la emisión manda, no el cobro.
        $credito = collect($data['documents'])->firstWhere('credit', true);
        $this->assertNotNull($credito);
        $this->assertSame(18.0, $credito['igv']);
    }

    public function test_las_notas_de_credito_restan_el_igv_del_periodo(): void
    {
        $this->document(['invoice_mto_igv' => 18, 'overall_total' => 118]);
        $this->document([
            'invoice_type_doc' => '07',
            'invoice_serie' => 'FC01',
            'invoice_mto_igv' => 5.4,
            'overall_total' => 35.4,
            'invoice_status' => 'Aceptada',
        ]);

        $data = $this->service->monthSummary(now()->year, now()->month);

        $this->assertSame(1, $data['documents_count']);
        $this->assertSame(5.4, $data['notes_igv']);
        $this->assertSame(12.6, $data['net_igv']);
    }

    public function test_el_pago_de_igv_es_un_egreso_y_no_se_registra_dos_veces(): void
    {
        $periodo = now()->format('Y-m');

        $pago = $this->service->registerPayment($this->account, $periodo, 54, now()->toDateString(), 'Declaración 621');

        $this->assertSame(TreasuryTransaction::TYPE_EXPENSE, $pago->type);
        $this->assertSame(TreasuryTaxService::ORIGIN_IGV, $pago->origin_type);
        $this->assertSame(54.0, (float) $pago->amount);
        $this->assertSame('IGV por pagar', $pago->category?->name);
        $this->assertSame(54.0, $this->service->paidAmount($periodo));

        $this->expectException(TreasuryOperationException::class);
        $this->service->registerPayment($this->account, $periodo, 54, now()->toDateString());
    }

    public function test_la_prevision_proyecta_el_igv_del_periodo_sin_pagar(): void
    {
        // Emitido este mes: vence el día configurado del mes siguiente.
        $this->document(['invoice_mto_igv' => 18, 'overall_total' => 118]);

        $forecast = app(TreasuryForecastService::class)->build('PEN', 60);

        $items = collect($forecast['rows'])->flatMap(fn ($row) => $row['items']);
        $igv = $items->firstWhere('type', 'igv');

        $this->assertNotNull($igv, 'La previsión debe incluir el IGV por pagar.');
        $this->assertSame(18.0, $igv['amount']);

        // Al pagarlo, deja de proyectarse.
        $this->service->registerPayment($this->account, now()->format('Y-m'), 18, now()->toDateString());

        $forecast = app(TreasuryForecastService::class)->build('PEN', 60);
        $items = collect($forecast['rows'])->flatMap(fn ($row) => $row['items']);

        $this->assertNull($items->firstWhere('type', 'igv'));
    }

    public function test_un_comprobante_en_dolares_no_se_mezcla_con_el_igv_en_soles(): void
    {
        $this->document(['invoice_type_currency' => 'USD', 'invoice_mto_igv' => 10, 'overall_total' => 110]);
        $this->document(['invoice_mto_igv' => 18, 'overall_total' => 118]);

        $this->assertSame(18.0, $this->service->monthSummary(now()->year, now()->month, 'PEN')['net_igv']);
        $this->assertSame(10.0, $this->service->monthSummary(now()->year, now()->month, 'USD')['net_igv']);
    }

    /**
     * Crea un comprobante de venta con los datos mínimos del cálculo de IGV.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function document(array $overrides = []): SaleDocument
    {
        $sale = Sale::create([
            'user_id' => auth()->id(),
            'client_id' => 1,
            'local_id' => 1,
            'total' => 118,
            'advancement' => 0,
            'total_discount' => 0,
            'payments' => null,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
        ]);

        return SaleDocument::create(array_merge([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '1',
            'invoice_broadcast_date' => now()->toDateString(),
            'invoice_type_currency' => 'PEN',
            'invoice_mto_igv' => 18,
            'overall_total' => 118,
            'invoice_status' => 'Aceptada',
            'status' => 1,
            'forma_pago' => 'Contado',
        ], $overrides));
    }
}
