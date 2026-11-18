<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\LocalSale;
use Modules\Socialevents\Entities\EvenLocalArea;
use Modules\Socialevents\Entities\EvenLocalAreaSchedule;
use Modules\Socialevents\Entities\EvenLocalRentalRate;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Alquiler de campos deportivos: horario por día de la semana y torneos con monto fijo.
 * Las reglas de horario y torneo solo aplican a áreas de tipo sports_field.
 */
class SportsFieldRentalTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $organizador;

    private LocalSale $local;

    private EvenLocalArea $campo;

    private EvenLocalRentalRate $tarifaCampo;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo no deportivo: en modo deportivo el middleware bloquea la ruta alquiler-local (404).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '0'],
        );

        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de campos',
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
        ]);

        $this->organizador = $this->crearOrganizador();

        // El alquiler referencia local_sales (LocalSale), no even_locals.
        $this->local = LocalSale::create([
            'description' => 'Complejo deportivo',
            'address' => 'Av. Principal 123',
        ]);

        $this->campo = EvenLocalArea::create([
            'local_id' => $this->local->id,
            'name' => 'Campo 1',
            'area_type' => EvenLocalArea::TYPE_SPORTS_FIELD,
            'capacity' => 22,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Horario de la semana: lunes a viernes de 7:00 a 12:00 (weekday 1..5).
        foreach ([1, 2, 3, 4, 5] as $dia) {
            EvenLocalAreaSchedule::create([
                'area_id' => $this->campo->id,
                'day_of_week' => $dia,
                'open_time' => '07:00',
                'close_time' => '12:00',
            ]);
        }

        // Complejo deportivo usado como sede por defecto del torneo (solo guarda el nombre).
        $this->crearComplejo('Complejo Los Olivos');

        $this->tarifaCampo = EvenLocalRentalRate::create([
            'local_id' => $this->local->id,
            'area_id' => $this->campo->id,
            'name' => 'Tarifa campo',
            'hourly_rate' => 100,
            'is_active' => true,
        ]);
    }

    private function crearOrganizador(): User
    {
        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);
        foreach (['even_alquiler_local_listado', 'even_alquiler_local_nuevo', 'even_alquiler_local_editar'] as $nombre) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['socialevent_organization_id' => $this->organization->id]);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /** Línea de alquiler por hora (un área, un día). Los montos se recalculan en el servidor. */
    private function linea(string $fecha, string $inicio, string $fin, ?int $areaId = null, ?int $tarifaId = null): array
    {
        $horas = (strtotime("$fecha $fin") - strtotime("$fecha $inicio")) / 3600;
        $base = $horas * 100;

        return [
            'area_id' => $areaId ?? $this->campo->id,
            'rental_rate_id' => $tarifaId ?? $this->tarifaCampo->id,
            'event_date' => $fecha,
            'event_end_date' => $fecha,
            'start_time' => $inicio,
            'end_time' => $fin,
            'total_hours' => $horas,
            'hourly_rate' => 100,
            'base_amount' => $base,
            'subtotal' => $base,
            'extras' => [],
        ];
    }

    /** Cabecera de reserva. El total se toma de las líneas enviadas. */
    private function payload(array $areas, float $total, array $extra = []): array
    {
        return array_merge([
            'local_id' => $this->local->id,
            'customer_id' => $this->crearCliente()->id,
            'event_type' => 'party',
            'beer_provided_by' => 'none',
            'paid_amount' => round($total * 0.2, 2),
            'total_price' => $total,
            'areas' => $areas,
        ], $extra);
    }

    private function crearCliente()
    {
        return \App\Models\Person::create([
            'full_name' => 'Cliente de prueba',
            'short_name' => 'Cliente',
            'number' => (string) fake()->unique()->numerify('########'),
            'is_client' => true,
        ]);
    }

    public function test_reserva_hora_dentro_del_horario_del_campo_se_guarda(): void
    {
        // Lunes 2026-11-23 de 8:00 a 10:00: dentro de 7:00-12:00.
        $payload = $this->payload([$this->linea('2026-11-23', '08:00', '10:00')], 200);

        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $payload)
            ->assertRedirect(route('even_alquiler_local_index'));

        $this->assertDatabaseHas('even_local_rentals', ['local_id' => $this->local->id, 'total_price' => 200]);
    }

    public function test_reserva_fuera_del_horario_del_campo_es_rechazada(): void
    {
        // Lunes 2026-11-23 de 13:00 a 15:00: fuera de 7:00-12:00.
        $payload = $this->payload([$this->linea('2026-11-23', '13:00', '15:00')], 200);

        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['areas.0.start_time']);

        $this->assertDatabaseCount('even_local_rentals', 0);
    }

    public function test_reserva_en_dia_sin_horario_es_rechazada(): void
    {
        // Domingo 2026-11-22: el campo no tiene horario configurado ese día.
        $payload = $this->payload([$this->linea('2026-11-22', '08:00', '09:00')], 100);

        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['areas.0.start_time']);
    }

    public function test_reserva_que_choca_con_otra_en_la_misma_area_es_rechazada(): void
    {
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payload([$this->linea('2026-11-23', '08:00', '10:00')], 200))
            ->assertRedirect(route('even_alquiler_local_index'));

        // Otro equipo pide 09:00-10:00 en el mismo campo: choca.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payload([$this->linea('2026-11-23', '09:00', '10:00')], 100))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['areas.0.start_time']);
    }

    public function test_reserva_contigua_en_la_misma_area_es_aceptada(): void
    {
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payload([$this->linea('2026-11-23', '08:00', '10:00')], 200))
            ->assertRedirect(route('even_alquiler_local_index'));

        // El segundo equipo empieza justo cuando termina el primero.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payload([$this->linea('2026-11-23', '10:00', '12:00')], 200))
            ->assertRedirect(route('even_alquiler_local_index'));

        $this->assertDatabaseCount('even_local_rentals', 2);
    }

    /** Torneo: serie de fechas del día elegido con un monto fijo para todo el torneo. */
    private function payloadTorneo(array $extra = []): array
    {
        $torneo = array_merge([
            'area_id' => $this->campo->id,
            'start_date' => '2026-11-23',
            'end_date' => '2026-12-14',
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'total_amount' => 900,
            'venue_name' => 'Complejo Los Olivos',
        ], $extra);

        return [
            'local_id' => $this->local->id,
            'customer_id' => $this->crearCliente()->id,
            'event_type' => 'party',
            'beer_provided_by' => 'none',
            'paid_amount' => 200,
            'total_price' => $torneo['total_amount'],
            'tournament' => $torneo,
            'areas' => [],
        ];
    }

    public function test_torneo_crea_una_reserva_con_monto_fijo_y_una_linea_por_fecha(): void
    {
        // Lunes 23/11, 30/11, 07/12 y 14/12: cuatro fechas.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo())
            ->assertRedirect(route('even_alquiler_local_index'));

        $this->assertDatabaseHas('even_local_rentals', [
            'local_id' => $this->local->id,
            'is_tournament' => true,
            'total_price' => 900,
        ]);

        $rental = \Modules\Socialevents\Entities\EvenLocalRental::where('is_tournament', true)->firstOrFail();
        $this->assertCount(4, $rental->items);
    }

    public function test_torneo_en_area_que_no_es_campo_es_rechazado(): void
    {
        $salon = EvenLocalArea::create([
            'local_id' => $this->local->id,
            'name' => 'Salón',
            'area_type' => EvenLocalArea::TYPE_EVENT_HALL,
            'capacity' => 100,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo(['area_id' => $salon->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tournament.area_id']);

        $this->assertDatabaseCount('even_local_rentals', 0);
    }

    public function test_torneo_fuera_del_horario_del_campo_es_rechazado(): void
    {
        // Lunes de 13:00 a 15:00: fuera de 7:00-12:00.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo(['start_time' => '13:00', 'end_time' => '15:00']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tournament.start_time']);

        $this->assertDatabaseCount('even_local_rentals', 0);
    }

    public function test_torneo_sin_fechas_del_dia_elegido_es_rechazado(): void
    {
        // Rango de un solo martes (2026-11-24) pero el día elegido es lunes.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo(['start_date' => '2026-11-24', 'end_date' => '2026-11-24']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tournament.day_of_week']);
    }

    public function test_torneo_que_choca_con_una_reserva_de_la_misma_area_es_rechazado(): void
    {
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payload([$this->linea('2026-11-30', '08:00', '10:00')], 200))
            ->assertRedirect(route('even_alquiler_local_index'));

        // El torneo incluye el lunes 30/11 a la misma hora: choca.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tournament.start_time']);
    }

    public function test_torneo_guarda_solo_el_nombre_del_complejo_como_sede(): void
    {
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo(['venue_name' => 'Complejo Los Olivos']))
            ->assertRedirect(route('even_alquiler_local_index'));

        $this->assertDatabaseHas('even_local_rentals', [
            'is_tournament' => true,
            'tournament_venue' => 'Complejo Los Olivos',
        ]);
    }

    public function test_torneo_con_complejo_inexistente_es_rechazado(): void
    {
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), $this->payloadTorneo(['venue_name' => 'Complejo fantasma']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tournament.venue_name']);

        $this->assertDatabaseCount('even_local_rentals', 0);
    }

    public function test_complejo_no_puede_recibir_areas_ni_horarios(): void
    {
        // Un complejo (even_locals) no es un local de alquiler: no tiene áreas ni horario.
        $complejo = $this->crearComplejo('Complejo sin áreas');

        // Usarlo como local de una reserva por horas debe fallar: el local debe existir en local_sales.
        $this->actingAs($this->organizador)
            ->postJson(route('even_alquiler_local_store'), array_merge(
                $this->payload([$this->linea('2026-11-23', '08:00', '10:00')], 200),
                ['local_id' => $complejo->id],
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['local_id']);

        // Y no se le pueden crear áreas ni horarios desde la gestión de áreas.
        $this->assertDatabaseMissing('even_local_areas', ['local_id' => $complejo->id]);
        $this->assertDatabaseMissing('even_local_area_schedules', ['area_id' => $complejo->id]);
    }

    private function crearComplejo(string $nombre): \Modules\Socialevents\Entities\EvenLocal
    {
        return \Modules\Socialevents\Entities\EvenLocal::create([
            'names' => $nombre,
            'address' => 'Jr. Deportes 456',
            'status' => true,
            'socialevent_organization_id' => $this->organization->id,
        ]);
    }
}
