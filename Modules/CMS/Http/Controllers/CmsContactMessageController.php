<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CmsContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
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

        $messages = $query->latest()->paginate(15)->appends($request->query());

        $stats = [
            'total' => ContactMessage::count(),
            'pending' => ContactMessage::where('status', 'pending')->count(),
            'read' => ContactMessage::where('status', 'read')->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
        ];

        return Inertia::render('CMS::ContactMessages/List', [
            'messages' => $messages,
            'stats' => $stats,
            'filters' => $request->all(['search', 'status', 'dates']),
        ]);
    }

    public function show($id)
    {
        $contactMessage = ContactMessage::findOrFail($id);

        if ($contactMessage->status === 'pending') {
            $contactMessage->update(['status' => 'read']);
        }

        return Inertia::render('CMS::ContactMessages/Show', [
            'contactMessage' => $contactMessage,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contactMessage = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,read,replied',
            'admin_notes' => 'nullable|string',
        ]);

        $contactMessage->update($validated);

        return redirect()->route('cms_contact_messages_show', $id)
            ->with('success', 'Mensaje actualizado correctamente.');
    }
}
