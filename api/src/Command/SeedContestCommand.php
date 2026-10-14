<?php

namespace App\Command;

use App\Entity\AuditLog;
use App\Entity\ContestState;
use App\Entity\PrizeType;
use App\Entity\User;
use App\Enum\ContestStatus;
use App\Enum\TicketStatus;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

#[AsCommand(
    name: 'app:seed-contest',
    description: 'Seed prize types, 500000 ticket codes, contest state and demo users'
)]
final class SeedContestCommand extends Command
{
    private const TOTAL_TICKETS = 500_000;
    private const CODE_LENGTH = 10;
    private const CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const BATCH_SIZE = 2_000;

    /** @var list<array{code: string, label: string, percent: int, stock_target: int}> */
    private const PRIZE_TYPES = [
        ['code' => 'INFUSEUR', 'label' => 'Infuseur à thé', 'percent' => 60, 'stock_target' => 300_000],
        ['code' => 'DETOX_100', 'label' => '100 g détox / infusion', 'percent' => 20, 'stock_target' => 100_000],
        ['code' => 'SIGNATURE_100', 'label' => '100 g signature', 'percent' => 10, 'stock_target' => 50_000],
        ['code' => 'COFFRET_39', 'label' => 'Coffret 39 €', 'percent' => 6, 'stock_target' => 30_000],
        ['code' => 'COFFRET_69', 'label' => 'Coffret 69 €', 'percent' => 4, 'stock_target' => 20_000],
    ];

