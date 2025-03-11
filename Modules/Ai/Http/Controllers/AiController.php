<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $opciones = [
            'post->consulta->request->[user_id, content]',
            'post->censurar->request->[user_id, content]',
            'post->mejorar->request->[user_id, content]',
            'post->recomendar->request->[user_id, content]',
        ];

        return response()->json($opciones);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function consulta(Request $request): String
    {
        $user_id=null;
        try {
            $user_id = Auth::user()->id;
        } catch (\Throwable $th) {
            $user_id = $request->input('user_id');
        }

        $message = $request->input('message');
        $respuesta = $this->send_prompt($user_id, $message);
        return $respuesta;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function censurar(Request $request)
    {
        $user_id=null;
        try {
            $user_id = Auth::user()->id;
        } catch (\Throwable $th) {
            $user_id = $request->input('user_id');
        }
        $cens = "por favor censura con asteriscos los nombres personales y de empresas privadas en el siguiente texto, las públicas no; solo responde lo que pedí sin palabras previas o saludos: ";
        $message = $cens.$request->input('message');
        $respuesta = $this->send_prompt($user_id, $message);
        return $respuesta;
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('ai::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('ai::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function send_prompt($user_id, $message, $archivo = null)
    {
        $port = env('AI__PORT', 5000);
        // URL del servidor Flask
        $url = 'http://127.0.0.1:'.$port.'/assistant_ai';

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
}
