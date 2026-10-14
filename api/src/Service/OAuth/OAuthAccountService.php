<?php

namespace App\Service\OAuth;

use App\Entity\User;
use App\Http\ApiException;
use App\Repository\UserRepository;
use App\Service\AppMailer;
use Doctrine\ORM\EntityManagerInterface;

final class OAuthAccountService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly AppMailer $mailer,
    ) {
    }

    /**
     * @param array{provider: string, subject: string, email: string, firstName: string, lastName: string} $profile
     */
    public function upsertFromProfile(array $profile): User
    {
        $provider = $profile['provider'];
        $subject = $profile['subject'];
        $email = strtolower(trim($profile['email']));

        if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ApiException::validation('Email OAuth manquant ou invalide.');
        }
        if ('' === $subject) {
            throw ApiException::validation('Identifiant OAuth manquant.');
        }

        $user = $this->users->findOneBy([
            'oauthProvider' => $provider,
            'oauthSubject' => $subject,
        ]);

        if (!$user) {
            $user = $this->users->findOneBy(['email' => $email]);
        }

        $isNew = false;
        if (!$user) {
            $isNew = true;
            $user = (new User())
                ->setEmail($email)
                ->setFirstName($profile['firstName'] !== '' ? $profile['firstName'] : 'Prénom')
                ->setLastName($profile['lastName'] !== '' ? $profile['lastName'] : 'Nom')
                ->setRoles(['ROLE_USER'])
                ->setMarketingOptIn(false);
            $this->em->persist($user);
        } else {
            if ('' !== $profile['firstName']) {
                $user->setFirstName($profile['firstName']);
            }
            if ('' !== $profile['lastName']) {
                $user->setLastName($profile['lastName']);
            }
        }

        $user
            ->setOauthProvider($provider)
            ->setOauthSubject($subject);

        $this->em->flush();

        if ($isNew) {
            $this->mailer->sendWelcome($user);
        }

        return $user;
    }
}
