<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\CRM\Entities\CrmMessage;
use Modules\CRM\Entities\CrmParticipant;

class CrmIaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function clientDashboard()
    {
        $conversationId = request()->get('conv');
        $participants = CrmParticipant::with('user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', '<>', Auth::id())
            ->get();

        $messages = CrmMessage::where('conversation_id', $conversationId)
            ->orderBy('id')
            ->limit(200)
            ->get();
        //dd($messages);
        return Inertia::render('CRM::Chat/studentDashboard', [
            'messages' => $messages,
            'participants' => $participants
        ]);
    }

    public function send_prompt($user_id, $message, $archivo = null)
    {
        $port = env('AI__PORT', 5000);
        // URL del servidor Flask
        $url = 'http://127.0.0.1:' . $port . '/assistant_ai';

        $retryCount = 5; // Número de reintentos
        $retryDelay = 300; // Retardo entre reintentos en milisegundos

        for ($i = 0; $i < $retryCount; $i++) {
            try {
                // Enviar la solicitud POST
                $response = Http::asForm()->timeout(30)->post($url, [
                    'user_id' => $user_id,
                    'message' => $message,
                    'archivo' => $archivo,
                ]);

                // Verificar si la solicitud fue exitosa
                if ($response->successful()) {
                    // Devolver la respuesta del servidor Flask
                    $data = response()->json($response->json())->original; // Accede al contenido
                    return $data['response']; // Accede al campo 'response'
                } else {
                    // Manejar el error
                    return response()->json([
                        'error' => 'Error al comunicarse con el servidor de AI',
                        'details' => $response->body(),
                    ], $response->status());
                }
            } catch (\Exception $e) {
                // Capturar excepciones de tiempo de espera agotado y realizar un nuevo intento
                if ($i < $retryCount - 1 && $e instanceof \Illuminate\Http\Client\RequestException && $e->getCode() === 28) {
                    usleep($retryDelay * 1000); // Retardo en microsegundos
                } else {
                    throw $e; // Lanzar la excepción si no es un error de tiempo de espera agotado o se ha excedido el número de reintentos
                }
            }
        }

        // Si todos los reintentos fallan, devolver la respuesta de la última solicitud
        return $response;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('crm::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
