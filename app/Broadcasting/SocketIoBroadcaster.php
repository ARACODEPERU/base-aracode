<?php

namespace App\Broadcasting;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SocketIoBroadcaster
{
    protected $client;
    protected $url;
    protected $maxAttempts;
    protected $retryDelays = [30, 60, 100];

    public function __construct()
    {
        $this->client = new Client([
            'connect_timeout' => (float) env('SOCKET_IO_BROADCAST_CONNECT_TIMEOUT', 10),
            'timeout' => (float) env('SOCKET_IO_BROADCAST_TIMEOUT', 20),
        ]);

        $this->url = rtrim((string) config('services.socket_io.internal_url', 'http://127.0.0.1:3000'), '/');
        $this->maxAttempts = (int) env('SOCKET_IO_BROADCAST_MAX_ATTEMPTS', 5);
    }

    protected function internalApiHeaders(): array
    {
        $key = (string) config('services.internal_api.key', '');

        if ($key === '') {
            return [];
        }

        return ['X-Internal-Api-Key' => $key];
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($channels as $channel) {
            $this->postWithRetry($channel, $event, $payload);
        }
    }

    protected function postWithRetry($channel, $event, array $payload = [])
    {
        $attempt = 1;

        while ($this->maxAttempts <= 0 || $attempt <= $this->maxAttempts) {
            try {
                $this->client->post("{$this->url}/api/crm/broadcast", [
                    'headers' => $this->internalApiHeaders(),
                    'json' => [
                        'channel' => $channel,
                        'event' => $event,
                        'data' => $payload,
                    ],
                ]);

                return;
            } catch (\Exception $e) {
                if ($this->maxAttempts > 0 && $attempt >= $this->maxAttempts) {
                    Log::error('SocketIOBroadcaster: ' . $e->getMessage());
                    return;
                }

                $delay = $this->retryDelay($attempt);
                Log::warning("SocketIOBroadcaster retry in {$delay}s: " . $e->getMessage());
                sleep($delay);
                $attempt++;
            }
        }
    }

    protected function retryDelay($attempt)
    {
        return $this->retryDelays[min($attempt - 1, count($this->retryDelays) - 1)];
    }
}
