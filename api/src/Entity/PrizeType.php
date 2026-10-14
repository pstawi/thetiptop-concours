<?php

namespace App\Entity;

use App\Repository\PrizeTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrizeTypeRepository::class)]
#[ORM\Table(name: 'prize_type')]
#[ORM\UniqueConstraint(name: 'uniq_prize_type_code', columns: ['code'])]
class PrizeType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $code;

    #[ORM\Column(length: 150)]
    private string $label;

    #[ORM\Column]
    private int $percent;

    #[ORM\Column(name: 'stock_target')]
    private int $stockTarget;

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
        $this->code = $code;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getPercent(): int
    {
        return $this->percent;
    }

    public function setPercent(int $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    public function getStockTarget(): int
    {
        return $this->stockTarget;
    }

    public function setStockTarget(int $stockTarget): self
    {
        $this->stockTarget = $stockTarget;

        return $this;
    }
}
