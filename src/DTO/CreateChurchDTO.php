<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "CreateChurchDTO",
    description: "DTO para criação de igreja",
    required: ["name", "document_type", "document_number"]
)]
class CreateChurchDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "Nome da igreja é obrigatório")]
        #[Assert\Length(min: 3, max: 100, minMessage: "Nome deve ter pelo menos 3 caracteres", maxMessage: "Nome deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Nome da igreja", example: "Igreja Central")]
        public readonly string $name,

        #[Assert\NotBlank(message: "Tipo de documento é obrigatório")]
        #[Assert\Choice(choices: ['CPF', 'CNPJ'], message: "Tipo de documento deve ser CPF ou CNPJ")]
        #[OA\Property(description: "Tipo do documento", enum: ["CPF", "CNPJ"], example: "CNPJ")]
        public readonly string $documentType,

        #[Assert\NotBlank(message: "Número do documento é obrigatório")]
        #[OA\Property(description: "Número do documento", example: "11222333000181")]
        public readonly string $documentNumber,

        #[Assert\Length(max: 20, maxMessage: "Código interno deve ter no máximo 20 caracteres")]
        #[OA\Property(description: "Código interno único", example: "IC001")]
        public readonly ?string $internalCode = null,

        #[Assert\Length(max: 20, maxMessage: "Telefone deve ter no máximo 20 caracteres")]
        #[OA\Property(description: "Telefone", example: "(11) 99999-1111")]
        public readonly ?string $phone = null,

        #[Assert\Length(max: 200, maxMessage: "Logradouro deve ter no máximo 200 caracteres")]
        #[OA\Property(description: "Logradouro", example: "Rua das Flores, 123")]
        public readonly ?string $addressStreet = null,

        #[Assert\Length(max: 10, maxMessage: "Número deve ter no máximo 10 caracteres")]
        #[OA\Property(description: "Número", example: "123")]
        public readonly ?string $addressNumber = null,

        #[Assert\Length(max: 50, maxMessage: "Complemento deve ter no máximo 50 caracteres")]
        #[OA\Property(description: "Complemento", example: "Sala 1")]
        public readonly ?string $addressComplement = null,

        #[Assert\Length(max: 100, maxMessage: "Cidade deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Cidade", example: "São Paulo")]
        public readonly ?string $city = null,

        #[Assert\Length(max: 2, maxMessage: "Estado deve ter no máximo 2 caracteres")]
        #[OA\Property(description: "Estado", example: "SP")]
        public readonly ?string $state = null,

        #[Assert\Length(max: 10, maxMessage: "CEP deve ter no máximo 10 caracteres")]
        #[OA\Property(description: "CEP", example: "01234-567")]
        public readonly ?string $cep = null,

        #[OA\Property(description: "Website", example: "https://igrejacentral.com")]
        public readonly ?string $website = null,

        #[Assert\PositiveOrZero(message: "Limite de membros deve ser um número positivo ou zero")]
        #[OA\Property(description: "Limite de membros", example: 100)]
        public readonly ?int $membersLimit = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            documentType: $data['document_type'] ?? '',
            documentNumber: $data['document_number'] ?? '',
            internalCode: $data['internal_code'] ?? null,
            phone: $data['phone'] ?? null,
            addressStreet: $data['address_street'] ?? null,
            addressNumber: $data['address_number'] ?? null,
            addressComplement: $data['address_complement'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            cep: $data['cep'] ?? null,
            website: $data['website'] ?? null,
            membersLimit: isset($data['members_limit']) ? (int)$data['members_limit'] : null
        );
    }
}
