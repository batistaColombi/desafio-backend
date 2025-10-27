<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateChurchDTO
{
    public function __construct(
        #[Assert\Length(min: 3, max: 100, minMessage: "Nome deve ter pelo menos 3 caracteres", maxMessage: "Nome deve ter no máximo 100 caracteres")]
        public readonly ?string $name = null,

        #[Assert\Choice(choices: ['CPF', 'CNPJ'], message: "Tipo de documento deve ser CPF ou CNPJ")]
        public readonly ?string $documentType = null,

        #[Assert\Length(max: 20, maxMessage: "Número do documento deve ter no máximo 20 caracteres")]
        public readonly ?string $documentNumber = null,

        #[Assert\Length(max: 20, maxMessage: "Código interno deve ter no máximo 20 caracteres")]
        public readonly ?string $internalCode = null,

        #[Assert\Length(max: 20, maxMessage: "Telefone deve ter no máximo 20 caracteres")]
        public readonly ?string $phone = null,

        #[Assert\Length(max: 200, maxMessage: "Logradouro deve ter no máximo 200 caracteres")]
        public readonly ?string $addressStreet = null,

        #[Assert\Length(max: 10, maxMessage: "Número deve ter no máximo 10 caracteres")]
        public readonly ?string $addressNumber = null,

        #[Assert\Length(max: 50, maxMessage: "Complemento deve ter no máximo 50 caracteres")]
        public readonly ?string $addressComplement = null,

        #[Assert\Length(max: 100, maxMessage: "Cidade deve ter no máximo 100 caracteres")]
        public readonly ?string $city = null,

        #[Assert\Length(max: 2, maxMessage: "Estado deve ter no máximo 2 caracteres")]
        public readonly ?string $state = null,

        #[Assert\Length(max: 10, maxMessage: "CEP deve ter no máximo 10 caracteres")]
        public readonly ?string $cep = null,

        #[Assert\Url(message: "Website deve ser uma URL válida")]
        public readonly ?string $website = null,

        #[Assert\PositiveOrZero(message: "Limite de membros deve ser um número positivo ou zero")]
        public readonly ?int $membersLimit = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            documentType: $data['document_type'] ?? null,
            documentNumber: $data['document_number'] ?? null,
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

    public function hasUpdates(): bool
    {
        return $this->name !== null ||
               $this->documentType !== null ||
               $this->documentNumber !== null ||
               $this->internalCode !== null ||
               $this->phone !== null ||
               $this->addressStreet !== null ||
               $this->addressNumber !== null ||
               $this->addressComplement !== null ||
               $this->city !== null ||
               $this->state !== null ||
               $this->cep !== null ||
               $this->website !== null ||
               $this->membersLimit !== null;
    }
}
