<?php

namespace App\Service\OAuth;

use App\Http\ApiException;

final class OAuthStateManager
{
    public function __construct(
        private readonly OAuthSettings $settings,
    ) {
    }

    public function create(string $provider): string
    {
        $payload = [
            'p' => $provider,
            'n' => bin2hex(random_bytes(8)),
            'e' => time() + 600,
        ];
        $body = rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $body, $this->settings->getAppSecret());

        return $body.'.'.$sig;
    }

    public function assertValid(string $state, string $expectedProvider): void
    {
        $parts = explode('.', $state, 2);
        if (2 !== \count($parts)) {
            throw ApiException::validation('State OAuth invalide.');
        }

        [$body, $sig] = $parts;
        $expected = hash_hmac('sha256', $body, $this->settings->getAppSecret());
        if (!hash_equals($expected, $sig)) {
            throw ApiException::validation('State OAuth invalide.');
        }

        $json = base64_decode(strtr($body, '-_', '+/'), true);
        if (false === $json) {
            throw ApiException::validation('State OAuth invalide.');
        }

        try {
            /** @var array{p?: string, e?: int} $payload */
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiException::validation('State OAuth invalide.');
        }

        if (($payload['p'] ?? null) !== $expectedProvider) {
            throw ApiException::validation('State OAuth invalide.');
        }

        if (($payload['e'] ?? 0) < time()) {
            throw ApiException::validation('State OAuth expiré.');
        }
    }
}
