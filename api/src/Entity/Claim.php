<?php

namespace App\Entity;

use App\Enum\ClaimChannel;
use App\Enum\ClaimStatus;
use App\Repository\ClaimRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClaimRepository::class)]
#[ORM\Table(name: 'claim')]
#[ORM\UniqueConstraint(name: 'uniq_claim_participation', columns: ['participation_id'])]
#[ORM\Index(name: 'idx_claim_status', columns: ['status'])]
class Claim
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'participation_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Participation $participation = null;

    #[ORM\ManyToOne(targetEntity: GrandPrizeDraw::class)]
    #[ORM\JoinColumn(name: 'grand_prize_draw_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GrandPrizeDraw $grandPrizeDraw = null;

    #[ORM\Column(length: 20, enumType: ClaimChannel::class)]
    private ClaimChannel $channel = ClaimChannel::STORE;

    #[ORM\Column(length: 20, enumType: ClaimStatus::class)]
    private ClaimStatus $status = ClaimStatus::PENDING;

    #[ORM\Column(name: 'delivered_at', nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'delivered_by_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $deliveredBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipation(): ?Participation
    {
        return $this->participation;
    }

    public function setParticipation(?Participation $participation): self
    {
        $this->participation = $participation;

        return $this;
    }

    public function getGrandPrizeDraw(): ?GrandPrizeDraw
    {
        return $this->grandPrizeDraw;
    }

    public function setGrandPrizeDraw(?GrandPrizeDraw $grandPrizeDraw): self
    {
        $this->grandPrizeDraw = $grandPrizeDraw;

        return $this;
    }

    public function getChannel(): ClaimChannel
    {
        return $this->channel;
    }

    public function setChannel(ClaimChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getStatus(): ClaimStatus
    {
        return $this->status;
    }

    public function setStatus(ClaimStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    public function getDeliveredBy(): ?User
    {
        return $this->deliveredBy;
    }

    public function setDeliveredBy(?User $deliveredBy): self
    {
        $this->deliveredBy = $deliveredBy;

        return $this;
    }
}
