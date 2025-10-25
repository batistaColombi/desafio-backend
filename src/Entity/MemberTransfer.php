<?php

namespace App\Entity;

use App\Repository\MemberTransferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: MemberTransferRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Broadcast]
class MemberTransfer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transfers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Member $member = null;

    #[ORM\ManyToOne(inversedBy: 'outgoingTransfers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Church $from_church = null;

    #[ORM\ManyToOne(inversedBy: 'incomingTransfers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Church $to_church = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $transfer_date = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function setMember(?Member $member): static
    {
        $this->member = $member;

        return $this;
    }

    public function getFromChurch(): ?Church
    {
        return $this->from_church;
    }

    public function setFromChurch(?Church $from_church): static
    {
        $this->from_church = $from_church;

        return $this;
    }

    public function getToChurch(): ?Church
    {
        return $this->to_church;
    }

    public function setToChurch(?Church $to_church): static
    {
        $this->to_church = $to_church;

        return $this;
    }

    public function getTransferDate(): ?\DateTimeInterface
    {
        return $this->transfer_date;
    }

    public function setTransferDate(\DateTimeInterface $transfer_date): static
    {
        $this->transfer_date = $transfer_date;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function setCreatedBy(?string $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function validateTransfer(self $transfer): void
    {
        $toChurch = $transfer->getToChurch();

        if (!$toChurch) {
            throw new BadRequestHttpException('Igreja destino não informada.');
        }

        $membersCount = $toChurch->getMembers()->count();

        if (null !== $toChurch->getMembersLimit() && $membersCount >= $toChurch->getMembersLimit()) {
            throw new BadRequestHttpException('A igreja destino atingiu o limite máximo de membros.');
        }
    }
}
