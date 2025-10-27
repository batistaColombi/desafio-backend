<?php

namespace App\DTO;

use App\Entity\MemberTransfer;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "MemberTransferListDTO",
    description: "DTO para listagem de transferências de membro"
)]
class MemberTransferListDTO
{
    public function __construct(
        #[OA\Property(description: "ID da transferência", example: 1)]
        public readonly int $id,

        #[OA\Property(description: "Membro transferido", type: "object")]
        public readonly ?array $member,

        #[OA\Property(description: "Igreja origem", type: "object")]
        public readonly ?array $fromChurch,

        #[OA\Property(description: "Igreja destino", type: "object")]
        public readonly ?array $toChurch,

        #[OA\Property(description: "Data da transferência", example: "2024-01-15")]
        public readonly ?string $transferDate,

        #[OA\Property(description: "Usuário que criou a transferência", example: "admin")]
        public readonly ?string $createdBy,

        #[OA\Property(description: "Data de criação", format: "date-time")]
        public readonly string $createdAt,

        #[OA\Property(description: "Data de atualização", format: "date-time")]
        public readonly string $updatedAt
    ) {}

    public static function fromEntity(MemberTransfer $transfer): self
    {
        return new self(
            id: $transfer->getId(),
            member: $transfer->getMember() ? [
                'id' => $transfer->getMember()->getId(),
                'name' => $transfer->getMember()->getName(),
                'email' => $transfer->getMember()->getEmail()
            ] : null,
            fromChurch: $transfer->getFromChurch() ? [
                'id' => $transfer->getFromChurch()->getId(),
                'name' => $transfer->getFromChurch()->getName()
            ] : null,
            toChurch: $transfer->getToChurch() ? [
                'id' => $transfer->getToChurch()->getId(),
                'name' => $transfer->getToChurch()->getName()
            ] : null,
            transferDate: $transfer->getTransferDate()?->format('Y-m-d'),
            createdBy: $transfer->getCreatedBy(),
            createdAt: $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $transfer->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'member' => $this->member,
            'from_church' => $this->fromChurch,
            'to_church' => $this->toChurch,
            'transfer_date' => $this->transferDate,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
