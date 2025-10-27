<?php

namespace App\DTO;

use App\Entity\Church;

class ChurchListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $city,
        public readonly ?string $state,
        public readonly ?int $membersLimit,
        public readonly int $currentMembersCount,
        public readonly string $createdAt
    ) {}

    public static function fromEntity(Church $church): self
    {
        return new self(
            id: $church->getId(),
            name: $church->getName(),
            city: $church->getCity() ?? 'Não informado',
            state: $church->getState(),
            membersLimit: $church->getMembersLimit(),
            currentMembersCount: $church->getMembers()->count(),
            createdAt: $church->getCreatedAt()->format('Y-m-d')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'state' => $this->state,
            'members_limit' => $this->membersLimit,
            'current_members_count' => $this->currentMembersCount,
            'created_at' => $this->createdAt
        ];
    }
}
