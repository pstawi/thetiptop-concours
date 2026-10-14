<?php

namespace App\Entity;

use App\Enum\EmailOutboxStatus;
use App\Repository\EmailOutboxRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailOutboxRepository::class)]
#[ORM\Table(name: 'email_outbox')]
#[ORM\Index(name: 'idx_email_outbox_status', columns: ['status'])]
class EmailOutbox
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'to_email', length: 180)]
    private string $toEmail;

    #[ORM\Column(length: 80)]
    private string $template;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'payload_json', type: Types::JSON)]
    private array $payloadJson = [];

    #[ORM\Column(length: 20, enumType: EmailOutboxStatus::class)]
    private EmailOutboxStatus $status = EmailOutboxStatus::PENDING;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'sent_at', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    public function setToEmail(string $toEmail): self
    {
        $this->toEmail = strtolower(trim($toEmail));

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPayloadJson(): array
    {
        return $this->payloadJson;
    }

    /** @param array<string, mixed> $payloadJson */
    public function setPayloadJson(array $payloadJson): self
    {
        $this->payloadJson = $payloadJson;

        return $this;
    }

    public function getStatus(): EmailOutboxStatus
    {
        return $this->status;
    }

    public function setStatus(EmailOutboxStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
