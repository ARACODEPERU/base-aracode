<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BlogSubscriberController extends Controller
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

        $subscribers = $query->latest()->paginate(20);
        
        $stats = [
            'total' => BlogSubscriber::count(),
            'active' => BlogSubscriber::where('status', 'active')->count(),
            'this_month' => BlogSubscriber::whereMonth('created_at', now()->month)->count(),
            'this_week' => BlogSubscriber::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('admin.blog-subscribers', compact('subscribers', 'stats'));
    }

    public function destroy(BlogSubscriber $blogSubscriber)
    {
        $blogSubscriber->update(['status' => 'unsubscribed']);

        return redirect()->route('admin.blog-subscribers.index')
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
