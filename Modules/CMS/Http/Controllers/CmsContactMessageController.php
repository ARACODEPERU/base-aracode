<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Carbon\Carbon;
use Modules\CMS\Emails\ContactMessageReplyMail;

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

    /**
     * Envia al correo de la persona la respuesta escrita desde el panel (listado
     * o detalle) y deja el mensaje como respondido.
     *
     * El estado solo cambia si el correo salio de verdad: marcar respondido
     * cuando el envio fallo dejaria sin seguimiento a quien nunca recibio nada.
     */
    public function reply(Request $request, $id)
    {
        $contactMessage = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'message.required' => 'El mensaje de respuesta no puede estar vacio.',
            'message.max' => 'El mensaje de respuesta no puede superar los 5000 caracteres.',
        ]);

        try {
            Mail::to($contactMessage->email)->send(new ContactMessageReplyMail(
                $validated['subject'],
                $validated['message'],
                [
                    'name' => $contactMessage->name,
                    'email' => $contactMessage->email,
                    'service' => $contactMessage->service,
                    'message' => $contactMessage->message,
                    'created_at' => optional($contactMessage->created_at)->format('d/m/Y H:i'),
                ],
            ));
        } catch (\Throwable $e) {
            Log::error('Contacto: no se pudo enviar la respuesta a ' . $contactMessage->email . ': ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Un fallo de envio vuelve al front como error del formulario, no como
            // exito, para que el administrador sepa que el correo no salio.
            throw ValidationException::withMessages([
                'subject' => 'No se pudo enviar el correo a ' . $contactMessage->email
                    . '. Intentalo de nuevo o revisa el log del servidor.',
            ]);
        }

        if ($contactMessage->status !== 'replied') {
            $contactMessage->update(['status' => 'replied']);
        }

        return back()->with('success', 'Respuesta enviada a ' . $contactMessage->email . '.');
    }
}
