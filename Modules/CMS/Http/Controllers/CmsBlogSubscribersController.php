<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlogSubscriber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class CmsBlogSubscribersController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogSubscriber::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($dates = $request->get('dates')) {
            if ($dates) {
                if (str_contains($dates, ' to ') || str_contains($dates, ' a ')) {
                    $separator = str_contains($dates, ' to ') ? ' to ' : ' a ';
                    [$startDate, $endDate] = explode($separator, $dates);
                    $query->whereDate('created_at', '>=', Carbon::parse($startDate)->startOfDay())
                          ->whereDate('created_at', '<=', Carbon::parse($endDate)->endOfDay());
                } else {
                    $query->whereDate('created_at', Carbon::parse($dates)->toDateString());
                }
            }
        }

        $subscribers = $query->latest()->paginate(20)->appends($request->query());

        $stats = [
            'total' => BlogSubscriber::count(),
            'active' => BlogSubscriber::where('status', 'active')->count(),
            'this_month' => BlogSubscriber::whereMonth('created_at', now()->month)->count(),
            'this_week' => BlogSubscriber::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return Inertia::render('CMS::BlogSubscribers/List', [
            'subscribers' => $subscribers,
            'stats' => $stats,
            'filters' => $request->all(['search', 'status', 'dates']),
        ]);
    }

    public function destroy($id)
    {
        $subscriber = BlogSubscriber::findOrFail($id);
        $subscriber->update(['status' => 'unsubscribed']);

        return redirect()->route('cms_blog_subscribers_list')
            ->with('success', 'Suscriptor dado de baja correctamente.');
    }

    public function export(Request $request)
    {
        $query = BlogSubscriber::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $subscribers = $query->latest()->get();

        $csv = "Nombre,Email,Estado,Fuente,Fecha de suscripción\n";

        foreach ($subscribers as $subscriber) {
            $csv .= '"' . ($subscriber->name ?? '') . '",';
            $csv .= '"' . $subscriber->email . '",';
            $csv .= '"' . ($subscriber->status === 'active' ? 'Activo' : 'Inactivo') . '",';
            $csv .= '"' . ($subscriber->source ?? 'blog') . '",';
            $csv .= '"' . $subscriber->created_at->format('d/m/Y H:i') . "\"\n";
        }

        $filename = 'suscriptores-blog-' . now()->format('Y-m-d') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
