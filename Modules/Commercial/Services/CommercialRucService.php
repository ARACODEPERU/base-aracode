<?php

namespace Modules\Commercial\Services;

use App\Models\Parameter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CommercialRucService
{
    //api.migo.pe
    protected $baseMigo = 'https://api.migo.pe/api';

    //api.migo.pe
    protected $tokenMigo;

    public function __construct()
    {
        $this->tokenMigo = Parameter::where('parameter_code', 'P000023')->value('value_default');
    }

    public function consultaRUC($ruc)
    {
        $client = new Client();

        try {
            $response = $client->post($this->baseMigo . '/v1/ruc', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'token' => $this->tokenMigo,
                    'ruc' => $ruc,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody(), true);

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
                ],
            ];
        } catch (ClientException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $errorResponse['message'] ?? 'Error desconocido';

            return [
                'success' => false,
                'error' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ocurrió un error inesperado: ' . $e->getMessage(),
            ];
        }
    }
}
