<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealActivity;

class HealActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = HealActivity::with(['actorPerson', 'patient.person'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('actor_display', 'like', "%{$search}%")
                        ->orWhere('patient_display', 'like', "%{$search}%")
                        ->orWhere('activity_type', 'like', "%{$search}%");
                });
            })
            ->when($request->get('activity_type'), function ($query, $type) {
                $query->where('activity_type', $type);
            })
            ->when($request->get('date_from'), function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->get('date_to'), function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->latest('created_at')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        return Inertia::render('Health::Admin/Activities/Index', [
            'activities' => $activities,
            'filters' => $request->all('search', 'activity_type', 'date_from', 'date_to'),
            'activityTypes' => [
                'created' => 'Creación',
                'updated' => 'Modificación',
                'deleted' => 'Eliminación',
                'signed' => 'Firma',
                'cancelled' => 'Anulación',
                'restored' => 'Restauración',
            ],
        ]);
    }

    public function show(int $id)
    {
        $activity = HealActivity::with(['actorPerson', 'patient.person'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'activity' => $activity,
        ]);
    }
}
