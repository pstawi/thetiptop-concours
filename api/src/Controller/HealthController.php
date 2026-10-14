<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/api/v1/health', name: 'api_v1_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            [
                'status' => 'ok',
                'service' => 'thetiptop-api',
                'version' => 'v1',
            ],
            Response::HTTP_OK
        );
    }
}
