<?php

namespace App\Controller;

use App\Repository\PrizeTypeRepository;
use App\Service\ContestStateService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ContestController
{
    public function __construct(
        private readonly ContestStateService $contestStateService,
        private readonly PrizeTypeRepository $prizeTypes,
    ) {
    }

    #[Route('/api/v1/contest', name: 'api_v1_contest', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $state = $this->contestStateService->getCurrent();
        $prizes = [];
        foreach ($this->prizeTypes->findBy([], ['percent' => 'DESC']) as $prize) {
            $prizes[] = [
                'code' => $prize->getCode(),
                'label' => $prize->getLabel(),
                'percent' => $prize->getPercent(),
            ];
        }

        return new JsonResponse([
            'status' => $state->getStatus()->value,
            'openAt' => $state->getOpenAt()?->format(\DateTimeInterface::ATOM),
            'claimAt' => $state->getClaimAt()?->format(\DateTimeInterface::ATOM),
            'closedAt' => $state->getClosedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $state->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'prizes' => $prizes,
            'grandPrize' => [
                'label' => 'Coffret d\'une valeur de 360 €',
                'note' => 'Tirage au sort après clôture parmi les comptes ayant au moins une participation.',
            ],
        ]);
    }
}
