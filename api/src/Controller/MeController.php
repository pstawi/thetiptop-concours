<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiException;
use App\Service\UserResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MeController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly UserResponseFactory $userResponseFactory,
    ) {
    }

    #[Route('/api/v1/me', name: 'api_v1_me_get', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return new JsonResponse($this->userResponseFactory->toArray($this->currentUser()));
    }

    #[Route('/api/v1/me', name: 'api_v1_me_patch', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        try {
            /** @var mixed $payload */
            $payload = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiException::validation('JSON invalide.');
        }

        if (!\is_array($payload)) {
            throw ApiException::validation('JSON invalide.');
        }

        if (\array_key_exists('firstName', $payload)) {
            $firstName = trim((string) $payload['firstName']);
            if ('' === $firstName) {
                throw ApiException::validation('Le prénom est obligatoire.');
            }
            $user->setFirstName($firstName);
        }

        if (\array_key_exists('lastName', $payload)) {
            $lastName = trim((string) $payload['lastName']);
            if ('' === $lastName) {
                throw ApiException::validation('Le nom est obligatoire.');
            }
            $user->setLastName($lastName);
        }

        if (\array_key_exists('marketingOptIn', $payload)) {
            $user->setMarketingOptIn((bool) $payload['marketingOptIn']);
        }

        if (\array_key_exists('gender', $payload)) {
            $gender = $payload['gender'];
            $user->setGender(null === $gender || '' === $gender ? null : (string) $gender);
        }

        if (\array_key_exists('address', $payload)) {
            $address = $payload['address'];
            $user->setAddress(null === $address || '' === $address ? null : (string) $address);
        }

        if (\array_key_exists('birthdate', $payload)) {
            $birthdate = $payload['birthdate'];
            if (null === $birthdate || '' === $birthdate) {
                $user->setBirthdate(null);
            } else {
                $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $birthdate);
                if (!$parsed || $parsed->format('Y-m-d') !== (string) $birthdate) {
                    throw ApiException::validation('Date de naissance invalide (format Y-m-d).');
                }
                $user->setBirthdate($parsed);
            }
        }

        $this->em->flush();

        return new JsonResponse($this->userResponseFactory->toArray($user));
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw ApiException::unauthorized();
        }

        return $user;
    }
}
