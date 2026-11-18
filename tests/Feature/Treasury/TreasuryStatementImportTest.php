<?php

namespace Tests\Feature\Treasury;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryReconciliationItem;
use Modules\Treasury\Entities\TreasuryStatementImport;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryReconciliationService;
use Modules\Treasury\Services\TreasuryStatementImportService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Importación de extractos (CSV, Excel y OFX) y consolidado sin detracción.
 */
class TreasuryStatementImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userWith([
            'treasury_dashboard',
            'treasury_conciliacion',
            'treasury_conciliacion_cerrar',
            'treasury_conciliacion_importar',
        ]);

        $this->actingAs($this->user);
    }

    public function test_el_consolidado_no_suma_la_cuenta_de_detraccion(): void
    {
        $cash = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 1000);

        // La cuenta de detracción es única: la crea la migración del módulo.
        $detraction = TreasuryAccount::query()->detraction()->first()
            ?? $this->makeAccount('Detracción', TreasuryAccount::TYPE_DETRACTION, 0);
        $detraction->update(['opening_balance' => 300]);

        app(TreasuryLedgerService::class)->registerIncome($cash, now()->toDateString(), 200, null, null, null, null, 'Cobro');

        $data = app(TreasuryLedgerService::class)->consolidatedBalances();

        $this->assertSame(1200.0, (float) $data['consolidation']['consolidated'], 'El consolidado solo suma cuentas operativas.');
        $this->assertSame(300.0, (float) $data['consolidation']['detraction'], 'La detracción se informa aparte.');
        $this->assertSame(1, $data['consolidation']['detraction_accounts']);
        $this->assertSame($detraction->id, TreasuryAccount::query()->detraction()->first()->id);
    }

    public function test_importa_un_csv_empareja_por_monto_y_fecha_y_crea_partidas_nuevas(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $income = app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            500,
            null,
            null,
            null,
            'OP-001',
            'Cobro del cliente'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->format('d/m/Y') . ';Cobro del cliente;500.00;OP-001',
            now()->addDay()->format('d/m/Y') . ';Comisión de mantenimiento;-80.00;COM-9',
        ]);

        $file = UploadedFile::fake()->createWithContent('extracto.csv', $csv);
        $service = app(TreasuryStatementImportService::class);

        $preview = $service->preview($file, $account);

        $this->assertSame(';', $preview['delimiter']);
        $this->assertSame(0, $preview['mapping']['fecha']);
        $this->assertSame(1, $preview['mapping']['descripcion']);
        $this->assertSame(2, $preview['mapping']['monto']);
        $this->assertSame(3, $preview['mapping']['referencia']);
        $this->assertSame(2, $preview['total']);

        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(2, $result['rows_total']);
        $this->assertSame(1, $result['rows_matched']);
        $this->assertSame(1, $result['rows_created']);
        $this->assertContains($income->id, $result['matched_transaction_ids']);

        $item = TreasuryReconciliationItem::query()->where('source', 'import')->firstOrFail();

        $this->assertSame('expense', $item->type);
        $this->assertSame(80.0, (float) $item->amount);
        $this->assertSame('COM-9', $item->reference);
        $this->assertNotNull($item->statement_import_id);
    }

    public function test_la_vista_previa_http_devuelve_el_mapeo_sugerido(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $csv = "Fecha,Concepto,Importe\n" . now()->format('Y-m-d') . ",Venta,150.00\n";
        $file = UploadedFile::fake()->createWithContent('extracto.csv', $csv);

        $this->post(route('treasury_reconciliation_import_preview'), [
            'file' => $file,
            'treasury_account_id' => $account->id,
        ])
            ->assertOk()
            ->assertJsonPath('mapping.fecha', 0)
            ->assertJsonPath('mapping.descripcion', 1)
            ->assertJsonPath('mapping.monto', 2)
            ->assertJsonStructure(['token', 'headers', 'rows', 'fields']);
    }

    public function test_rechaza_un_formato_no_soportado(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);
        $file = UploadedFile::fake()->createWithContent('extracto.pdf', '%PDF-1.4');

        $this->expectException(TreasuryOperationException::class);

        app(TreasuryStatementImportService::class)->preview($file, $account);
    }

    public function test_la_conciliacion_compara_el_extracto_con_el_libro_en_su_rango_de_fechas(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            500,
            null,
            null,
            null,
            'OP-001',
            'Cobro del cliente'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->format('d/m/Y') . ';Cobro del cliente;500.00;OP-001',
            now()->subDay()->format('d/m/Y') . ';Comisión de mantenimiento;-80.00;COM-9',
            now()->subDays(5)->format('d/m/Y') . ';Transferencia recibida;200.00;TR-5',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);

        $this->assertNotNull($comparison);
        $this->assertCount(3, $comparison['lines']);
        $this->assertSame(1, $comparison['accepted'], 'La línea que coincide en monto y fecha queda aceptada.');
        $this->assertSame(2, $comparison['pending']);
        $this->assertSame(now()->subDays(12)->toDateString(), $comparison['from']);
        $this->assertSame(now()->addDays(7)->toDateString(), $comparison['to']);

        $libro = collect($comparison['book'])->firstWhere('description', 'Cobro del cliente');
        $this->assertTrue($libro['matched'], 'El movimiento del libro aparece vinculado a la línea del extracto.');
    }

    public function test_registra_como_movimiento_una_linea_del_extracto_sin_par(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->subDay()->format('d/m/Y') . ';Comisión de mantenimiento;-80.00;COM-9',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();
        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);

        $this->assertSame('pending', $comparison['lines'][0]['status']);

        $this->post(route('treasury_reconciliation_statement_movement', $import->id), [
            'line_index' => 0,
        ])->assertRedirect();

        $transaction = TreasuryTransaction::query()
            ->where('source', TreasuryTransaction::SOURCE_STATEMENT)
            ->firstOrFail();

        $this->assertSame(80.0, (float) $transaction->amount);
        $this->assertSame(TreasuryTransaction::TYPE_EXPENSE, $transaction->type);
        $this->assertSame(now()->subDay()->toDateString(), $transaction->transaction_date->toDateString());

        // La partida de ajuste se retira: el banco y el libro ya coinciden con el movimiento.
        $this->assertSame(0, TreasuryReconciliationItem::query()->where('statement_import_id', $import->id)->count());

        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);
        $this->assertSame('created', $comparison['lines'][0]['status']);
        $this->assertSame(0, $comparison['pending']);
    }

    public function test_registra_varias_lineas_del_extracto_seleccionadas_a_la_vez(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->subDay()->format('d/m/Y') . ';Comisión de mantenimiento;-80.00;COM-9',
            now()->subDays(2)->format('d/m/Y') . ';Transferencia recibida;200.00;TR-5',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();
        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);

        $this->assertSame(2, $comparison['pending']);

        $response = $this->post(route('treasury_reconciliation_statement_movements', $import->id), [
            'line_indexes' => [0, 1],
        ]);
        $response->assertRedirect();

        // El aviso separa ingresos y egresos: el egreso va con signo negativo.
        $success = (string) session('success');
        $this->assertStringContainsString('1 ingreso(s) por 200.00', $success);
        $this->assertStringContainsString('1 egreso(s) por -80.00', $success);
        $this->assertStringContainsString('Neto: 120.00', $success);

        $transactions = TreasuryTransaction::query()
            ->where('source', TreasuryTransaction::SOURCE_STATEMENT)
            ->orderBy('transaction_date')
            ->get();

        $this->assertCount(2, $transactions);
        $this->assertSame(280.0, (float) $transactions->sum('amount'));
        // Ordenadas por fecha: primero la transferencia recibida (hace 2 días).
        $this->assertSame(TreasuryTransaction::TYPE_INCOME, $transactions[0]->type);
        $this->assertSame(TreasuryTransaction::TYPE_EXPENSE, $transactions[1]->type);

        // Las partidas de ajuste de las dos líneas se retiran.
        $this->assertSame(0, TreasuryReconciliationItem::query()->where('statement_import_id', $import->id)->count());

        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);
        $this->assertSame(0, $comparison['pending']);
        $this->assertSame(2, $comparison['created']);
    }

    public function test_el_registro_multiple_es_todo_o_nada(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->subDay()->format('d/m/Y') . ';Comisión de mantenimiento;-80.00;COM-9',
            now()->subDays(2)->format('d/m/Y') . ';Transferencia recibida;200.00;TR-5',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();

        $this->post(route('treasury_reconciliation_statement_movement', $import->id), [
            'line_index' => 0,
        ])->assertRedirect();

        // La segunda selección incluye una línea ya registrada: no se crea nada.
        $this->post(route('treasury_reconciliation_statement_movements', $import->id), [
            'line_indexes' => [0, 1],
        ])->assertSessionHas('error');

        $this->assertSame(1, TreasuryTransaction::query()
            ->where('source', TreasuryTransaction::SOURCE_STATEMENT)
            ->count());
    }

    public function test_no_empareja_un_ingreso_del_banco_con_un_egreso_del_libro(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            100,
            null,
            null,
            null,
            'OP-1',
            'Cobro del cliente'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->format('d/m/Y') . ';Pago al proveedor;-100.00;OP-1',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        // Mismo monto y misma fecha, pero sentido contrario: no son la misma operación.
        $this->assertSame(0, $result['rows_matched']);
        $this->assertSame(1, $result['rows_created']);
    }

    public function test_un_par_recomendado_se_puede_marcar_como_movimiento_diferente(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $book = app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            300,
            null,
            null,
            null,
            'TX-1',
            'Cobro del día'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->format('d/m/Y') . ';Cobro del día;300.00;TX-1',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(1, $result['rows_matched']);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();

        // El encargado sabe que son dos operaciones distintas del mismo día.
        $this->post(route('treasury_reconciliation_statement_unlink', [$import->id, 0]))->assertRedirect();

        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);

        $this->assertSame(1, $comparison['pending'], 'La línea vuelve a quedar sin par.');
        $this->assertSame(0, $comparison['accepted']);
        $this->assertSame(1, count($comparison['book_unmatched']), 'El movimiento del libro sigue existiendo y sin par.');
        $this->assertSame(300.0, (float) $book->fresh()->amount, 'El movimiento del libro no se modifica.');

        // Ahora la línea del banco se registra como movimiento nuevo.
        $this->post(route('treasury_reconciliation_statement_movement', $import->id), [
            'line_index' => 0,
        ])->assertRedirect();

        $this->assertSame(2, TreasuryTransaction::query()->active()->count());
        $this->assertSame(600.0, (float) TreasuryTransaction::query()->active()->sum('amount'));
    }

    public function test_conserva_la_hora_del_extracto_al_registrar_el_movimiento(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia',
            now()->format('d/m/Y') . ' 14:35;Comisión de mantenimiento;-25.00;COM-1',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();

        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);
        $this->assertSame('14:35:00', $comparison['lines'][0]['time']);

        $this->post(route('treasury_reconciliation_statement_movement', $import->id), [
            'line_index' => 0,
        ])->assertRedirect();

        $transaction = TreasuryTransaction::query()
            ->where('source', TreasuryTransaction::SOURCE_STATEMENT)
            ->firstOrFail();

        $this->assertSame('14:35:00', $transaction->transaction_time);
    }

    public function test_el_numero_de_operacion_empareja_aunque_la_fecha_difiera(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->subDays(4)->toDateString(),
            500,
            null,
            null,
            null,
            'REF-1',
            'Cobro del cliente',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $this->user->id,
            true,
            null,
            null,
            null,
            null,
            '0012345'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia;N° Operación',
            now()->format('d/m/Y') . ';Cobro del cliente;500.00;REF-1;0012345',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(1, $result['rows_matched'], 'El N° de operación manda sobre la diferencia de fecha.');
        $this->assertSame(0, $result['rows_created']);
        $this->assertSame('0012345', $result['lines'][0]['operation_number']);
    }

    public function test_numeros_de_operacion_distintos_no_son_el_mismo_movimiento(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            300,
            null,
            null,
            null,
            'REF-1',
            'Cobro del cliente',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $this->user->id,
            true,
            null,
            null,
            null,
            null,
            '0001111'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia;N° Operación',
            now()->format('d/m/Y') . ';Cobro del cliente;300.00;REF-1;0002222',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(0, $result['rows_matched'], 'Dos N° de operación distintos son dos movimientos.');
        $this->assertSame(1, $result['rows_created']);
    }

    public function test_el_movimiento_creado_desde_el_extracto_conserva_el_numero_de_operacion(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia;N° Operación',
            now()->format('d/m/Y') . ';Comisión de mantenimiento;-25.00;REF-9;00456',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();

        $comparison = app(TreasuryReconciliationService::class)->statementComparison($account);
        $this->assertSame('00456', $comparison['lines'][0]['operation_number']);

        $this->post(route('treasury_reconciliation_statement_movement', $import->id), [
            'line_index' => 0,
        ])->assertRedirect();

        $transaction = TreasuryTransaction::query()
            ->where('source', TreasuryTransaction::SOURCE_STATEMENT)
            ->firstOrFail();

        $this->assertSame('00456', $transaction->operation_number);
        $this->assertSame('REF-9', $transaction->reference);
    }

    public function test_el_numero_de_operacion_del_extracto_completa_el_movimiento_ya_emparejado(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        // Movimiento registrado antes de que existiera el N° de operación: el
        // número del banco quedó como referencia y el campo está vacío.
        $transaction = app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            500,
            null,
            null,
            null,
            '01015573',
            'Cobro del cliente'
        );

        $this->assertNull($transaction->operation_number);

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia;N° Operación',
            now()->format('d/m/Y') . ';Cobro del cliente;500.00;01015573;01015573',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(1, $result['rows_matched']);
        $this->assertSame(
            '01015573',
            $transaction->fresh()->operation_number,
            'El número del extracto se guarda en el movimiento que ya estaba emparejado.'
        );
    }

    public function test_el_numero_de_operacion_respeta_la_referencia_cuando_hay_dos_lineas_iguales(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $ledger = app(TreasuryLedgerService::class);

        // Dos egresos idénticos del mismo día: el par puede armarse cruzado,
        // pero cada número pertenece al movimiento cuya referencia lo trae.
        $first = $ledger->registerExpense($account, now()->toDateString(), 106, null, null, null, '02943205', 'TRAN.CEL.BM.');
        $second = $ledger->registerExpense($account, now()->toDateString(), 106, null, null, null, '02932139', 'TRAN.CEL.BM.');

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;N° Operación',
            now()->format('d/m/Y') . ';TRAN.CEL.BM.;-106.00;02932139',
            now()->format('d/m/Y') . ';TRAN.CEL.BM.;-106.00;02943205',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(2, $result['rows_matched']);
        $this->assertSame('02943205', $first->fresh()->operation_number);
        $this->assertSame('02932139', $second->fresh()->operation_number);
    }

    public function test_el_mismo_numero_de_operacion_empareja_aunque_el_monto_difiera(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryLedgerService::class)->registerIncome(
            $account,
            now()->toDateString(),
            500,
            null,
            null,
            null,
            'REF-1',
            'Cobro del cliente',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $this->user->id,
            true,
            null,
            null,
            null,
            null,
            '0012345'
        );

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia;N° Operación',
            now()->format('d/m/Y') . ';Cobro del cliente;480.00;REF-1;0012345',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(1, $result['rows_matched'], 'El mismo N° de operación es el mismo movimiento.');
        $this->assertSame(0, $result['rows_created']);
    }

    public function test_no_se_puede_registrar_dos_movimientos_con_el_mismo_numero_de_operacion_en_la_cuenta(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 1000);
        $ledger = app(TreasuryLedgerService::class);

        $ledger->registerExpense(
            $account,
            now()->toDateString(),
            100,
            null,
            null,
            null,
            'REF-1',
            'Pago al proveedor',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $this->user->id,
            true,
            null,
            null,
            null,
            null,
            '0007777'
        );

        $this->expectException(TreasuryOperationException::class);
        $this->expectExceptionMessage('ya está registrado en esta cuenta');

        $ledger->registerExpense(
            $account,
            now()->toDateString(),
            100,
            null,
            null,
            null,
            'REF-2',
            'El mismo pago otra vez',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $this->user->id,
            true,
            null,
            null,
            null,
            null,
            '0007777'
        );
    }

    public function test_no_se_puede_crear_desde_el_extracto_un_movimiento_con_numero_de_operacion_ya_registrado(): void
    {
        $account = $this->makeAccount('Caja', TreasuryAccount::TYPE_CASH, 0);

        $existing = app(TreasuryLedgerService::class)->registerExpense(
            $account,
            now()->toDateString(),
            100,
            null,
            null,
            null,
            'REF-1',
            'Pago al proveedor',
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $this->user->id,
            true,
            null,
            null,
            null,
            null,
            '00456'
        );

        // Ya conciliado: no entra en los candidatos del emparejamiento, pero su
        // N° de operación sigue existiendo en la cuenta.
        $existing->forceFill(['reconciled_at' => now()])->save();

        $csv = implode("\n", [
            'Fecha;Descripción;Monto;Referencia;N° Operación',
            now()->format('d/m/Y') . ';Pago al proveedor;100.00;REF-1;00456',
        ]);

        $service = app(TreasuryStatementImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('extracto.csv', $csv), $account);
        $result = $service->confirm([
            'token' => $preview['token'],
            'file_name' => 'extracto.csv',
            'until' => now()->toDateString(),
            'mapping' => $preview['mapping'],
        ], $account, $this->user->id);

        $this->assertSame(0, $result['rows_matched']);

        $import = TreasuryStatementImport::query()->latest('id')->firstOrFail();

        $this->post(route('treasury_reconciliation_statement_movement', $import->id), [
            'line_index' => 0,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, TreasuryTransaction::query()->active()->count());
    }

    private function makeAccount(string $label, string $type, float $opening): TreasuryAccount
    {
        return TreasuryAccount::create([
            'type' => $type,
            'label' => $label,
            'currency_type_id' => 'PEN',
            'opening_balance' => $opening,
            'status' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
