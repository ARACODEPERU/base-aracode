<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealSetting;

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
