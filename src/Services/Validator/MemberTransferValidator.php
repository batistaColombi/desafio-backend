<?php

namespace App\Services\Validator;

use App\Entity\MemberTransfer;
use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MemberTransferValidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function validateTransfer(MemberTransfer $transfer): void
    {
        if (!$transfer->getFromChurch() || !$transfer->getToChurch()) {
            throw new BadRequestHttpException("Igreja origem ou destino não informada.");
        }
        if ($transfer->getFromChurch() === $transfer->getToChurch()) {
            throw new BadRequestHttpException("Não é possível transferir para a mesma igreja.");
        }
        
        $toChurch = $transfer->getToChurch();
        if ($toChurch->getMembersLimit() !== null && $toChurch->getMembers()->count() >= $toChurch->getMembersLimit()) {
            throw new BadRequestHttpException("Igreja destino atingiu limite máximo de membros.");
        }

        $this->validateUniqueEmailInDestinationChurch($transfer);
        
        $this->validateTransferInterval($transfer);
    }

    private function validateUniqueEmailInDestinationChurch(MemberTransfer $transfer): void
    {
        $member = $transfer->getMember();
        $toChurch = $transfer->getToChurch();
        
        if (!$member || !$member->getEmail()) {
            return;
        }

        $existingMember = $this->em->getRepository(Member::class)->findOneBy([
            'email' => $member->getEmail(),
            'church' => $toChurch,
        ]);

        if ($existingMember && $existingMember->getId() !== $member->getId()) {
            throw new BadRequestHttpException(sprintf(
                'O email "%s" já está em uso por outro membro na igreja destino "%s".',
                $member->getEmail(),
                $toChurch->getName()
            ));
        }
    }

    private function validateTransferInterval(MemberTransfer $transfer): void
    {
        $member = $transfer->getMember();
        if (!$member) {
            return;
        }

        $transferDate = $transfer->getTransferDate();
        if (!$transferDate) {
            return;
        }

        $lastTransfer = $this->em->getRepository(MemberTransfer::class)
            ->createQueryBuilder('mt')
            ->where('mt.member = :member')
            ->andWhere('mt.id != :currentId')
            ->setParameter('member', $member)
            ->setParameter('currentId', $transfer->getId() ?? 0)
            ->orderBy('mt.transfer_date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastTransfer) {
            $lastTransferDate = $lastTransfer->getTransferDate();
            $interval = $transferDate->diff($lastTransferDate);
            
            if ($interval->days < 10) {
                throw new BadRequestHttpException(sprintf(
                    'Não é possível transferir o membro "%s" antes de 10 dias da última transferência. Última transferência: %s',
                    $member->getName(),
                    $lastTransferDate->format('d/m/Y')
                ));
            }
        }
    }
}