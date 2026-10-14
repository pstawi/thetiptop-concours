<?php

namespace App\Entity;

use App\Enum\ContestStatus;
use App\Repository\ContestStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestStateRepository::class)]
#[ORM\Table(name: 'contest_state')]
class ContestState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: ContestStatus::class)]
    private ContestStatus $status = ContestStatus::PREVIEW;

    #[ORM\Column(name: 'open_at', nullable: true)]
    private ?\DateTimeImmutable $openAt = null;

    #[ORM\Column(name: 'claim_at', nullable: true)]
    private ?\DateTimeImmutable $claimAt = null;

    #[ORM\Column(name: 'closed_at', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ContestStatus
    {
        return $this->status;
    }

    public function setStatus(ContestStatus $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOpenAt(): ?\DateTimeImmutable
    {
        return $this->openAt;
    }

    public function setOpenAt(?\DateTimeImmutable $openAt): self
    {
        $this->openAt = $openAt;

        return $this;
    }

    public function getClaimAt(): ?\DateTimeImmutable
    {
        return $this->claimAt;
    }

    public function setClaimAt(?\DateTimeImmutable $claimAt): self
    {
        $this->claimAt = $claimAt;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
