<?php

namespace App\Broadcasting;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SocketIoBroadcaster
{
    protected $client;
    protected $url;

    public function __construct()
    {
        $this->client = new Client();

        $this->url = env('VITE_SOCKET_IO_SERVER', 'https://localhost:3000');
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        try {
            foreach ($channels as $channel) {
                $this->client->post("{$this->url}/api/crm/broadcast", [
                    'json' => [
                        'channel' => $channel,
                        'event' => $event,
                        'data' => $payload,
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SocketIOBroadcaster: ' . $e->getMessage());
        }
    }
}
