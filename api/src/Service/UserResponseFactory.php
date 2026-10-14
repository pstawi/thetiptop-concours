<?php

namespace App\Service;

use App\Entity\User;

final class UserResponseFactory
{
    /** @return array<string, mixed> */
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'birthdate' => $user->getBirthdate()?->format('Y-m-d'),
            'gender' => $user->getGender(),
            'address' => $user->getAddress(),
            'marketingOptIn' => $user->isMarketingOptIn(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
