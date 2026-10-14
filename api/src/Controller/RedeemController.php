<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiException;
use App\Service\RedeemService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RedeemController
{
    public function __construct(
        private readonly Security $security,
        private readonly RedeemService $redeemService,
    ) {
    }

    #[Route('/api/v1/codes/redeem', name: 'api_v1_codes_redeem', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw ApiException::unauthorized();
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

        $code = (string) ($payload['code'] ?? '');
        $result = $this->redeemService->redeem(
            $user,
            $code,
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        );

        return new JsonResponse($result);
    }
}
