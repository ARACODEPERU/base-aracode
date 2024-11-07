<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ApisnetPeController extends Controller
{
    protected $token = 'apis-token-11376.43LtemdBY9nZYES8Ky9uZ5oNaYmA0fYe';
    public function consult(Request $request)
    {
        $type = $request->get('document_type');
        $number = $request->get('number');

        if ($type == 6) {
            $data = $this->consultaRUC($number);
        } else {
            $data = $this->consultaDNI($number);
        }


        return response()->json($data);
    }

    public function consultaRUC($ruc)
    {

        if ($ruc) {
            $client = new Client([
                'base_uri' => 'https://api.apis.net.pe/',
                'timeout'  => 2.0,
            ]);

            try {
                // Realizamos la solicitud GET
                $response = $client->request('GET', 'v2/sunat/ruc', [
                    'query' => [
                        'numero' => $ruc
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->token,
                    ],
                ]);

                // Convertimos la respuesta a JSON
                $data = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'person' => $data
                ];
            } catch (ClientException $e) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message = $errorResponse['message'] ?? 'Error desconocido';

                return [
                    'success' => false,
                    'error' => $message
                ];
            } catch (\Exception $e) {
                // Manejo de otros errores no HTTP
                return [
                    'success' => false,
                    'error' => 'Ocurrió un error inesperado: ' . $e->getMessage()
                ];
            }
        }
    }

    public function consultaDNI($dni)
    {

        if ($dni) {
            $client = new Client([
                'base_uri' => 'https://api.apis.net.pe/',
                'timeout'  => 2.0,
            ]);

            try {
                // Realizamos la solicitud GET
                $response = $client->request('GET', 'v2/reniec/dni', [
                    'query' => [
                        'numero' => $dni
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->token,
                    ],
                ]);

                // Convertimos la respuesta a JSON
                $data = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'person' => [
                        'razonSocial' => $data['apellidoPaterno'] . ' ' . $data['apellidoMaterno'] . ' ' . $data['nombres']
                    ]
                ];
            } catch (ClientException $e) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message = $errorResponse['message'] ?? 'Error desconocido';

                return [
                    'success' => false,
                    'error' => $message
                ];
            } catch (\Exception $e) {
                // Manejo de otros errores no HTTP
                return [
                    'success' => false,
                    'error' => 'Ocurrió un error inesperado: ' . $e->getMessage()
                ];
            }
        }
    }
}
