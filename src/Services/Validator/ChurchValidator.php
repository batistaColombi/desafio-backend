<?php

namespace App\Services\Validator;

use App\Entity\Church;
use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ChurchValidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function validate(Church $church): void
    {
        if (!$church->getName()) {
            throw new BadRequestHttpException("Nome da igreja obrigatório.");
        }
        if (!$church->getDocumentType() || !$church->getDocumentNumber()) {
            throw new BadRequestHttpException("Documento da igreja obrigatório.");
        }
        if ($church->getMembersLimit() !== null && $church->getMembersLimit() < 0) {
            throw new BadRequestHttpException("Limite de membros inválido.");
        }
    }

    public function validateUniqueEmail(string $email, Church $church, ?Member $currentMember = null): void
    {
        if (empty($email)) {
            return;
        }

        $existingMember = $this->em->getRepository(Member::class)->findOneBy([
            'email' => $email,
            'church' => $church,
        ]);

        if ($existingMember && (!$currentMember || $existingMember->getId() !== $currentMember->getId())) {
            throw new BadRequestHttpException(sprintf('O email "%s" já está em uso por outro membro nesta igreja.', $email));
        }
    }
}