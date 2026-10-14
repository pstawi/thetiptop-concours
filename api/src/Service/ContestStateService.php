<?php

namespace App\Service;

use App\Entity\ContestState;
use App\Enum\ContestStatus;
use App\Repository\ContestStateRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ContestStateService
{
    public function __construct(
        private readonly ContestStateRepository $contestStates,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getCurrent(): ContestState
    {
        $state = $this->contestStates->findOneBy([]);
        if (!$state) {
            $state = new ContestState();
            $state->setStatus(ContestStatus::PREVIEW);
            $this->em->persist($state);
            $this->em->flush();
        }

        return $state;
    }

    public function setStatus(ContestStatus $status): ContestState
    {
        $state = $this->getCurrent();
        $now = new \DateTimeImmutable();
        $state->setStatus($status);

        match ($status) {
            ContestStatus::OPEN => $state->setOpenAt($state->getOpenAt() ?? $now),
            ContestStatus::CLAIM => $state->setClaimAt($state->getClaimAt() ?? $now),
            ContestStatus::CLOSED => $state->setClosedAt($state->getClosedAt() ?? $now),
            ContestStatus::PREVIEW => null,
        };

        $this->em->flush();

        return $state;
    }
}
