<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealSetting;
use Modules\Health\Support\BillingOptions;

class HealSettingController extends Controller
{
    public function index(): Response
    {
        $settings = HealSetting::first();

        return Inertia::render('Health::Admin/Settings/Index', [
            'settings' => $settings ? $settings->toArray() : [
                'establishment_name' => 'Mi Consultorio',
                'establishment_logo' => null,
                'representative' => null,
                'notification_email' => null,
                'phone' => null,
                'address' => null,
                'working_hours' => [
                    ['start' => '08:00', 'end' => '14:00'],
                    ['start' => '16:00', 'end' => '20:00'],
                ],
                'settings' => [
                    'primary_color' => '#4361ee',
                    'secondary_color' => '#805dca',
                    'ruc' => '',
                    'print_footer' => 'Documento generado por el sistema de gestión de salud.',
                    'recipe_template' => '',
                ],
            ],
            // Opciones de facturación de los cobros (afectación IGV, unidad y
            // textos) más los catálogos SUNAT con los que se elige la unidad.
            'billingOptions' => BillingOptions::read(),
            'affectations' => $this->catalog('sunat_affectation_igv_types', ['id', 'description']),
            'unitTypes' => $this->catalog('sunat_unit_types', ['id', 'symbol', 'description'], ['active' => true]),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'establishment_name' => ['nullable', 'string', 'max:255'],
            'representative' => ['nullable', 'string', 'max:255'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'working_hours' => ['nullable', 'array', 'min:1'],
            'working_hours.*.start' => ['required', 'string', 'date_format:H:i'],
            'working_hours.*.end' => ['required', 'string', 'date_format:H:i'],

            // Extra settings stored in JSON
            'primary_color' => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'print_footer' => ['nullable', 'string', 'max:500'],
            'recipe_template' => ['nullable', 'string', 'max:2000'],

            // Opciones de facturación de los cobros: se guardan en el grupo
            // `billing` de settings y las lee HealProcedureChargeController.
            'igv_affectation' => ['nullable', Rule::in(BillingOptions::AFFECTATIONS)],
            'unit_type' => ['nullable', 'string', 'max:3'],
            'billing_description' => ['nullable', 'string', 'max:255'],
            'consultation_recobro_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'allow_cancel_with_document' => ['nullable', 'boolean'],
        ]);

        $settings = HealSetting::first();

        if (!$settings) {
            $settings = new HealSetting();
        }

        $settings->fill($request->only([
            'establishment_name',
            'representative',
            'notification_email',
            'phone',
            'address',
        ]));

        if ($request->has('working_hours')) {
            $settings->working_hours = $request->input('working_hours');
        }

        // Merge extra settings into the settings JSON field
        $extraSettings = $settings->settings ?? [];
        foreach (['primary_color', 'secondary_color', 'ruc', 'print_footer', 'recipe_template'] as $field) {
            if ($request->has($field)) {
                $extraSettings[$field] = $request->input($field);
            }
        }
        $settings->settings = $extraSettings;

        $settings->save();

        BillingOptions::save($request->only([
            'igv_affectation',
            'unit_type',
            'billing_description',
            'consultation_recobro_months',
            'allow_cancel_with_document',
        ]));

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
        ]);

        $settings = HealSetting::first();

        if (!$settings) {
            $settings = new HealSetting();
        }

        if ($settings->establishment_logo) {
            Storage::disk('public')->delete($settings->establishment_logo);
        }

        $path = $request->file('logo')->store('health/establishment', 'public');
        $settings->establishment_logo = $path;
        $settings->save();

        return response()->json([
            'success' => true,
            'logo_url' => Storage::url($path),
        ]);
    }

    /**
     * Catálogo SUNAT para los selectores del panel.
     *
     * Devuelve vacío si la tabla no existe todavía, para que la pantalla siga
     * abriendo en un despliegue que aún no corrió las migraciones base.
     *
     * @param array<int, string> $columns
     * @param array<string, mixed> $where
     * @return array<int, object>
     */
    private function catalog(string $table, array $columns, array $where = []): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->when($where, fn ($query, $conditions) => $query->where($conditions))
            ->orderBy('id')
            ->get($columns)
            ->all();
    }

    public function deleteLogo()
    {
        $settings = HealSetting::first();

        if ($settings && $settings->establishment_logo) {
            Storage::disk('public')->delete($settings->establishment_logo);
            $settings->establishment_logo = null;
            $settings->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo eliminado correctamente.',
        ]);
    }
}
