<?php

namespace App\Service;

use App\DTO\CreateMemberDTO;
use App\DTO\UpdateMemberDTO;
use App\DTO\MemberDTO;
use App\DTO\MemberListDTO;
use App\Entity\Member;
use App\Entity\Church;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MemberDTOService
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $em
    ) {}

    public function validateDTO(CreateMemberDTO|UpdateMemberDTO $dto): array
    {
        $violations = $this->validator->validate($dto);
        $errors = [];
        
        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }
        
        return $errors;
    }

    public function createMemberFromDTO(CreateMemberDTO $dto): Member
    {
        $church = $this->em->getRepository(Church::class)->find($dto->churchId);
        if (!$church) {
            throw new \InvalidArgumentException("Igreja não encontrada");
        }

        $member = new Member();
        $member->setName($dto->name);
        $member->setDocumentType($dto->documentType);
        $member->setDocumentNumber($dto->documentNumber);
        $member->setEmail($dto->email);
        $member->setPhone($dto->phone);
        $member->setBirthDate($dto->birthDate ? new \DateTime($dto->birthDate) : null);
        $member->setAddressStreet($dto->addressStreet);
        $member->setAddressNumber($dto->addressNumber);
        $member->setAddressComplement($dto->addressComplement);
        $member->setCity($dto->city);
        $member->setState($dto->state);
        $member->setCep($dto->cep);
        $member->setChurch($church);

        return $member;
    }

    public function updateMemberFromDTO(Member $member, UpdateMemberDTO $dto): Member
    {
        if ($dto->name !== null) {
            $member->setName($dto->name);
        }
        if ($dto->documentType !== null) {
            $member->setDocumentType($dto->documentType);
        }
        if ($dto->documentNumber !== null) {
            $member->setDocumentNumber($dto->documentNumber);
        }
        if ($dto->email !== null) {
            $member->setEmail($dto->email);
        }
        if ($dto->phone !== null) {
            $member->setPhone($dto->phone);
        }
        if ($dto->birthDate !== null) {
            $member->setBirthDate(new \DateTime($dto->birthDate));
        }
        if ($dto->addressStreet !== null) {
            $member->setAddressStreet($dto->addressStreet);
        }
        if ($dto->addressNumber !== null) {
            $member->setAddressNumber($dto->addressNumber);
        }
        if ($dto->addressComplement !== null) {
            $member->setAddressComplement($dto->addressComplement);
        }
        if ($dto->city !== null) {
            $member->setCity($dto->city);
        }
        if ($dto->state !== null) {
            $member->setState($dto->state);
        }
        if ($dto->cep !== null) {
            $member->setCep($dto->cep);
        }
        if ($dto->churchId !== null) {
            $church = $this->em->getRepository(Church::class)->find($dto->churchId);
            if (!$church) {
                throw new \InvalidArgumentException("Igreja não encontrada");
            }
            $member->setChurch($church);
        }

        return $member;
    }

    public function toMemberDTO(Member $member): MemberDTO
    {
        return MemberDTO::fromEntity($member);
    }

    public function toMemberListDTO(Member $member): MemberListDTO
    {
        return MemberListDTO::fromEntity($member);
    }
}
