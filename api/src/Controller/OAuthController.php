<?php

namespace App\Controller;

use App\Http\ApiException;
use App\Service\OAuth\OAuthAccountService;
use App\Service\OAuth\OAuthProviderClient;
use App\Service\OAuth\OAuthSettings;
use App\Service\OAuth\OAuthStateManager;
use App\Service\UserResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthController
{
    public function __construct(
        private readonly OAuthSettings $settings,
        private readonly OAuthStateManager $stateManager,
        private readonly OAuthProviderClient $providerClient,
        private readonly OAuthAccountService $accountService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserResponseFactory $userResponseFactory,
    ) {
    }

    #[Route('/api/v1/auth/oauth/{provider}', name: 'api_v1_auth_oauth_start', methods: ['GET'], requirements: ['provider' => 'google|facebook'])]
    public function start(string $provider, Request $request): Response
    {
        $this->assertProvider($provider);

        if (!$this->settings->hasRealCredentials($provider)) {
            if ($this->settings->isMockEnabled()) {
                // Mode démo : pas de clés → front (ou JSON) pour utiliser POST /auth/oauth/mock
                if ($this->wantsJson($request)) {
                    return new JsonResponse([
                        'mock' => true,
                        'provider' => $provider,
                        'message' => 'Clés OAuth absentes : utilisez POST /api/v1/auth/oauth/mock',
                        'mockEndpoint' => '/api/v1/auth/oauth/mock',
                    ]);
                }

                $target = $this->settings->getFrontUrl().'/oauth/callback?mock=1&provider='.urlencode($provider);

                return new RedirectResponse($target);
            }

            throw ApiException::validation(
                sprintf('Clés OAuth %s absentes. Activez OAUTH_MOCK=1 ou configurez les credentials.', $provider)
            );
        }

        $state = $this->stateManager->create($provider);
        $url = $this->providerClient->buildAuthorizeUrl($provider, $state);

        if ($this->wantsJson($request)) {
            return new JsonResponse(['authorizeUrl' => $url, 'provider' => $provider]);
        }

        return new RedirectResponse($url);
    }

    #[Route('/api/v1/auth/oauth/{provider}/callback', name: 'api_v1_auth_oauth_callback', methods: ['GET'], requirements: ['provider' => 'google|facebook'])]
    public function callback(string $provider, Request $request): Response
    {
        $this->assertProvider($provider);

        if ($request->query->has('error')) {
            $message = (string) $request->query->get('error_description', $request->query->get('error'));
            throw ApiException::unauthorized('OAuth refusé : '.$message);
        }

        if (!$this->settings->hasRealCredentials($provider)) {
            throw ApiException::validation('Callback OAuth réel indisponible sans credentials.');
        }

        $code = (string) $request->query->get('code', '');
        $state = (string) $request->query->get('state', '');
        if ('' === $code || '' === $state) {
            throw ApiException::validation('Paramètres OAuth manquants (code/state).');
        }

        $this->stateManager->assertValid($state, $provider);
        $profile = $this->providerClient->fetchProfile($provider, $code);
        $user = $this->accountService->upsertFromProfile($profile);
        $token = $this->jwtManager->create($user);

        if ($this->wantsJson($request)) {
            return new JsonResponse([
                'token' => $token,
                'user' => $this->userResponseFactory->toArray($user),
            ]);
        }

        $target = $this->settings->getFrontUrl().'/oauth/callback?token='.urlencode($token);

        return new RedirectResponse($target);
    }

    #[Route('/api/v1/auth/oauth/mock', name: 'api_v1_auth_oauth_mock', methods: ['POST'])]
    public function mock(Request $request): JsonResponse
    {
        if (!$this->settings->isMockEnabled()) {
            throw ApiException::oauthMockDisabled();
        }

        try {
            /** @var mixed $payload */
            $payload = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiException::validation('JSON invalide.');
        }

        if (!\is_array($payload)) {
            throw ApiException::validation('JSON invalide.');
        }

        $provider = strtolower(trim((string) ($payload['provider'] ?? 'google')));
        $this->assertProvider($provider);

        $email = trim((string) ($payload['email'] ?? 'oauth.mock@thetiptop.local'));
        $firstName = trim((string) ($payload['firstName'] ?? 'OAuth'));
        $lastName = trim((string) ($payload['lastName'] ?? 'Mock'));
        $subject = trim((string) ($payload['subject'] ?? ($provider.'-mock-'.$email)));

        $user = $this->accountService->upsertFromProfile([
            'provider' => $provider,
            'subject' => $subject,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ]);

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'user' => $this->userResponseFactory->toArray($user),
            'mock' => true,
        ]);
    }

    private function assertProvider(string $provider): void
    {
        if (!$this->settings->isProviderSupported($provider)) {
            throw ApiException::validation('Fournisseur OAuth non supporté (google|facebook).');
        }
    }

    private function wantsJson(Request $request): bool
    {
        return str_contains((string) $request->headers->get('Accept', ''), 'application/json')
            || '1' === $request->query->get('json');
    }
}
