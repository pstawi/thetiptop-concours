<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiException;
use App\Repository\UserRepository;
use App\Service\PasswordPolicy;
use App\Service\UserResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserResponseFactory $userResponseFactory,
        private readonly PasswordPolicy $passwordPolicy,
    ) {
    }

    #[Route('/api/v1/auth/register', name: 'api_v1_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $firstName = trim((string) ($payload['firstName'] ?? ''));
        $lastName = trim((string) ($payload['lastName'] ?? ''));
        $marketingOptIn = (bool) ($payload['marketingOptIn'] ?? false);
        $acceptCgu = (bool) ($payload['acceptCgu'] ?? false);

        if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ApiException::validation('Email invalide.');
        }
        if ('' === $firstName || '' === $lastName) {
            throw ApiException::validation('Le prénom et le nom sont obligatoires.');
        }
        if (!$acceptCgu) {
            throw ApiException::validation('Les CGU doivent être acceptées.');
        }

        $this->passwordPolicy->assertValid($password);

        if ($this->users->findOneBy(['email' => strtolower($email)])) {
            throw ApiException::validation('Cet email est déjà utilisé.');
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setMarketingOptIn($marketingOptIn)
            ->setRoles(['ROLE_USER']);

        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'user' => $this->userResponseFactory->toArray($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/v1/auth/login', name: 'api_v1_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ('' === $email || '' === $password) {
            throw ApiException::validation('Email et mot de passe requis.');
        }

        $user = $this->users->findOneBy(['email' => $email]);
        if (!$user || null === $user->getPassword() || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw ApiException::unauthorized('Identifiants invalides.');
        }

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'user' => $this->userResponseFactory->toArray($user),
        ]);
    }

    /** @return array<string, mixed> */
    private function decodeJson(Request $request): array
    {
        try {
            /** @var mixed $data */
            $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiException::validation('JSON invalide.');
        }

        if (!\is_array($data)) {
            throw ApiException::validation('JSON invalide.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
