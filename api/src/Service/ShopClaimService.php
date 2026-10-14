<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Claim;
use App\Entity\User;
use App\Enum\ClaimStatus;
use App\Enum\ContestStatus;
use App\Http\ApiException;
use App\Repository\ClaimRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ShopClaimService
{
    public function __construct(
        private readonly ClaimRepository $claims,
        private readonly ContestStateService $contestStateService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query): array
    {
        $this->assertDeliverPhase();

        $q = trim($query);
        if ('' === $q) {
            throw ApiException::validation('Paramètre q requis (email ou code ticket).');
        }

        $qb = $this->claims->createQueryBuilder('c')
            ->innerJoin('c.participation', 'p')
            ->innerJoin('p.user', 'u')
            ->innerJoin('p.ticketCode', 't')
            ->innerJoin('t.prizeType', 'prize')
            ->addSelect('p', 'u', 't', 'prize')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(50);

        if (str_contains($q, '@')) {
            $qb->andWhere('LOWER(u.email) = :email')
                ->setParameter('email', strtolower($q));
        } else {
            $code = strtoupper($q);
            $qb->andWhere('t.code = :code')
                ->setParameter('code', $code);
        }

        /** @var list<Claim> $rows */
        $rows = $qb->getQuery()->getResult();

        return array_map(fn (Claim $claim) => $this->serialize($claim), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function deliver(User $shopUser, int $claimId): array
    {
        if (!\in_array('ROLE_SHOP', $shopUser->getRoles(), true)) {
            throw ApiException::forbidden('Compte boutique requis.');
        }

        $this->assertDeliverPhase();

        $this->em->beginTransaction();
        try {
            /** @var Claim|null $claim */
            $claim = $this->claims->createQueryBuilder('c')
                ->andWhere('c.id = :id')
                ->setParameter('id', $claimId)
                ->getQuery()
                ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$claim) {
                throw ApiException::notFound('Claim introuvable.');
            }

            if (ClaimStatus::PENDING !== $claim->getStatus()) {
                throw ApiException::claimNotPending();
            }

            $now = new \DateTimeImmutable();
            $claim
                ->setStatus(ClaimStatus::DELIVERED)
                ->setDeliveredAt($now)
                ->setDeliveredBy($shopUser);

            $audit = (new AuditLog())
                ->setActor($shopUser)
                ->setAction('deliver')
                ->setEntity('claim')
                ->setEntityId((string) $claim->getId())
                ->setPayloadJson([
                    'status' => ClaimStatus::DELIVERED->value,
                ]);

            $this->em->persist($audit);
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            throw $e;
        }

        // Recharger relations pour la réponse
        $this->em->refresh($claim);

        return $this->serialize($claim, true);
    }

    private function assertDeliverPhase(): void
    {
        $status = $this->contestStateService->getCurrent()->getStatus();
        if (!\in_array($status, [ContestStatus::OPEN, ContestStatus::CLAIM], true)) {
            throw ApiException::contestPhaseInvalid(
                'La remise boutique n\'est possible qu\'en phases OPEN ou CLAIM.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Claim $claim, bool $withFullCode = false): array
    {
        $participation = $claim->getParticipation();
        if (!$participation) {
            return [
                'id' => $claim->getId(),
                'status' => $claim->getStatus()->value,
                'channel' => $claim->getChannel()->value,
                'type' => 'grand_prize',
                'deliveredAt' => $claim->getDeliveredAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        $user = $participation->getUser();
        $ticket = $participation->getTicketCode();
        $prize = $ticket->getPrizeType();
        $code = $ticket->getCode();
        $masked = str_repeat('X', max(0, \strlen($code) - 4)).substr($code, -4);

        return [
            'id' => $claim->getId(),
            'status' => $claim->getStatus()->value,
            'channel' => $claim->getChannel()->value,
            'type' => 'ticket',
            'participant' => [
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
            'code' => $withFullCode || ClaimStatus::PENDING === $claim->getStatus() ? $code : $masked,
            'codeMasked' => $masked,
            'prize' => [
                'code' => $prize->getCode(),
                'label' => $prize->getLabel(),
            ],
            'participatedAt' => $participation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'deliveredAt' => $claim->getDeliveredAt()?->format(\DateTimeInterface::ATOM),
            'deliveredBy' => $claim->getDeliveredBy()?->getEmail(),
        ];
    }
}
