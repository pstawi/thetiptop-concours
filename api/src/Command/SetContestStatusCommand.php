<?php

namespace App\Command;

use App\Enum\ContestStatus;
use App\Service\ContestStateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:contest:set-status',
    description: 'Change la phase du concours (PREVIEW|OPEN|CLAIM|CLOSED) — usage local / admin CLI'
)]
final class SetContestStatusCommand extends Command
{
    public function __construct(
        private readonly ContestStateService $contestStateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('status', InputArgument::REQUIRED, 'PREVIEW|OPEN|CLAIM|CLOSED');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $raw = strtoupper((string) $input->getArgument('status'));
        $status = ContestStatus::tryFrom($raw);
        if (!$status) {
            $io->error('Status invalide. Valeurs : PREVIEW, OPEN, CLAIM, CLOSED');

            return Command::FAILURE;
        }

        $state = $this->contestStateService->setStatus($status);
        $io->success(sprintf('Phase concours = %s', $state->getStatus()->value));

        return Command::SUCCESS;
    }
}
