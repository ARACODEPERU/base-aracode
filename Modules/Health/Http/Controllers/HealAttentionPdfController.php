<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealSetting;

class HealAttentionPdfController extends Controller
{
    public function download(int $id)
    {
        $attention = HealAttention::with([
            'patient.person',
            'doctor.person',
            'doctor.user',
            'history',
            'exams',
            'treatments',
            'cie10',
        ])->findOrFail($id);

        $settings = HealSetting::first();
        $extra = $settings?->settings ?? [];

        $logoPath = $settings?->establishment_logo
            ? Storage::disk('public')->path($settings->establishment_logo)
            : null;

        $logoUrl = $logoPath && file_exists($logoPath)
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $vitalSigns = [];
        if ($attention->blood_pressure_systolic || $attention->blood_pressure_diastolic) {
            $vitalSigns['blood_pressure'] = ($attention->blood_pressure_systolic ?? '—') . '/' . ($attention->blood_pressure_diastolic ?? '—');
        }
        if ($attention->heart_rate) $vitalSigns['heart_rate'] = $attention->heart_rate . ' lpm';
        if ($attention->temperature) $vitalSigns['temperature'] = $attention->temperature . ' °C';
        if ($attention->oxygen_saturation) $vitalSigns['oxygen_saturation'] = $attention->oxygen_saturation . '%';
        if ($attention->weight) $vitalSigns['weight'] = $attention->weight . ' kg';
        if ($attention->height) $vitalSigns['height'] = $attention->height . ' cm';

        $diagnosis = collect();
        if ($attention->patient_story) $diagnosis->push("**Relato del paciente:**\n" . $attention->patient_story);
        if ($attention->diagnosis) $diagnosis->push("**Diagnóstico:**\n" . $attention->diagnosis);
        if ($attention->cie10) $diagnosis->push("**CIE-10:** " . $attention->cie10->code . ' - ' . $attention->cie10->description);

        $treatmentLines = collect();
        if ($attention->treatments) {
            foreach ($attention->treatments as $t) {
                $treatmentLines->push("- {$t->description}" . ($t->indications ? ": {$t->indications}" : ''));
            }
        }

        $observations = $attention->observations ?? $attention->evolution ?? null;

        $prescription = $extra['recipe_template'] ?? '';
        if ($attention->exams) {
            $examText = $attention->exams->pluck('name')->implode(', ');
            if ($examText) {
                $prescription .= "\n\nExámenes solicitados: {$examText}";
            }
        }

        $data = [
            'establishmentName' => $settings?->establishment_name ?? 'Mi Consultorio',
            'logoUrl' => $logoUrl,
            'ruc' => $extra['ruc'] ?? '',
            'address' => $settings?->address ?? '',
            'phone' => $settings?->phone ?? '',
            'email' => $settings?->notification_email ?? '',
            'primaryColor' => $extra['primary_color'] ?? '#4361ee',
            'printFooter' => $extra['print_footer'] ?? 'Documento generado por el sistema de gestión de salud.',
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
            'patientName' => $attention->patient?->person?->full_name ?? '—',
            'patientDoc' => $attention->patient?->person?->document_type
                ? $attention->patient->person->document_type . ': ' . ($attention->patient->person->document_number ?? '—')
                : '—',
            'doctorName' => $attention->doctor?->person?->full_name ?? '—',
            'doctorSpecialty' => $attention->doctor?->specialty ?? '—',
            'attentionDate' => $attention->attention_at?->format('d/m/Y H:i') ?? '—',
            'serviceType' => ucfirst(str_replace('_', ' ', $attention->service_type ?? 'General')),
            'signedAt' => $attention->signed_at?->format('d/m/Y H:i'),
            'vitalSigns' => !empty($vitalSigns) ? $vitalSigns : null,
            'diagnosis' => $diagnosis->count() ? $diagnosis->implode("\n\n") : null,
            'treatment' => $treatmentLines->count() ? $treatmentLines->implode("\n") : null,
            'observations' => $observations,
            'prescription' => $prescription ?: null,
            'prescriptionData' => $this->prescriptionData($attention, $settings, $extra, $logoUrl),
        ];

        $pdf = Pdf::loadView('health::pdf.attention', $data);
        $pdf->setPaper('A4', 'portrait');

        $fileName = 'atencion_' . $attention->id . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    public function prescription(Request $request, int $id)
    {
        $attention = HealAttention::with([
            'patient.person',
            'doctor.person',
            'treatments',
        ])->findOrFail($id);

        $settings = HealSetting::first();
        $extra = $settings?->settings ?? [];

        $logoPath = $settings?->establishment_logo
            ? Storage::disk('public')->path($settings->establishment_logo)
            : null;

        $logoUrl = $logoPath && file_exists($logoPath)
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        // Extract pharmacological treatments
        $pharmacologicalTreatments = [];
        if ($attention->treatments) {
            foreach ($attention->treatments as $t) {
                if ($t->treatment_type !== 'farmacologica') {
                    continue;
                }

                $pharmData = $t->pharmacological_data;

                $pharmacologicalTreatments[] = [
                    'active_ingredient' => $pharmData['active_ingredient'] ?? null,
                    'dosage' => $pharmData['dosage'] ?? null,
                    'frequency' => $pharmData['frequency'] ?? null,
                    'brand' => $pharmData['brand'] ?? null,
                    'administration_indications' => $pharmData['administration_indications'] ?? null,
                ];
            }
        }

        // Calculate patient age
        $patientAge = '—';
        if ($attention->patient?->person?->birthdate) {
            $birthDate = Carbon::parse($attention->patient->person->birthdate)->startOfDay();
            $today = now()->startOfDay();
            if ($birthDate->lessThanOrEqualTo($today)) {
                $years = (int) $birthDate->diffInYears($today);
                $months = (int) $birthDate->copy()->addYears($years)->diffInMonths($today);
                $patientAge = "{$years}a {$months}m";
            }
        }

        $data = [
            'establishmentName' => $settings?->establishment_name ?? 'Mi Consultorio',
            'logoUrl' => $logoUrl,
            'ruc' => $extra['ruc'] ?? '',
            'address' => $settings?->address ?? '',
            'phone' => $settings?->phone ?? '',
            'email' => $settings?->notification_email ?? '',
            'primaryColor' => $extra['primary_color'] ?? '#4361ee',
            'printFooter' => $extra['print_footer'] ?? 'Documento generado por el sistema de gestión de salud.',
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
            'patientName' => $attention->patient?->person?->full_name ?? '—',
            'patientAge' => $patientAge,
            'doctorName' => $attention->doctor?->person?->full_name ?? '—',
            'doctorSpecialty' => $attention->doctor?->specialty ?? '—',
            'doctorTelephone' => $attention->doctor?->person?->telephone ?? null,
            'doctorAddress' => $attention->doctor?->person?->address ?? null,
            'colegiatura' => $attention->doctor?->colegiatura ?? null,
            'attentionDate' => $attention->attention_at?->format('d/m/Y') ?? '—',
            'pharmacologicalTreatments' => $pharmacologicalTreatments,
            'nonPharmacologicalIndications' => $attention->non_pharmacological_indications,
        ];

        $pdf = Pdf::loadView('health::pdf.prescription', $data);
        $pdf->setPaper('A4', 'portrait');

        $fileName = 'receta_' . $attention->id . '_' . now()->format('Ymd_His') . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    private function prescriptionData(HealAttention $attention, ?HealSetting $settings, array $extra, ?string $logoUrl): array
    {
        $pharmacologicalTreatments = [];
        if ($attention->treatments) {
            foreach ($attention->treatments as $t) {
                if ($t->treatment_type !== 'farmacologica') {
                    continue;
                }

                $pharmData = $t->pharmacological_data;

                $pharmacologicalTreatments[] = [
                    'active_ingredient' => $pharmData['active_ingredient'] ?? null,
                    'dosage' => $pharmData['dosage'] ?? null,
                    'frequency' => $pharmData['frequency'] ?? null,
                    'brand' => $pharmData['brand'] ?? null,
                    'administration_indications' => $pharmData['administration_indications'] ?? null,
                ];
            }
        }

        $patientAge = '-';
        if ($attention->patient?->person?->birthdate) {
            $birthDate = Carbon::parse($attention->patient->person->birthdate)->startOfDay();
            $today = now()->startOfDay();
            if ($birthDate->lessThanOrEqualTo($today)) {
                $years = (int) $birthDate->diffInYears($today);
                $months = (int) $birthDate->copy()->addYears($years)->diffInMonths($today);
                $patientAge = "{$years}a {$months}m";
            }
        }

        return [
            'establishmentName' => $settings?->establishment_name ?? 'Mi Consultorio',
            'logoUrl' => $logoUrl,
            'ruc' => $extra['ruc'] ?? '',
            'address' => $settings?->address ?? '',
            'phone' => $settings?->phone ?? '',
            'email' => $settings?->notification_email ?? '',
            'primaryColor' => $extra['primary_color'] ?? '#4361ee',
            'printFooter' => $extra['print_footer'] ?? 'Documento generado por el sistema de gestion de salud.',
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
            'patientName' => $attention->patient?->person?->full_name ?? '-',
            'patientAge' => $patientAge,
            'doctorName' => $attention->doctor?->person?->full_name ?? '-',
            'doctorSpecialty' => $attention->doctor?->specialty ?? '-',
            'doctorTelephone' => $attention->doctor?->person?->telephone ?? null,
            'doctorAddress' => $attention->doctor?->person?->address ?? null,
            'colegiatura' => $attention->doctor?->colegiatura ?? null,
            'attentionDate' => $attention->attention_at?->format('d/m/Y') ?? '-',
            'pharmacologicalTreatments' => $pharmacologicalTreatments,
            'nonPharmacologicalIndications' => $attention->non_pharmacological_indications,
        ];
    }
}
