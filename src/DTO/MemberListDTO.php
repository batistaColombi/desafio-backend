<?php

namespace App\DTO;

use App\Entity\Member;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "MemberListDTO",
    description: "DTO para listagem de membros"
)]
class MemberListDTO
{
    public function __construct(
        #[OA\Property(description: "ID do membro", example: 1)]
        public readonly int $id,

        #[OA\Property(description: "Nome do membro", example: "João Silva")]
        public readonly string $name,

        #[OA\Property(description: "Tipo do documento", example: "CPF")]
        public readonly string $documentType,

        #[OA\Property(description: "Número do documento", example: "11144477735")]
        public readonly string $documentNumber,

        #[OA\Property(description: "Email do membro", example: "joao@email.com")]
        public readonly string $email,

        #[OA\Property(description: "Telefone", example: "(11) 99999-3333")]
        public readonly ?string $phone,

        #[OA\Property(description: "Data de nascimento", example: "1990-05-15")]
        public readonly ?string $birthDate,

        #[OA\Property(description: "Igreja do membro", type: "object")]
        public readonly ?array $church,

        #[OA\Property(description: "Data de criação", format: "date-time")]
        public readonly string $createdAt,

        #[OA\Property(description: "Data de atualização", format: "date-time")]
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(Member $member): self
    {
        return new self(
            id: $member->getId(),
            name: $member->getName(),
            documentType: $member->getDocumentType(),
            documentNumber: $member->getDocumentNumber(),
            email: $member->getEmail(),
            phone: $member->getPhone(),
            birthDate: $member->getBirthDate()?->format('Y-m-d'),
            church: $member->getChurch() ? [
                'id' => $member->getChurch()->getId(),
                'name' => $member->getChurch()->getName()
            ] : null,
            createdAt: $member->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $member->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birthDate,
            'church' => $this->church,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
