<?php

namespace App\Service;

use App\DTO\ChurchDTO;
use App\DTO\ChurchListDTO;
use App\DTO\CreateChurchDTO;
use App\DTO\UpdateChurchDTO;
use App\Entity\Church;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChurchDTOService
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function createChurchFromDTO(CreateChurchDTO $dto): Church
    {
        $church = new Church();
        $church->setName($dto->name);
        $church->setDocumentType($dto->documentType);
        $church->setDocumentNumber($dto->documentNumber);
        $church->setInternalCode($dto->internalCode);
        $church->setPhone($dto->phone);
        $church->setAddressStreet($dto->addressStreet);
        $church->setAddressNumber($dto->addressNumber);
        $church->setAddressComplement($dto->addressComplement);
        $church->setCity($dto->city);
        $church->setState($dto->state);
        $church->setCep($dto->cep);
        $church->setWebsite($dto->website);
        $church->setMembersLimit($dto->membersLimit);

        return $church;
    }

    public function updateChurchFromDTO(Church $church, UpdateChurchDTO $dto): Church
    {
        if ($dto->name !== null) {
            $church->setName($dto->name);
        }
        if ($dto->documentType !== null) {
            $church->setDocumentType($dto->documentType);
        }
        if ($dto->documentNumber !== null) {
            $church->setDocumentNumber($dto->documentNumber);
        }
        if ($dto->internalCode !== null) {
            $church->setInternalCode($dto->internalCode);
        }
        if ($dto->phone !== null) {
            $church->setPhone($dto->phone);
        }
        if ($dto->addressStreet !== null) {
            $church->setAddressStreet($dto->addressStreet);
        }
        if ($dto->addressNumber !== null) {
            $church->setAddressNumber($dto->addressNumber);
        }
        if ($dto->addressComplement !== null) {
            $church->setAddressComplement($dto->addressComplement);
        }
        if ($dto->city !== null) {
            $church->setCity($dto->city);
        }
        if ($dto->state !== null) {
            $church->setState($dto->state);
        }
        if ($dto->cep !== null) {
            $church->setCep($dto->cep);
        }
        if ($dto->website !== null) {
            $church->setWebsite($dto->website);
        }
        if ($dto->membersLimit !== null) {
            $church->setMembersLimit($dto->membersLimit);
        }

        return $church;
    }

    public function validateDTO(object $dto): array
    {
        $errors = $this->validator->validate($dto);
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        return $errorMessages;
    }

    public function toChurchDTO(Church $church): ChurchDTO
    {
        return ChurchDTO::fromEntity($church);
    }

    public function toChurchListDTO(Church $church): ChurchListDTO
    {
        return ChurchListDTO::fromEntity($church);
    }

    public function toChurchListDTOs(array $churches): array
    {
        return array_map(fn(Church $church) => $this->toChurchListDTO($church), $churches);
    }
}
