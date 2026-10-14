<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Enum\ContestStatus;
use App\Http\ApiException;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Service\AdminStatsService;
use App\Service\ContestStateService;
use App\Service\GrandPrizeDrawService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController
{
    public function __construct(
        private readonly Security $security,
        private readonly AdminStatsService $statsService,
        private readonly ContestStateService $contestStateService,
        private readonly GrandPrizeDrawService $drawService,
        private readonly UserRepository $users,
        private readonly AuditLogRepository $audits,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/v1/admin/stats', name: 'api_v1_admin_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->requireAdmin();

        return new JsonResponse($this->statsService->getStats());
    }

    #[Route('/api/v1/admin/exports/emails', name: 'api_v1_admin_export_emails', methods: ['GET'])]
    public function exportEmails(): StreamedResponse
    {
        $this->requireAdmin();

        $rows = $this->users->createQueryBuilder('u')
            ->andWhere('u.marketingOptIn = true')
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $response = new StreamedResponse(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'firstName', 'lastName', 'consentDate'], ';');
            /** @var User $user */
            foreach ($rows as $user) {
                fputcsv($out, [
                    $user->getEmail(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                ], ';');
            }
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="optin-emails.csv"');

        return $response;
    }

    #[Route('/api/v1/admin/contest', name: 'api_v1_admin_contest_patch', methods: ['PATCH'])]
    public function patchContest(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();

        try {
            /** @var mixed $payload */
            $payload = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiException::validation('JSON invalide.');
        }

        if (!\is_array($payload) || !isset($payload['status'])) {
            throw ApiException::validation('Champ status requis.');
        }

        $status = ContestStatus::tryFrom(strtoupper((string) $payload['status']));
        if (!$status) {
            throw ApiException::validation('Status invalide (PREVIEW|OPEN|CLAIM|CLOSED).');
        }

        $before = $this->contestStateService->getCurrent()->getStatus()->value;
        $state = $this->contestStateService->setStatus($status);
        $state->setUpdatedBy($admin);
        $this->em->flush();

        $audit = (new AuditLog())
            ->setActor($admin)
            ->setAction('contest_phase_change')
            ->setEntity('contest_state')
            ->setEntityId((string) $state->getId())
            ->setPayloadJson([
                'from' => $before,
                'to' => $state->getStatus()->value,
            ]);
        $this->em->persist($audit);
        $this->em->flush();

        return new JsonResponse([
            'status' => $state->getStatus()->value,
            'openAt' => $state->getOpenAt()?->format(\DateTimeInterface::ATOM),
            'claimAt' => $state->getClaimAt()?->format(\DateTimeInterface::ATOM),
            'closedAt' => $state->getClosedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $state->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/api/v1/admin/draw/grand-prize', name: 'api_v1_admin_draw', methods: ['POST'])]
    public function draw(): JsonResponse
    {
        $admin = $this->requireAdmin();
        $result = $this->drawService->draw($admin);

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/api/v1/admin/audit', name: 'api_v1_admin_audit', methods: ['GET'])]
    public function audit(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $qb = $this->audits->createQueryBuilder('a')
            ->leftJoin('a.actor', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(100);

        $action = trim((string) $request->query->get('action', ''));
        if ('' !== $action) {
            $qb->andWhere('a.action = :action')->setParameter('action', $action);
        }

        /** @var list<AuditLog> $rows */
        $rows = $qb->getQuery()->getResult();
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => $row->getId(),
                'action' => $row->getAction(),
                'entity' => $row->getEntity(),
                'entityId' => $row->getEntityId(),
                'actorEmail' => $row->getActor()?->getEmail(),
                'payload' => $row->getPayloadJson(),
                'createdAt' => $row->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return new JsonResponse(['items' => $items]);
    }

    private function requireAdmin(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw ApiException::unauthorized();
        }
        if (!\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw ApiException::forbidden('Compte admin requis.');
        }

        return $user;
    }
}
