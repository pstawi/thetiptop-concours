<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Claim;
use App\Entity\Participation;
use App\Entity\TicketCode;
use App\Entity\User;
use App\Enum\ClaimChannel;
use App\Enum\ClaimStatus;
use App\Enum\ContestStatus;
use App\Enum\TicketStatus;
use App\Http\ApiException;
use App\Repository\TicketCodeRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RedeemService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TicketCodeRepository $ticketCodes,
        private readonly ContestStateService $contestStateService,
        private readonly RedeemRateLimiter $rateLimiter,
    ) {
    }

    /**
     * @return array{
     *   code: string,
     *   prize: array{code: string, label: string},
     *   claim: array{id: int|null, status: string, channel: string},
     *   participatedAt: string
     * }
     */
    public function redeem(User $user, string $rawCode, ?string $ip, ?string $userAgent): array
    {
        if (!\in_array('ROLE_USER', $user->getRoles(), true)) {
            throw ApiException::forbidden('Seuls les participants peuvent valider un code.');
        }

        $this->rateLimiter->assertAllowed($user, $ip);

        $code = strtoupper(trim($rawCode));
        if (!preg_match('/^[A-Z0-9]{10}$/', $code)) {
            throw ApiException::codeInvalid();
        }

        $contest = $this->contestStateService->getCurrent();
        if (ContestStatus::OPEN !== $contest->getStatus()) {
            throw ApiException::contestNotOpen();
        }

        $this->em->beginTransaction();
        try {
            /** @var TicketCode|null $ticket */
            $ticket = $this->ticketCodes->createQueryBuilder('t')
                ->andWhere('t.code = :code')
                ->setParameter('code', $code)
                ->getQuery()
                ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$ticket) {
                throw ApiException::codeInvalid('Code ticket inconnu.');
            }

            if (TicketStatus::USED === $ticket->getStatus()) {
                throw ApiException::codeAlreadyUsed();
            }

            $now = new \DateTimeImmutable();
            $ticket
                ->setStatus(TicketStatus::USED)
                ->setUsedAt($now)
                ->setUsedBy($user);

            $participation = (new Participation())
                ->setUser($user)
                ->setTicketCode($ticket)
                ->setIp($ip)
                ->setUserAgent($userAgent);

            $claim = (new Claim())
                ->setParticipation($participation)
                ->setChannel(ClaimChannel::STORE)
                ->setStatus(ClaimStatus::PENDING);

            $audit = (new AuditLog())
                ->setActor($user)
                ->setAction('redeem')
                ->setEntity('ticket_code')
                ->setEntityId((string) $ticket->getId())
                ->setPayloadJson([
                    'code' => $code,
                    'prize' => $ticket->getPrizeType()->getCode(),
                ]);

            $this->em->persist($participation);
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

        $prize = $ticket->getPrizeType();

        return [
            'code' => $ticket->getCode(),
            'prize' => [
                'code' => $prize->getCode(),
                'label' => $prize->getLabel(),
            ],
            'claim' => [
                'id' => $claim->getId(),
                'status' => $claim->getStatus()->value,
                'channel' => $claim->getChannel()->value,
            ],
            'participatedAt' => $participation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'instructions' => 'Présentez-vous en boutique Thé Tip Top pour retirer votre lot (remise en magasin uniquement).',
        ];
    }
}
