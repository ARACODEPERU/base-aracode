<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use PhpParser\Builder\Param;

class ApisnetPeController extends Controller
{
    //apis.net
    //protected $token;
    //decolecta.com
    protected $token;
    //api.migo.pe
    protected $tokenMigo;

    //apis.net
    //protected $base_url = 'https://api.apis.net.pe/';
    //decolecta.com
    protected $baseUrl = 'https://api.decolecta.com/';
    //api.migo.pe
    protected $baseMigo = 'https://api.migo.pe/api';

    public function __construct()
    {
        //$this->token = Parameter::where('parameter_code', 'P000012')->value('value_default');
        $this->token = Parameter::where('parameter_code', 'P000024')->value('value_default');
        $this->tokenMigo = Parameter::where('parameter_code', 'P000023')->value('value_default');
    }

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
                'base_uri' => $this->baseUrl,
                'timeout'  => 2.0,
            ]);

            //$sunat = 'v2/sunat/ruc';
            //$sunat = 'v1/sunat/ruc/full';
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
                    'person' => [
                        'razon_social' => $data['razon_social'],
                        'numero_documento' => $data['numero_documento'],
                        'direccion' => $data['direccion'],
                        'estado' => $data['estado'],
                        'condicion' => $data['condicion'],
                        'ubigeo' => $data['ubigeo'],
                        'distrito' => $data['distrito'],
                        'provincia' => $data['provincia'],
                        'departamento' => $data['departamento'],
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

    public function consultaDNI($dni)
    {

        if ($dni) {
            $client = new Client([
                'base_uri' => $this->baseUrl,
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
                        'razon_social' => $data['full_name'],
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

    public function consultMigo(Request $request)
    {
        $type = $request->get('document_type');
        $number = $request->get('number');

        if ($type == 6) {
            $data = $this->consultaRUCmigo($number);
        } else {
            $data = $this->consultaDNI($number);
        }


        return response()->json($data);
    }

    public function consultaRUCmigo($ruc){
        $client = new Client();

        try {
            $response = $client->post($this->baseMigo . '/v1/ruc', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'token' => $this->tokenMigo, // ⚠️ Guarda tu token en .env
                    'ruc' => $ruc,
                ],
                'timeout' => 10, // segundos (opcional)
            ]);

            // Obtener respuesta como array asociativo
            $data = json_decode($response->getBody(), true);
            //dd($data);
            return [
                'success' => true,
                'person' => [
                    'razon_social' => $data['nombre_o_razon_social'],
                    'numero_documento' => $data['ruc'],
                    'direccion' => $data['direccion_simple'],
                    'estado' => $data['estado_del_contribuyente'],
                    'condicion' => $data['condicion_de_domicilio'],
                    'ubigeo' => $data['ubigeo'],
                    'distrito' => $data['distrito'],
                    'provincia' => $data['provincia'],
                    'departamento' => $data['departamento'],
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

    public function consultaDNImigo($dni){
        $client = new Client();

        try {
            $response = $client->post($this->baseMigo . '/v1/dni', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'token' => $this->tokenMigo, // ⚠️ Guarda tu token en .env
                    'ruc' => $dni,
                ],
                'timeout' => 10, // segundos (opcional)
            ]);

            // Obtener respuesta como array asociativo
            $data = json_decode($response->getBody(), true);
            //dd($data);
            return [
                'success' => true,
                'person' => [
                    'razon_social' => $data['nombre'],
                    'names' => null,
                    'father_lastname' => null,
                    'mother_lastname' => null,
                    'document_number' => null,
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
