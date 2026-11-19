<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * El menú y las vistas de Salud resuelven nombres de ruta en el navegador.
 *
 * Si una ruta se renombra en el backend, la aplicación entera falla al
 * renderizar el dashboard con "route is not in the route list" y la pantalla
 * queda en blanco. Esta prueba fija los nombres que consume el front.
 */
class HealthMenuRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_nombres_de_ruta_del_menu_y_las_vistas_existen(): void
    {
        $nombres = [
            // Menú lateral (Menu.js).
            'health_dashboard',
            'heal_doctors_list',
            'heal_patients_list',
            'heal_attentions_list',
            'heal_agendas_list',
            'heal_appointment_notices',
            'heal_google_calendar',
            'heal_clinical_records_list',
            'heal_procedure_charges_list',
            'heal_activities_list',
            'heal_parameters',
            'heal_settings',

            // Acciones de las pantallas de pacientes y doctores.
            'heal_patients_create',
            'heal_patients_store',
            'heal_patients_edit',
            'heal_patients_update',
            'heal_patients_destroy',
            'heal_patients_panel',
            'heal_patients_story',
            'heal_patients_appointments',
            'heal_patients_search',
            'heal_patients_vitals_last',
            'heal_doctors_create',
            'heal_doctors_store',
            'heal_doctors_edit',
            'heal_doctors_update',
            'heal_doctors_destroy',
            'heal_doctors_pin_update',
            'heal_doctors_access_reset',

            // Agendas y atenciones.
            'heal_agendas_day',
            'heal_agendas_availability',
            'heal_agendas_appointments_store',
            'heal_agendas_appointments_move',
            'heal_attentions_create',
            'heal_attentions_store',
            'heal_attentions_edit',
            'heal_attentions_update',
            'heal_attentions_destroy',
            'heal_attentions_sign',
            'heal_attentions_pdf',
            'heal_attentions_prescription',
            'heal_clinical_records_show',
            'heal_activities_export',
        ];

        $faltantes = array_values(array_filter(
            $nombres,
            fn (string $nombre) => !Route::has($nombre)
        ));

        $this->assertSame([], $faltantes, 'Rutas usadas por el front que no existen: ' . implode(', ', $faltantes));
    }
}
