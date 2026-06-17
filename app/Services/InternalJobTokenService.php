<?php

namespace App\Services;

use Illuminate\Support\Str;

class InternalJobTokenService
{
    public function issue(int $userId, int $ttlSeconds = 3600): string
    {
        $payload = base64_encode(json_encode([
            'uid' => $userId,
            'exp' => now()->addSeconds($ttlSeconds)->timestamp,
            'nonce' => Str::random(16),
        ], JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $payload, $this->signingKey());

        return $payload.'.'.$signature;
    }

    /**
     * @return array{uid: int, exp: int, nonce: string}|null
     */
    public function verify(?string $token): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $expected = hash_hmac('sha256', $payload, $this->signingKey());

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        try {
            $data = json_decode(base64_decode($payload, true), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($data) || ! isset($data['uid'], $data['exp'])) {
            return null;
        }

        if ((int) $data['exp'] < now()->timestamp) {
            return null;
        }

        return [
            'uid' => (int) $data['uid'],
            'exp' => (int) $data['exp'],
            'nonce' => (string) ($data['nonce'] ?? ''),
        ];
    }

    private function signingKey(): string
    {
        $key = (string) config('services.internal_api.key', '');

        if ($key === '') {
            throw new \RuntimeException('INTERNAL_API_KEY is not configured.');
        }

        return $key;
    }
}
