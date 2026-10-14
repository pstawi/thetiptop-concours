<?php

namespace App\Entity;

use App\Enum\TicketStatus;
use App\Repository\TicketCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketCodeRepository::class)]
#[ORM\Table(name: 'ticket_code')]
#[ORM\UniqueConstraint(name: 'uniq_ticket_code_code', columns: ['code'])]
#[ORM\Index(name: 'idx_ticket_code_status', columns: ['status'])]
#[ORM\Index(name: 'idx_ticket_code_prize_type', columns: ['prize_type_id'])]
class TicketCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $code;

    #[ORM\ManyToOne(targetEntity: PrizeType::class)]
    #[ORM\JoinColumn(name: 'prize_type_id', referencedColumnName: 'id', nullable: false)]
    private PrizeType $prizeType;

    #[ORM\Column(length: 20, enumType: TicketStatus::class)]
    private TicketStatus $status = TicketStatus::AVAILABLE;

    #[ORM\Column(name: 'used_at', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'used_by_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $usedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }

    public function getPrizeType(): PrizeType
    {
        return $this->prizeType;
    }

    public function setPrizeType(PrizeType $prizeType): self
    {
        $this->prizeType = $prizeType;

        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeImmutable $usedAt): self
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function getUsedBy(): ?User
    {
        return $this->usedBy;
    }

    public function setUsedBy(?User $usedBy): self
    {
        $this->usedBy = $usedBy;

        return $this;
    }
}
