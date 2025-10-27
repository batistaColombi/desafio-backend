<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "CreateMemberTransferDTO",
    description: "DTO para criação de transferência de membro",
    required: ["member_id", "from_church_id", "to_church_id"]
)]
class CreateMemberTransferDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "ID do membro é obrigatório")]
        #[Assert\Positive(message: "ID do membro deve ser positivo")]
        #[OA\Property(description: "ID do membro a ser transferido", example: 1)]
        public readonly int $memberId,

        #[Assert\NotBlank(message: "ID da igreja origem é obrigatório")]
        #[Assert\Positive(message: "ID da igreja origem deve ser positivo")]
        #[OA\Property(description: "ID da igreja origem", example: 1)]
        public readonly int $fromChurchId,

        #[Assert\NotBlank(message: "ID da igreja destino é obrigatório")]
        #[Assert\Positive(message: "ID da igreja destino deve ser positivo")]
        #[OA\Property(description: "ID da igreja destino", example: 2)]
        public readonly int $toChurchId,

        #[Assert\Date(message: "Data de transferência deve ser uma data válida")]
        #[OA\Property(description: "Data da transferência (opcional, padrão: hoje)", example: "2024-01-15")]
        public readonly ?string $transferDate = null,

        #[Assert\Length(max: 100, maxMessage: "Usuário deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Usuário que criou a transferência (opcional)", example: "admin")]
        public readonly ?string $createdBy = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            memberId: (int) ($data['member_id'] ?? 0),
            fromChurchId: (int) ($data['from_church_id'] ?? 0),
            toChurchId: (int) ($data['to_church_id'] ?? 0),
            transferDate: $data['transfer_date'] ?? null,
            createdBy: $data['created_by'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'from_church_id' => $this->fromChurchId,
            'to_church_id' => $this->toChurchId,
            'transfer_date' => $this->transferDate,
            'created_by' => $this->createdBy,
        ];
    }
}
