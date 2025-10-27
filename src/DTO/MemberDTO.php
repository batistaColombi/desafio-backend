<?php

namespace App\DTO;

use App\Entity\Member;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "MemberDTO",
    description: "DTO para resposta completa de membro"
)]
class MemberDTO
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

        #[OA\Property(description: "Logradouro", example: "Rua A, 100")]
        public readonly ?string $addressStreet,

        #[OA\Property(description: "Número", example: "100")]
        public readonly ?string $addressNumber,

        #[OA\Property(description: "Complemento", example: "Apto 1")]
        public readonly ?string $addressComplement,

        #[OA\Property(description: "Cidade", example: "São Paulo")]
        public readonly ?string $city,

        #[OA\Property(description: "Estado", example: "SP")]
        public readonly ?string $state,

        #[OA\Property(description: "CEP", example: "01234-567")]
        public readonly ?string $cep,

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
            addressStreet: $member->getAddressStreet(),
            addressNumber: $member->getAddressNumber(),
            addressComplement: $member->getAddressComplement(),
            city: $member->getCity(),
            state: $member->getState(),
            cep: $member->getCep(),
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
            'address_street' => $this->addressStreet,
            'address_number' => $this->addressNumber,
            'address_complement' => $this->addressComplement,
            'city' => $this->city,
            'state' => $this->state,
            'cep' => $this->cep,
            'church' => $this->church,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
