<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateMemberTransferDTO",
    description: "DTO para atualização de transferência de membro",
    required: []
)]
class UpdateMemberTransferDTO
{
    public function __construct(
        #[Assert\Positive(message: "ID do membro deve ser positivo")]
        #[OA\Property(description: "ID do membro a ser transferido")]
        public readonly ?int $memberId = null,

        #[Assert\Positive(message: "ID da igreja origem deve ser positivo")]
        #[OA\Property(description: "ID da igreja origem")]
        public readonly ?int $fromChurchId = null,

        #[Assert\Positive(message: "ID da igreja destino deve ser positivo")]
        #[OA\Property(description: "ID da igreja destino")]
        public readonly ?int $toChurchId = null,

        #[Assert\Date(message: "Data de transferência deve ser uma data válida")]
        #[OA\Property(description: "Data da transferência", example: "2024-01-15")]
        public readonly ?string $transferDate = null,

        #[Assert\Length(max: 100, maxMessage: "Usuário deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Usuário que criou a transferência")]
        public readonly ?string $createdBy = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            memberId: isset($data['member_id']) ? (int) $data['member_id'] : null,
            fromChurchId: isset($data['from_church_id']) ? (int) $data['from_church_id'] : null,
            toChurchId: isset($data['to_church_id']) ? (int) $data['to_church_id'] : null,
            transferDate: $data['transfer_date'] ?? null,
            createdBy: $data['created_by'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->memberId !== null) $data['member_id'] = $this->memberId;
        if ($this->fromChurchId !== null) $data['from_church_id'] = $this->fromChurchId;
        if ($this->toChurchId !== null) $data['to_church_id'] = $this->toChurchId;
        if ($this->transferDate !== null) $data['transfer_date'] = $this->transferDate;
        if ($this->createdBy !== null) $data['created_by'] = $this->createdBy;
        return $data;
    }

    public function hasUpdates(): bool
    {
        return $this->memberId !== null || 
               $this->fromChurchId !== null || 
               $this->toChurchId !== null || 
               $this->transferDate !== null || 
               $this->createdBy !== null;
    }
}
