<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiException;
use App\Repository\ClaimRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ParticipationsController
{
    public function __construct(
        private readonly Security $security,
        private readonly ParticipationRepository $participations,
        private readonly ClaimRepository $claims,
    ) {
    }

    #[Route('/api/v1/me/participations', name: 'api_v1_me_participations', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw ApiException::unauthorized();
        }

        $rows = $this->participations->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $data = [];

        foreach ($rows as $participation) {
            $ticket = $participation->getTicketCode();
            $prize = $ticket->getPrizeType();
            $claim = $this->claims->findOneBy(['participation' => $participation]);
            $code = $ticket->getCode();
            $masked = str_repeat('X', max(0, \strlen($code) - 4)).substr($code, -4);

            $data[] = [
                'id' => $participation->getId(),
                'code' => $masked,
                'prize' => [
                    'code' => $prize->getCode(),
                    'label' => $prize->getLabel(),
                ],
                'claim' => $claim ? [
                    'id' => $claim->getId(),
                    'status' => $claim->getStatus()->value,
                    'channel' => $claim->getChannel()->value,
                    'deliveredAt' => $claim->getDeliveredAt()?->format(\DateTimeInterface::ATOM),
                ] : null,
                'participatedAt' => $participation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return new JsonResponse(['items' => $data]);
    }
}
