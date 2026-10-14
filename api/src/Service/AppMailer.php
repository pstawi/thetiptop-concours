<?php

namespace App\Service;

use App\Entity\EmailOutbox;
use App\Entity\User;
use App\Enum\EmailOutboxStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class AppMailer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function enqueueAndSend(string $toEmail, string $template, array $payload = []): EmailOutbox
    {
        $outbox = (new EmailOutbox())
            ->setToEmail($toEmail)
            ->setTemplate($template)
            ->setPayloadJson($payload)
            ->setStatus(EmailOutboxStatus::PENDING);

        $this->em->persist($outbox);
        $this->em->flush();

        try {
            $subject = match ($template) {
                'welcome' => 'Bienvenue au jeu-concours Thé Tip Top',
                'claim_reminder' => 'Rappel : retirez votre lot en boutique',
                'grand_prize_winner' => 'Félicitations : vous avez gagné le gros lot !',
                default => 'Notification Thé Tip Top',
            };

            $html = $this->twig->render('emails/'.$template.'.html.twig', $payload);
            $email = (new Email())
                ->from('noreply@thetiptop.local')
                ->to($toEmail)
                ->subject($subject)
                ->html($html);

            $this->mailer->send($email);
            $outbox->setStatus(EmailOutboxStatus::SENT);
            $outbox->setSentAt(new \DateTimeImmutable());
        } catch (\Throwable $e) {
            $outbox->setStatus(EmailOutboxStatus::FAILED);
            $payload['error'] = $e->getMessage();
            $outbox->setPayloadJson($payload);
        }

        $this->em->flush();

        return $outbox;
    }

    public function sendWelcome(User $user): void
    {
        $this->enqueueAndSend($user->getEmail(), 'welcome', [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ]);
    }

    public function sendGrandPrizeWinner(User $user): void
    {
        $this->enqueueAndSend($user->getEmail(), 'grand_prize_winner', [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ]);
    }

    public function sendClaimReminder(User $user, string $prizeLabel): void
    {
        $this->enqueueAndSend($user->getEmail(), 'claim_reminder', [
            'firstName' => $user->getFirstName(),
            'prizeLabel' => $prizeLabel,
        ]);
    }
}
