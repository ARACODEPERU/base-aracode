<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ApisnetPeController extends Controller
{
    //apis.net
    //protected $token = 'apis-token-11376.43LtemdBY9nZYES8Ky9uZ5oNaYmA0fYe';
    //decolecta.com
    protected $token = 'sk_5647.ESeEwR43KlU9bhHFlTpMQaYdY1QuI5ZG';

    //apis.net
    //protected $base_url = 'https://api.apis.net.pe/';
    //decolecta.com
    protected $base_url = 'https://api.decolecta.com/';

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
                'base_uri' => $this->base_url,
                'timeout'  => 2.0,
            ]);

            //$sunat = 'v2/sunat/ruc';
            $sunat = 'v1/sunat/ruc/full';

            try {
                // Realizamos la solicitud GET
                $response = $client->request('GET', $sunat, [
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
                //dd($data);
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
                'base_uri' => $this->base_url,
                'timeout'  => 2.0,
            ]);

            try {
                //$sunat = 'v2/sunat/ruc';
                $sunat = 'v1/reniec/dni';
                // Realizamos la solicitud GET
                $response = $client->request('GET', $sunat, [
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

                // return [
                //     'success' => true,
                //     'person' => [
                //         'razonSocial' => $data['apellidoPaterno'] . ' ' . $data['apellidoMaterno'] . ' ' . $data['nombres'],
                //         'names' => $data['nombres'],
                //         'father_lastname' => $data['apellidoPaterno'],
                //         'mother_lastname' => $data['apellidoMaterno'],
                //     ]
                // ];

                return [
                    'success' => true,
                    'person' => [
                        'razonSocial' => $data['full_name'],
                        'names' => $data['first_name'],
                        'father_lastname' => $data['first_last_name'],
                        'mother_lastname' => $data['second_last_name'],
                        'document_number' => $data["document_number"],
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
