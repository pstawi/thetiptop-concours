<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_app_user_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_app_user_oauth', columns: ['oauth_provider', 'oauth_subject'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(name: 'password_hash', length: 255, nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(name: 'oauth_provider', length: 20, nullable: true)]
    private ?string $oauthProvider = null;

    #[ORM\Column(name: 'oauth_subject', length: 255, nullable: true)]
    private ?string $oauthSubject = null;

    #[ORM\Column(name: 'first_name', length: 100)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', length: 100)]
    private string $lastName;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $birthdate = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(name: 'marketing_opt_in', options: ['default' => false])]
    private bool $marketingOptIn = false;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getOauthProvider(): ?string
    {
        return $this->oauthProvider;
    }

    public function setOauthProvider(?string $oauthProvider): self
    {
        $this->oauthProvider = $oauthProvider;

        return $this;
    }

    public function getOauthSubject(): ?string
    {
        return $this->oauthSubject;
    }

    public function setOauthSubject(?string $oauthSubject): self
    {
        $this->oauthSubject = $oauthSubject;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeImmutable $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function isMarketingOptIn(): bool
    {
        return $this->marketingOptIn;
    }

    public function setMarketingOptIn(bool $marketingOptIn): self
    {
        $this->marketingOptIn = $marketingOptIn;

        return $this;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!\in_array('ROLE_USER', $roles, true) && !\in_array('ROLE_SHOP', $roles, true) && !\in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