    /** @var list<array{email: string, password: string, firstName: string, lastName: string, roles: list<string>}> */
    private const DEMO_USERS = [
        [
            'email' => 'admin@thetiptop.local',
            'password' => 'Admin123!',
            'firstName' => 'Ada',
            'lastName' => 'Admin',
            'roles' => ['ROLE_ADMIN'],
        ],
        [
            'email' => 'shop@thetiptop.local',
            'password' => 'Shop123!',
            'firstName' => 'Sam',
            'lastName' => 'Boutique',
            'roles' => ['ROLE_SHOP'],
        ],
        [
            'email' => 'user@thetiptop.local',
            'password' => 'User123!',
            'firstName' => 'Léa',
            'lastName' => 'Martin',
            'roles' => ['ROLE_USER'],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Supprime tickets / lots / état concours et re-seed (interdit en prod sans procédure)')
            ->addOption('tickets', null, InputOption::VALUE_REQUIRED, 'Nombre de tickets (défaut 500000, tests locaux uniquement)', (string) self::TOTAL_TICKETS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startedAt = microtime(true);
        $force = (bool) $input->getOption('force');
        $ticketTotal = (int) $input->getOption('tickets');

        if ($ticketTotal <= 0) {
            $io->error('L\'option --tickets doit être un entier positif.');

            return Command::FAILURE;
        }

        if (($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev') === 'prod' && $force) {
            $io->error('--force est refusé quand APP_ENV=prod.');

            return Command::FAILURE;
        }

        $prizePlan = $this->buildPrizePlan($ticketTotal);
        if (null === $prizePlan) {
            $io->error('Impossible de répartir les tickets avec les pourcentages CDC pour ce total.');

            return Command::FAILURE;
        }

        $existingTickets = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ticket_code');
        if ($existingTickets > 0 && !$force) {
            $io->error(sprintf(
                'Des tickets existent déjà (%d). Relancer avec --force pour écraser (hors prod).',
                $existingTickets
            ));

            return Command::FAILURE;
        }

        if ($force && $existingTickets > 0) {
            $io->warning('Option --force : purge des tickets, lots et état concours…');
            $this->purgeContestData();
        }

        $io->title('Seed concours Thé Tip Top');
        $io->writeln(sprintf('Tickets ciblés : %d', $ticketTotal));

        $prizeTypes = $this->seedPrizeTypes($prizePlan);
        $this->seedContestState();
        $this->seedDemoUsers();
        $this->seedTicketCodes($io, $prizeTypes, $prizePlan);
        $this->assertIntegrity($io, $prizePlan, $ticketTotal);

        $elapsed = microtime(true) - $startedAt;
        $io->success(sprintf(
            'Seed terminé en %.1f s (%.1f min) — %d tickets, intégrité OK.',
            $elapsed,
            $elapsed / 60,
            $ticketTotal
        ));
        $io->listing([
            'admin@thetiptop.local / Admin123!',
            'shop@thetiptop.local / Shop123!',
            'user@thetiptop.local / User123!',
        ]);

        $audit = (new AuditLog())
            ->setAction('seed_contest')
            ->setEntity('contest')
            ->setEntityId('seed')
            ->setPayloadJson([
                'tickets' => $ticketTotal,
                'elapsed_seconds' => round($elapsed, 2),
                'force' => $force,
            ]);
        $this->em->persist($audit);
        $this->em->flush();

        return Command::SUCCESS;
    }

    /**
     * @return list<array{code: string, label: string, percent: int, stock_target: int}>|null
     */
    private function buildPrizePlan(int $ticketTotal): ?array
    {
        if ($ticketTotal === self::TOTAL_TICKETS) {
            return self::PRIZE_TYPES;
        }

        // Répartition proportionnelle exacte uniquement si divisible selon les % CDC.
        $plan = [];
        $sum = 0;
        foreach (self::PRIZE_TYPES as $index => $prize) {
            $stock = (int) round($ticketTotal * ($prize['percent'] / 100));
            if ($index === \count(self::PRIZE_TYPES) - 1) {
                $stock = $ticketTotal - $sum;
            }
            $sum += $stock;
            $plan[] = [
                'code' => $prize['code'],
                'label' => $prize['label'],
                'percent' => $prize['percent'],
                'stock_target' => $stock,
            ];
        }

        if ($sum !== $ticketTotal) {
            return null;
        }

        return $plan;
    }

    private function purgeContestData(): void
    {
        $this->connection->executeStatement('DELETE FROM claim');
        $this->connection->executeStatement('DELETE FROM participation');
        $this->connection->executeStatement('DELETE FROM ticket_code');
        $this->connection->executeStatement('DELETE FROM grand_prize_draw');
        $this->connection->executeStatement('DELETE FROM contest_state');
        $this->connection->executeStatement('DELETE FROM prize_type');
        $this->em->clear();
    }

    /**
     * @param list<array{code: string, label: string, percent: int, stock_target: int}> $prizePlan
     *
     * @return array<string, PrizeType>
     */
    private function seedPrizeTypes(array $prizePlan): array
    {
        $map = [];
        foreach ($prizePlan as $definition) {
            $existing = $this->em->getRepository(PrizeType::class)->findOneBy(['code' => $definition['code']]);
            $prize = $existing ?? new PrizeType();
            $prize
                ->setCode($definition['code'])
                ->setLabel($definition['label'])
                ->setPercent($definition['percent'])
                ->setStockTarget($definition['stock_target']);
            $this->em->persist($prize);
            $map[$definition['code']] = $prize;
        }
        $this->em->flush();

        return $map;
    }

    private function seedContestState(): void
    {
        $state = $this->em->getRepository(ContestState::class)->findOneBy([]);
        if (!$state) {
            $state = new ContestState();
            $this->em->persist($state);
        }

        $default = strtoupper((string) ($_ENV['APP_CONTEST_DEFAULT_STATUS'] ?? $_SERVER['APP_CONTEST_DEFAULT_STATUS'] ?? 'PREVIEW'));
        $status = ContestStatus::tryFrom($default) ?? ContestStatus::PREVIEW;
        $state->setStatus($status);
        $this->em->flush();
    }

    private function seedDemoUsers(): void
    {
        $factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'auto'],
        ]);
        $hasher = $factory->getPasswordHasher('common');

        foreach (self::DEMO_USERS as $definition) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $definition['email']]);
            if (!$user) {
                $user = new User();
                $user->setEmail($definition['email']);
                $this->em->persist($user);
            }

            $user
                ->setFirstName($definition['firstName'])
                ->setLastName($definition['lastName'])
                ->setRoles($definition['roles'])
                ->setPasswordHash($hasher->hash($definition['password']))
                ->setMarketingOptIn(false);
        }

        $this->em->flush();
    }

    /**
     * @param array<string, PrizeType> $prizeTypes
     * @param list<array{code: string, label: string, percent: int, stock_target: int}> $prizePlan
     */
    private function seedTicketCodes(SymfonyStyle $io, array $prizeTypes, array $prizePlan): void
    {
        $io->section('Génération des codes tickets');

        $total = 0;
        foreach ($prizePlan as $definition) {
            $total += $definition['stock_target'];
        }

        $io->progressStart($total);
        $used = [];
        $status = TicketStatus::AVAILABLE->value;

        foreach ($prizePlan as $definition) {
            $prizeId = $prizeTypes[$definition['code']]->getId();
            if (null === $prizeId) {
                throw new \RuntimeException(sprintf('PrizeType %s sans id après flush.', $definition['code']));
            }

            $remaining = $definition['stock_target'];
            while ($remaining > 0) {
                $batchSize = min(self::BATCH_SIZE, $remaining);
                $rows = [];
                $params = [];

                for ($i = 0; $i < $batchSize; ++$i) {
                    do {
                        $code = $this->generateCode();
                    } while (isset($used[$code]));
                    $used[$code] = true;

                    $rows[] = '(?, ?, ?)';
                    $params[] = $code;
                    $params[] = $prizeId;
                    $params[] = $status;
                }

                $sql = 'INSERT INTO ticket_code (code, prize_type_id, status) VALUES '.implode(', ', $rows);
                $this->connection->executeStatement($sql, $params);

                $remaining -= $batchSize;
                $io->progressAdvance($batchSize);
            }
        }

        $io->progressFinish();
        unset($used);
    }

    private function generateCode(): string
    {
        $alphabet = self::CODE_ALPHABET;
        $max = \strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; ++$i) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * @param list<array{code: string, label: string, percent: int, stock_target: int}> $prizePlan
     */
    private function assertIntegrity(SymfonyStyle $io, array $prizePlan, int $ticketTotal): void
    {
        $io->section('Contrôle d\'intégrité');

        $total = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ticket_code');
        if ($total !== $ticketTotal) {
            throw new \RuntimeException(sprintf(
                'Intégrité échouée : total tickets=%d attendu=%d',
                $total,
                $ticketTotal
            ));
        }

        $percentSum = 0;
        foreach ($prizePlan as $definition) {
            $percentSum += $definition['percent'];
            $count = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM ticket_code tc INNER JOIN prize_type pt ON pt.id = tc.prize_type_id WHERE pt.code = ?',
                [$definition['code']]
            );
            if ($count !== $definition['stock_target']) {
                throw new \RuntimeException(sprintf(
                    'Intégrité échouée pour %s : count=%d stock_target=%d',
                    $definition['code'],
                    $count,
                    $definition['stock_target']
                ));
            }
            $io->writeln(sprintf(
                '  %s : %d / %d (%d%%)',
                $definition['code'],
                $count,
                $definition['stock_target'],
                $definition['percent']
            ));
        }

        if (100 !== $percentSum) {
            throw new \RuntimeException(sprintf('Somme des pourcentages invalide : %d', $percentSum));
        }

        $dupes = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (SELECT code FROM ticket_code GROUP BY code HAVING COUNT(*) > 1) d'
        );
        if ($dupes > 0) {
            throw new \RuntimeException(sprintf('Codes en double détectés : %d', $dupes));
        }
    }
}
