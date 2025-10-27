<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateMemberDTO",
    description: "DTO para atualização de membro",
    required: []
)]
class UpdateMemberDTO
{
    public function __construct(
        #[Assert\Length(min: 3, max: 100, minMessage: "Nome deve ter pelo menos 3 caracteres", maxMessage: "Nome deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Nome do membro", example: "João Silva Atualizado")]
        public readonly ?string $name = null,

        #[Assert\Choice(choices: ['CPF', 'CNPJ'], message: "Tipo de documento deve ser CPF ou CNPJ")]
        #[OA\Property(description: "Tipo do documento", enum: ["CPF", "CNPJ"])]
        public readonly ?string $documentType = null,

        #[OA\Property(description: "Número do documento")]
        public readonly ?string $documentNumber = null,

        #[Assert\Email(message: "Email deve ter formato válido")]
        #[OA\Property(description: "Email único na igreja", format: "email")]
        public readonly ?string $email = null,

        #[Assert\Length(max: 20, maxMessage: "Telefone deve ter no máximo 20 caracteres")]
        #[OA\Property(description: "Telefone")]
        public readonly ?string $phone = null,

        #[Assert\Date(message: "Data de nascimento deve ser uma data válida")]
        #[OA\Property(description: "Data de nascimento", format: "date")]
        public readonly ?string $birthDate = null,

        #[Assert\Length(max: 200, maxMessage: "Logradouro deve ter no máximo 200 caracteres")]
        #[OA\Property(description: "Logradouro")]
        public readonly ?string $addressStreet = null,

        #[Assert\Length(max: 10, maxMessage: "Número deve ter no máximo 10 caracteres")]
        #[OA\Property(description: "Número")]
        public readonly ?string $addressNumber = null,

        #[Assert\Length(max: 50, maxMessage: "Complemento deve ter no máximo 50 caracteres")]
        #[OA\Property(description: "Complemento")]
        public readonly ?string $addressComplement = null,

        #[Assert\Length(max: 100, maxMessage: "Cidade deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Cidade")]
        public readonly ?string $city = null,

        #[Assert\Length(max: 2, maxMessage: "Estado deve ter no máximo 2 caracteres")]
        #[OA\Property(description: "Estado")]
        public readonly ?string $state = null,

        #[Assert\Length(max: 10, maxMessage: "CEP deve ter no máximo 10 caracteres")]
        #[OA\Property(description: "CEP")]
        public readonly ?string $cep = null,

        #[Assert\Positive(message: "ID da igreja deve ser positivo")]
        #[OA\Property(description: "ID da igreja")]
        public readonly ?int $churchId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            documentType: $data['document_type'] ?? null,
            documentNumber: $data['document_number'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            birthDate: $data['birth_date'] ?? null,
            addressStreet: $data['address_street'] ?? null,
            addressNumber: $data['address_number'] ?? null,
            addressComplement: $data['address_complement'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            cep: $data['cep'] ?? null,
            churchId: isset($data['church_id']) ? (int) $data['church_id'] : null
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->documentType !== null) $data['document_type'] = $this->documentType;
        if ($this->documentNumber !== null) $data['document_number'] = $this->documentNumber;
        if ($this->email !== null) $data['email'] = $this->email;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->birthDate !== null) $data['birth_date'] = $this->birthDate;
        if ($this->addressStreet !== null) $data['address_street'] = $this->addressStreet;
        if ($this->addressNumber !== null) $data['address_number'] = $this->addressNumber;
        if ($this->addressComplement !== null) $data['address_complement'] = $this->addressComplement;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->state !== null) $data['state'] = $this->state;
        if ($this->cep !== null) $data['cep'] = $this->cep;
        if ($this->churchId !== null) $data['church_id'] = $this->churchId;
        return $data;
    }

    public function hasUpdates(): bool
    {
        return $this->name !== null || 
               $this->documentType !== null || 
               $this->documentNumber !== null || 
               $this->email !== null || 
               $this->phone !== null || 
               $this->birthDate !== null || 
               $this->addressStreet !== null || 
               $this->addressNumber !== null || 
               $this->addressComplement !== null || 
               $this->city !== null || 
               $this->state !== null || 
               $this->cep !== null || 
               $this->churchId !== null;
    }
}
