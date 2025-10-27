<?php

namespace App\Service;

use App\DTO\CreateMemberTransferDTO;
use App\DTO\UpdateMemberTransferDTO;
use App\DTO\MemberTransferDTO;
use App\DTO\MemberTransferListDTO;
use App\Entity\MemberTransfer;
use App\Entity\Member;
use App\Entity\Church;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MemberTransferDTOService
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $em
    ) {}

    public function validateDTO(CreateMemberTransferDTO|UpdateMemberTransferDTO $dto): array
    {
        $violations = $this->validator->validate($dto);
        $errors = [];
        
        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }
        
        return $errors;
    }

    public function createMemberTransferFromDTO(CreateMemberTransferDTO $dto): MemberTransfer
    {
        $member = $this->em->getRepository(Member::class)->find($dto->memberId);
        if (!$member) {
            throw new \InvalidArgumentException("Membro não encontrado");
        }

        $fromChurch = $this->em->getRepository(Church::class)->find($dto->fromChurchId);
        if (!$fromChurch) {
            throw new \InvalidArgumentException("Igreja origem não encontrada");
        }

        $toChurch = $this->em->getRepository(Church::class)->find($dto->toChurchId);
        if (!$toChurch) {
            throw new \InvalidArgumentException("Igreja destino não encontrada");
        }

        $transfer = new MemberTransfer();
        $transfer->setMember($member);
        $transfer->setFromChurch($fromChurch);
        $transfer->setToChurch($toChurch);
        $transfer->setTransferDate($dto->transferDate ? new \DateTime($dto->transferDate) : new \DateTime());
        $transfer->setCreatedBy($dto->createdBy);

        return $transfer;
    }

    public function updateMemberTransferFromDTO(MemberTransfer $transfer, UpdateMemberTransferDTO $dto): MemberTransfer
    {
        if ($dto->memberId !== null) {
            $member = $this->em->getRepository(Member::class)->find($dto->memberId);
            if (!$member) {
                throw new \InvalidArgumentException("Membro não encontrado");
            }
            $transfer->setMember($member);
        }
        if ($dto->fromChurchId !== null) {
            $fromChurch = $this->em->getRepository(Church::class)->find($dto->fromChurchId);
            if (!$fromChurch) {
                throw new \InvalidArgumentException("Igreja origem não encontrada");
            }
            $transfer->setFromChurch($fromChurch);
        }
        if ($dto->toChurchId !== null) {
            $toChurch = $this->em->getRepository(Church::class)->find($dto->toChurchId);
            if (!$toChurch) {
                throw new \InvalidArgumentException("Igreja destino não encontrada");
            }
            $transfer->setToChurch($toChurch);
        }
        if ($dto->transferDate !== null) {
            $transfer->setTransferDate(new \DateTime($dto->transferDate));
        }
        if ($dto->createdBy !== null) {
            $transfer->setCreatedBy($dto->createdBy);
        }

        return $transfer;
    }

    public function toMemberTransferDTO(MemberTransfer $transfer): MemberTransferDTO
    {
        return MemberTransferDTO::fromEntity($transfer);
    }

    public function toMemberTransferListDTO(MemberTransfer $transfer): MemberTransferListDTO
    {
        return MemberTransferListDTO::fromEntity($transfer);
    }
}
