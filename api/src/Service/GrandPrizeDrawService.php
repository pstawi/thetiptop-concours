<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Claim;
use App\Entity\GrandPrizeDraw;
use App\Entity\User;
use App\Enum\ClaimChannel;
use App\Enum\ClaimStatus;
use App\Enum\ContestStatus;
use App\Http\ApiException;
use App\Repository\GrandPrizeDrawRepository;
use Doctrine\ORM\EntityManagerInterface;

final class GrandPrizeDrawService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GrandPrizeDrawRepository $draws,
        private readonly ContestStateService $contestStateService,
        private readonly AppMailer $mailer,
    ) {
    }

    /** @return array<string, mixed> */
    public function draw(User $admin): array
    {
        if (!\in_array('ROLE_ADMIN', $admin->getRoles(), true)) {
            throw ApiException::forbidden('Compte admin requis.');
        }

        $status = $this->contestStateService->getCurrent()->getStatus();
        if (ContestStatus::CLOSED !== $status) {
            throw ApiException::contestPhaseInvalid('Le tirage n\'est possible qu\'après passage en CLOSED.');
        }

        if ($this->draws->count([]) > 0) {
            throw ApiException::drawAlreadyDone();
        }

        /** @var list<int> $eligibleIds */
        $eligibleIds = $this->em->createQueryBuilder()
            ->select('DISTINCT u.id')
            ->from(User::class, 'u')
            ->innerJoin(\App\Entity\Participation::class, 'p', 'WITH', 'p.user = u')
            ->getQuery()
            ->getSingleColumnResult();

        $eligibleIds = array_map('intval', $eligibleIds);
        $eligibleCount = \count($eligibleIds);
        if (0 === $eligibleCount) {
            throw ApiException::validation('Aucun participant éligible pour le tirage.');
        }

        $seed = random_bytes(32);
        $seedHash = hash('sha256', $seed);
        $index = random_int(0, $eligibleCount - 1);
        $winnerId = $eligibleIds[$index];
        /** @var User $winner */
        $winner = $this->em->getRepository(User::class)->find($winnerId);
        if (!$winner) {
            throw ApiException::validation('Gagnant introuvable.');
        }

        $this->em->beginTransaction();
        try {
            // Re-check uniqueness inside transaction
            if ($this->draws->count([]) > 0) {
                throw ApiException::drawAlreadyDone();
            }

            $auditPayload = [
                'eligibleCount' => $eligibleCount,
                'winnerUserId' => $winner->getId(),
                'winnerEmail' => $winner->getEmail(),
                'drawnIndex' => $index,
            ];

            $draw = (new GrandPrizeDraw())
                ->setWinner($winner)
                ->setDrawnBy($admin)
                ->setSeedHash($seedHash)
                ->setEligibleCount($eligibleCount)
                ->setAuditPayload($auditPayload)
                ->setDrawnAt(new \DateTimeImmutable());

            $claim = (new Claim())
                ->setGrandPrizeDraw($draw)
                ->setChannel(ClaimChannel::STORE)
                ->setStatus(ClaimStatus::PENDING);

            $audit = (new AuditLog())
                ->setActor($admin)
                ->setAction('grand_prize_draw')
                ->setEntity('grand_prize_draw')
                ->setPayloadJson($auditPayload);

            $this->em->persist($draw);
            $this->em->persist($claim);
            $this->em->persist($audit);
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            throw $e;
        }

        $this->mailer->sendGrandPrizeWinner($winner);

        return [
            'drawId' => $draw->getId(),
            'eligibleCount' => $eligibleCount,
            'winner' => [
                'id' => $winner->getId(),
                'email' => $winner->getEmail(),
                'firstName' => $winner->getFirstName(),
                'lastName' => $winner->getLastName(),
            ],
            'claimId' => $claim->getId(),
            'drawnAt' => $draw->getDrawnAt()->format(\DateTimeInterface::ATOM),
            'seedHash' => $seedHash,
        ];
    }
}
