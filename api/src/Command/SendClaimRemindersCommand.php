<?php

namespace App\Command;

use App\Enum\ClaimStatus;
use App\Enum\ContestStatus;
use App\Repository\ClaimRepository;
use App\Service\AppMailer;
use App\Service\ContestStateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:emails:claim-reminders',
    description: 'Envoie les rappels de réclamation (phase CLAIM, claims PENDING)'
)]
final class SendClaimRemindersCommand extends Command
{
    public function __construct(
        private readonly ContestStateService $contestStateService,
        private readonly ClaimRepository $claims,
        private readonly AppMailer $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $status = $this->contestStateService->getCurrent()->getStatus();
        if (ContestStatus::CLAIM !== $status) {
            $io->warning('Phase actuelle != CLAIM — aucun envoi.');

            return Command::SUCCESS;
        }

        $pending = $this->claims->findBy(['status' => ClaimStatus::PENDING]);
        $sent = 0;
        foreach ($pending as $claim) {
            $participation = $claim->getParticipation();
            if (!$participation) {
                // gros lot : pas de prize ticket
                $user = $claim->getGrandPrizeDraw()?->getWinner();
                if ($user) {
                    $this->mailer->sendClaimReminder($user, 'Gros lot 360 €');
                    ++$sent;
                }
                continue;
            }

            $user = $participation->getUser();
            $label = $participation->getTicketCode()->getPrizeType()->getLabel();
            $this->mailer->sendClaimReminder($user, $label);
            ++$sent;
        }

        $io->success(sprintf('%d rappel(s) traités.', $sent));

        return Command::SUCCESS;
    }
}
