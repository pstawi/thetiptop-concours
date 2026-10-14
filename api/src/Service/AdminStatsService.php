<?php

namespace App\Service;

use App\Enum\TicketStatus;
use App\Repository\ClaimRepository;
use App\Repository\ParticipationRepository;
use App\Repository\PrizeTypeRepository;
use App\Repository\TicketCodeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AdminStatsService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TicketCodeRepository $tickets,
        private readonly PrizeTypeRepository $prizeTypes,
        private readonly UserRepository $users,
        private readonly ParticipationRepository $participations,
        private readonly ClaimRepository $claims,
        private readonly ContestStateService $contestStateService,
    ) {
    }

    /** @return array<string, mixed> */
    public function getStats(): array
    {
        $totalTickets = (int) $this->tickets->count([]);
        $used = (int) $this->tickets->count(['status' => TicketStatus::USED]);
        $available = (int) $this->tickets->count(['status' => TicketStatus::AVAILABLE]);

        $prizeBreakdown = [];
        foreach ($this->prizeTypes->findBy([], ['percent' => 'DESC']) as $prize) {
            $usedForPrize = (int) $this->em->createQueryBuilder()
                ->select('COUNT(t.id)')
                ->from(\App\Entity\TicketCode::class, 't')
                ->andWhere('t.prizeType = :prize')
                ->andWhere('t.status = :used')
                ->setParameter('prize', $prize)
                ->setParameter('used', TicketStatus::USED)
                ->getQuery()
                ->getSingleScalarResult();

            $prizeBreakdown[] = [
                'code' => $prize->getCode(),
                'label' => $prize->getLabel(),
                'percent' => $prize->getPercent(),
                'stockTarget' => $prize->getStockTarget(),
                'used' => $usedForPrize,
            ];
        }

        $usersCount = (int) $this->users->count([]);
        $optInCount = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(\App\Entity\User::class, 'u')
            ->andWhere('u.marketingOptIn = true')
            ->getQuery()
            ->getSingleScalarResult();

        $pendingClaims = (int) $this->claims->count(['status' => \App\Enum\ClaimStatus::PENDING]);
        $deliveredClaims = (int) $this->claims->count(['status' => \App\Enum\ClaimStatus::DELIVERED]);

        return [
            'contest' => [
                'status' => $this->contestStateService->getCurrent()->getStatus()->value,
            ],
            'tickets' => [
                'total' => $totalTickets,
                'used' => $used,
                'available' => $available,
            ],
            'prizes' => $prizeBreakdown,
            'users' => [
                'total' => $usersCount,
                'marketingOptIn' => $optInCount,
                'optInPercent' => $usersCount > 0 ? round(100 * $optInCount / $usersCount, 2) : 0,
            ],
            'participations' => (int) $this->participations->count([]),
            'claims' => [
                'pending' => $pendingClaims,
                'delivered' => $deliveredClaims,
            ],
        ];
    }
}
