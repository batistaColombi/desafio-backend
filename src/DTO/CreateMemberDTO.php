<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "CreateMemberDTO",
    description: "DTO para criação de membro",
    required: ["name", "document_type", "document_number", "email", "church_id"]
)]
class CreateMemberDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "Nome do membro é obrigatório")]
        #[Assert\Length(min: 3, max: 100, minMessage: "Nome deve ter pelo menos 3 caracteres", maxMessage: "Nome deve ter no máximo 100 caracteres")]
        #[OA\Property(description: "Nome do membro", example: "João Silva")]
        public readonly string $name,

        #[Assert\NotBlank(message: "Tipo de documento é obrigatório")]
        #[Assert\Choice(choices: ['CPF', 'CNPJ'], message: "Tipo de documento deve ser CPF ou CNPJ")]
        #[OA\Property(description: "Tipo do documento", enum: ["CPF", "CNPJ"], example: "CPF")]
        public readonly string $documentType,

        #[Assert\NotBlank(message: "Número do documento é obrigatório")]
        #[OA\Property(description: "Número do documento", example: "11144477735")]
        public readonly string $documentNumber,

        #[Assert\NotBlank(message: "Email é obrigatório")]
        #[Assert\Email(message: "Email deve ter formato válido")]
        #[OA\Property(description: "Email único na igreja", example: "joao@email.com")]
        public readonly string $email,

        #[Assert\Length(max: 20, maxMessage: "Telefone deve ter no máximo 20 caracteres")]
        #[OA\Property(description: "Telefone", example: "(11) 99999-3333")]
        public readonly ?string $phone = null,

        #[Assert\Date(message: "Data de nascimento deve ser uma data válida")]
        #[OA\Property(description: "Data de nascimento", example: "1990-05-15")]
        public readonly ?string $birthDate = null,

        #[Assert\Length(max: 200, maxMessage: "Logradouro deve ter no máximo 200 caracteres")]
        #[OA\Property(description: "Logradouro", example: "Rua A, 100")]
        public readonly ?string $addressStreet = null,

        #[Assert\Length(max: 10, maxMessage: "Número deve ter no máximo 10 caracteres")]
        #[OA\Property(description: "Número", example: "100")]
        public readonly ?string $addressNumber = null,

        #[Assert\Length(max: 50, maxMessage: "Complemento deve ter no máximo 50 caracteres")]
        #[OA\Property(description: "Complemento", example: "Apto 1")]
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

        #[Assert\NotBlank(message: "ID da igreja é obrigatório")]
        #[Assert\Positive(message: "ID da igreja deve ser positivo")]
        #[OA\Property(description: "ID da igreja", example: 1)]
        public readonly int $churchId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            documentType: $data['document_type'] ?? '',
            documentNumber: $data['document_number'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? null,
            birthDate: $data['birth_date'] ?? null,
            addressStreet: $data['address_street'] ?? null,
            addressNumber: $data['address_number'] ?? null,
            addressComplement: $data['address_complement'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            cep: $data['cep'] ?? null,
            churchId: (int) ($data['church_id'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
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
            'church_id' => $this->churchId,
        ];
    }
}
