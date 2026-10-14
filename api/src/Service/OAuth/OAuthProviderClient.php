<?php

namespace App\Service\OAuth;

use App\Http\ApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OAuthProviderClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OAuthSettings $settings,
    ) {
    }

    public function buildAuthorizeUrl(string $provider, string $state): string
    {
        $clientId = $this->settings->getClientId($provider);
        $redirectUri = $this->settings->getCallbackUrl($provider);

        return match ($provider) {
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'access_type' => 'online',
                'prompt' => 'select_account',
            ]),
            'facebook' => 'https://www.facebook.com/v18.0/dialog/oauth?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => 'email,public_profile',
            ]),
            default => throw ApiException::validation('Fournisseur OAuth non supporté.'),
        };
    }

    /**
     * @return array{provider: string, subject: string, email: string, firstName: string, lastName: string}
     */
    public function fetchProfile(string $provider, string $code): array
    {
        return match ($provider) {
            'google' => $this->fetchGoogleProfile($code),
            'facebook' => $this->fetchFacebookProfile($code),
            default => throw ApiException::validation('Fournisseur OAuth non supporté.'),
        };
    }

    /**
     * @return array{provider: string, subject: string, email: string, firstName: string, lastName: string}
     */
    private function fetchGoogleProfile(string $code): array
    {
        $tokenResponse = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $this->settings->getClientId('google'),
                'client_secret' => $this->settings->getClientSecret('google'),
                'redirect_uri' => $this->settings->getCallbackUrl('google'),
                'grant_type' => 'authorization_code',
            ],
        ]);

        $tokenData = $tokenResponse->toArray(false);
        $accessToken = $tokenData['access_token'] ?? null;
        if (!\is_string($accessToken) || '' === $accessToken) {
            throw ApiException::unauthorized('Échange du code Google impossible.');
        }

        $profileResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => ['Authorization' => 'Bearer '.$accessToken],
        ]);
        $profile = $profileResponse->toArray(false);

        return [
            'provider' => 'google',
            'subject' => (string) ($profile['sub'] ?? ''),
            'email' => (string) ($profile['email'] ?? ''),
            'firstName' => (string) ($profile['given_name'] ?? ''),
            'lastName' => (string) ($profile['family_name'] ?? ''),
        ];
    }

    /**
     * @return array{provider: string, subject: string, email: string, firstName: string, lastName: string}
     */
    private function fetchFacebookProfile(string $code): array
    {
        $tokenResponse = $this->httpClient->request('GET', 'https://graph.facebook.com/v18.0/oauth/access_token', [
            'query' => [
                'client_id' => $this->settings->getClientId('facebook'),
                'client_secret' => $this->settings->getClientSecret('facebook'),
                'redirect_uri' => $this->settings->getCallbackUrl('facebook'),
                'code' => $code,
            ],
        ]);

        $tokenData = $tokenResponse->toArray(false);
        $accessToken = $tokenData['access_token'] ?? null;
        if (!\is_string($accessToken) || '' === $accessToken) {
            throw ApiException::unauthorized('Échange du code Facebook impossible.');
        }

        $profileResponse = $this->httpClient->request('GET', 'https://graph.facebook.com/me', [
            'query' => [
                'fields' => 'id,email,first_name,last_name',
                'access_token' => $accessToken,
            ],
        ]);
        $profile = $profileResponse->toArray(false);

        return [
            'provider' => 'facebook',
            'subject' => (string) ($profile['id'] ?? ''),
            'email' => (string) ($profile['email'] ?? ''),
            'firstName' => (string) ($profile['first_name'] ?? ''),
            'lastName' => (string) ($profile['last_name'] ?? ''),
        ];
    }
}
