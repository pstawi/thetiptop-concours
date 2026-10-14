<?php

namespace App\Entity;

use App\Repository\GrandPrizeDrawRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrandPrizeDrawRepository::class)]
#[ORM\Table(name: 'grand_prize_draw')]
class GrandPrizeDraw
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'winner_user_id', referencedColumnName: 'id', nullable: false)]
    private User $winner;

    #[ORM\Column(name: 'drawn_at')]
    private \DateTimeImmutable $drawnAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'drawn_by_user_id', referencedColumnName: 'id', nullable: false)]
    private User $drawnBy;

    #[ORM\Column(name: 'seed_hash', length: 128)]
    private string $seedHash;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'audit_payload', type: Types::JSON)]
    private array $auditPayload = [];

    #[ORM\Column(name: 'eligible_count')]
    private int $eligibleCount;

    public function __construct()
    {
        $this->drawnAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWinner(): User
    {
        return $this->winner;
    }

    public function setWinner(User $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    public function getDrawnAt(): \DateTimeImmutable
    {
        return $this->drawnAt;
    }

    public function setDrawnAt(\DateTimeImmutable $drawnAt): self
    {
        $this->drawnAt = $drawnAt;

        return $this;
    }

    public function getDrawnBy(): User
    {
        return $this->drawnBy;
    }

    public function setDrawnBy(User $drawnBy): self
    {
        $this->drawnBy = $drawnBy;

        return $this;
    }

    public function getSeedHash(): string
    {
        return $this->seedHash;
    }

    public function setSeedHash(string $seedHash): self
    {
        $this->seedHash = $seedHash;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getAuditPayload(): array
    {
        return $this->auditPayload;
    }

    /** @param array<string, mixed> $auditPayload */
    public function setAuditPayload(array $auditPayload): self
    {
        $this->auditPayload = $auditPayload;

        return $this;
    }

    public function getEligibleCount(): int
    {
        return $this->eligibleCount;
    }

    public function setEligibleCount(int $eligibleCount): self
    {
        $this->eligibleCount = $eligibleCount;

        return $this;
    }
}
