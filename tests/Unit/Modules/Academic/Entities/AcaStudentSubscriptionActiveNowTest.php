<?php

namespace Tests\Unit\Modules\Academic\Entities;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Entities\AcaStudentSubscription;
use Tests\TestCase;

/**
 * Regla compartida de "suscripción vigente hoy".
 *
 * La promesa: el estado manda, salvo que la fecha de fin ya haya pasado; y una
 * suscripción dentro de sus fechas cuenta aunque su estado no se haya
 * actualizado. Así el acceso no depende de que el comando de vencimiento haya
 * corrido.
 */
class AcaStudentSubscriptionActiveNowTest extends TestCase
{
    private const TABLE = 'aca_student_subscriptions';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists(self::TABLE);
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TABLE);

        parent::tearDown();
    }

    public function test_activa_por_estado_sin_fechas(): void
    {
        $this->subscription(['status' => true]);

        $this->assertTrue($this->isActive());
    }

    public function test_activa_por_estado_con_fecha_de_fin_futura(): void
    {
        $this->subscription([
            'status' => true,
            'date_end' => now()->addMonth()->toDateString(),
        ]);

        $this->assertTrue($this->isActive());
    }

    public function test_vencida_pierde_vigencia_aunque_el_estado_siga_activo(): void
    {
        $this->subscription([
            'status' => true,
            'date_start' => now()->subMonths(3)->toDateString(),
            'date_end' => now()->subMonth()->toDateString(),
        ]);

        $this->assertFalse($this->isActive());
    }

    public function test_vigente_por_fechas_cuenta_aunque_el_estado_este_apagado(): void
    {
        $this->subscription([
            'status' => false,
            'date_start' => now()->subMonth()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
        ]);

        $this->assertTrue($this->isActive());
    }

    public function test_sin_estado_ni_fechas_no_cuenta(): void
    {
        $this->subscription(['status' => false]);

        $this->assertFalse($this->isActive());
    }

    public function test_apagada_y_vencida_no_cuenta(): void
    {
        $this->subscription([
            'status' => false,
            'date_start' => now()->subMonths(3)->toDateString(),
            'date_end' => now()->subMonth()->toDateString(),
        ]);

        $this->assertFalse($this->isActive());
    }

    public function test_solo_cuenta_la_suscripcion_del_alumno(): void
    {
        $this->subscription(['status' => true, 'student_id' => 2]);

        $this->assertFalse($this->isActive(1));
        $this->assertTrue($this->isActive(2));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function subscription(array $attributes): void
    {
        DB::table(self::TABLE)->insert(array_merge([
            'student_id' => 1,
            'subscription_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function isActive(int $studentId = 1): bool
    {
        return AcaStudentSubscription::query()
            ->where('student_id', $studentId)
            ->activeNow()
            ->exists();
    }
}
