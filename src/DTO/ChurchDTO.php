<?php

namespace App\DTO;

use App\Entity\Church;
use Symfony\Component\Validator\Constraints as Assert;

class ChurchDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $documentType,
        public readonly string $documentNumber,
        public readonly ?string $internalCode,
        public readonly ?string $phone,
        public readonly ?string $addressStreet,
        public readonly ?string $addressNumber,
        public readonly ?string $addressComplement,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $cep,
        public readonly ?string $website,
        public readonly ?int $membersLimit,
        public readonly int $currentMembersCount,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(Church $church): self
    {
        return new self(
            id: $church->getId(),
            name: $church->getName(),
            documentType: $church->getDocumentType(),
            documentNumber: $church->getDocumentNumber(),
            internalCode: $church->getInternalCode(),
            phone: $church->getPhone(),
            addressStreet: $church->getAddressStreet(),
            addressNumber: $church->getAddressNumber(),
            addressComplement: $church->getAddressComplement(),
            city: $church->getCity(),
            state: $church->getState(),
            cep: $church->getCep(),
            website: $church->getWebsite(),
            membersLimit: $church->getMembersLimit(),
            currentMembersCount: $church->getMembers()->count(),
            createdAt: $church->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $church->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'internal_code' => $this->internalCode,
            'phone' => $this->phone,
            'address_street' => $this->addressStreet,
            'address_number' => $this->addressNumber,
            'address_complement' => $this->addressComplement,
            'city' => $this->city,
            'state' => $this->state,
            'cep' => $this->cep,
            'website' => $this->website,
            'members_limit' => $this->membersLimit,
            'current_members_count' => $this->currentMembersCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
