<?php

namespace App\Service\OAuth;

final class OAuthSettings
{
    public function __construct(
        private readonly string $appSecret,
        private readonly string $defaultUri,
        private readonly string $frontUrl,
        private readonly bool $mockEnabled,
        private readonly ?string $googleClientId,
        private readonly ?string $googleClientSecret,
        private readonly ?string $facebookClientId,
        private readonly ?string $facebookClientSecret,
    ) {
    }

    public function isMockEnabled(): bool
    {
        return $this->mockEnabled;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function getFrontUrl(): string
    {
        return rtrim($this->frontUrl, '/');
    }

    public function getDefaultUri(): string
    {
        return rtrim($this->defaultUri, '/');
    }

    public function isProviderSupported(string $provider): bool
    {
        return \in_array($provider, ['google', 'facebook'], true);
    }

    public function hasRealCredentials(string $provider): bool
    {
        return match ($provider) {
            'google' => null !== $this->googleClientId && '' !== $this->googleClientId
                && null !== $this->googleClientSecret && '' !== $this->googleClientSecret,
            'facebook' => null !== $this->facebookClientId && '' !== $this->facebookClientId
                && null !== $this->facebookClientSecret && '' !== $this->facebookClientSecret,
            default => false,
        };
    }

    public function getClientId(string $provider): string
    {
        return match ($provider) {
            'google' => (string) $this->googleClientId,
            'facebook' => (string) $this->facebookClientId,
            default => '',
        };
    }

    public function getClientSecret(string $provider): string
    {
        return match ($provider) {
            'google' => (string) $this->googleClientSecret,
            'facebook' => (string) $this->facebookClientSecret,
            default => '',
        };
    }

    public function getCallbackUrl(string $provider): string
    {
        return $this->getDefaultUri().'/api/v1/auth/oauth/'.$provider.'/callback';
    }
}
