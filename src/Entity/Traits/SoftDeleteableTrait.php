<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

trait SoftDeleteableTrait
{
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isDeleted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $deletedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $deletedBy = null;

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getDeletedBy(): ?string
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?string $deletedBy): static
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }

    public function softDelete(?string $deletedBy = null): static
    {
        $this->isDeleted = true;
        $this->deletedAt = new \DateTime();
        $this->deletedBy = $deletedBy;
        return $this;
    }

    public function restore(): static
    {
        $this->isDeleted = false;
        $this->deletedAt = null;
        $this->deletedBy = null;
        return $this;
    }
}
