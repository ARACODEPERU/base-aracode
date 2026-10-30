<?php

namespace Tests\Feature\Socialevents;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionLandingView;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Modules\Socialevents\Support\TournamentLandingCache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La landing registra visitas con su procedencia y datos técnicos, y el
 * organizador puede consultar el resumen desde el listado de ediciones.
 */
class TournamentLandingVisitsTest extends TestCase
{
    use RefreshDatabase;

    private EventEdition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['socialevents.landing_geo_lookup' => false]);

        // La landing usa funciones propias de MySQL para algunas consultas.
        DB::connection()->getPdo()->sqliteCreateFunction('NOW', fn () => now()->toDateTimeString());
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$partes) => implode('', $partes));

        $organization = SocialeventOrganization::create([
            'name' => 'Organización de visitas',
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
            'match_rate' => 5,
            'prepaid_balance' => 1000,
        ]);

        $event = EvenEvent::create([
            'title' => 'Copa de visitas',
            'organization_id' => $organization->id,
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $event->id,
            'year' => 2026,
            'name' => 'Temporada 2026',
            'start_date' => '2026-10-01',
            'landing_published' => true,
            'public_slug' => 'copa-de-visitas',
        ]);

        TournamentLandingCache::forget((int) $this->edition->id);
    }

    private function adminConPermiso(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate([
            'name' => 'even_ediciones_listado',
            'guard_name' => 'web',
        ]));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_una_visita_publica_se_registra_con_origen_ciudad_y_dispositivo(): void
    {
        $this->withHeaders([
            'referer' => 'https://www.instagram.com/publicacion-del-torneo',
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
            'CF-IPCity' => 'Lima',
            'CF-Region' => 'Lima',
            'CF-IPCountry' => 'PE',
        ])->get(route('socialevents_torneos_landing', 'copa-de-visitas'))
            ->assertOk();

        $this->assertDatabaseHas('event_edition_landing_views', [
            'edition_id' => $this->edition->id,
            'source' => 'Instagram',
            'city' => 'Lima',
            'region' => 'Lima',
            'country' => 'PE',
            'country_code' => 'PE',
            'browser' => 'Chrome',
            'browser_version' => '126.0.0.0',
            'platform' => 'Android',
            'device_type' => 'mobile',
            'referrer_host' => 'www.instagram.com',
        ]);

        $this->assertSame(1, (int) $this->edition->fresh()->landing_views);
    }

    public function test_una_visita_directa_desde_computadora_queda_identificada(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        ])->get(route('socialevents_torneos_landing', 'copa-de-visitas'))
            ->assertOk();

        $visit = EventEditionLandingView::query()->firstOrFail();

        $this->assertSame('Directo', $visit->source);
        $this->assertNull($visit->referrer_host);
        $this->assertSame('Windows', $visit->platform);
        $this->assertSame('desktop', $visit->device_type);
        $this->assertSame('Chrome', $visit->browser);
    }

    public function test_los_bots_no_aumentan_el_contador(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get(route('socialevents_torneos_landing', 'copa-de-visitas'))
            ->assertOk();

        $this->assertSame(0, EventEditionLandingView::query()->count());
        $this->assertSame(0, (int) $this->edition->fresh()->landing_views);
    }

    public function test_el_organizador_consulta_el_resumen_y_el_detalle_de_visitas(): void
    {
        foreach ([
            ['city' => 'Lima', 'country' => 'Perú', 'device_type' => 'mobile', 'browser' => 'Chrome', 'platform' => 'Android', 'source' => 'Instagram', 'days' => 0],
            ['city' => 'Lima', 'country' => 'Perú', 'device_type' => 'desktop', 'browser' => 'Firefox', 'platform' => 'Windows', 'source' => 'Directo', 'days' => 1],
            ['city' => 'Trujillo', 'country' => 'Perú', 'device_type' => 'tablet', 'browser' => 'Safari', 'platform' => 'iPadOS', 'source' => 'Facebook', 'days' => 9],
        ] as $index => $row) {
            EventEditionLandingView::create([
                'edition_id' => $this->edition->id,
                'visitor_hash' => str_repeat((string) ($index + 1), 64),
                'city' => $row['city'],
                'country' => $row['country'],
                'country_code' => 'PE',
                'device_type' => $row['device_type'],
                'browser' => $row['browser'],
                'platform' => $row['platform'],
                'source' => $row['source'],
                'viewed_at' => now()->subDays($row['days']),
            ]);
        }

        $this->edition->forceFill(['landing_views' => 3])->save();

        $this->actingAs($this->adminConPermiso())
            ->getJson(route('even_ediciones_landing_visits', $this->edition->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.totals.views', 3)
            ->assertJsonPath('data.totals.unique_visitors', 3)
            ->assertJsonPath('data.totals.last_7_days', 2)
            ->assertJsonPath('data.breakdowns.cities.0.label', 'Lima')
            ->assertJsonPath('data.breakdowns.cities.0.total', 2)
            ->assertJsonFragment(['label' => 'mobile', 'total' => 1])
            ->assertJsonPath('data.recent.0.device_label', 'Móvil')
            ->assertJsonPath('data.recent.0.location', 'Lima, Perú');
    }

    public function test_un_usuario_sin_permiso_no_puede_ver_las_visitas(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('even_ediciones_landing_visits', $this->edition->id))
            ->assertForbidden();
    }
}
