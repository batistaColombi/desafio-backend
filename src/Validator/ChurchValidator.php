<?php

namespace App\Validator;

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
        
        $this->validateDocument($church);
        
        $this->validateUniqueInternalCode($church);
    }

    private function validateDocument(Church $church): void
    {
        $type = $church->getDocumentType();
        $number = preg_replace('/\D/', '', $church->getDocumentNumber());

        if ($type === 'CPF' && !$this->isValidCPF($number)) {
            throw new BadRequestHttpException("CPF inválido.");
        }
        if ($type === 'CNPJ' && !$this->isValidCNPJ($number)) {
            throw new BadRequestHttpException("CNPJ inválido.");
        }
    }

    private function isValidCPF(string $cpf): bool
    {
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        $sum = 0;
        for ($i = 0, $j = 10; $i < 9; $i++, $j--) {
            $sum += (int)$cpf[$i] * $j;
        }
        $rev = 11 - ($sum % 11);
        $dig1 = ($rev >= 10) ? 0 : $rev;
        $sum = 0;
        for ($i = 0, $j = 11; $i < 10; $i++, $j--) {
            $sum += (int)$cpf[$i] * $j;
        }
        $rev = 11 - ($sum % 11);
        $dig2 = ($rev >= 10) ? 0 : $rev;
        return $dig1 === (int)$cpf[9] && $dig2 === (int)$cpf[10];
    }

    private function isValidCNPJ(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        
        $lengths = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$cnpj[$i] * $lengths[$i];
        }
        $rev = $sum % 11;
        $dig1 = ($rev < 2) ? 0 : 11 - $rev;
        
        $sum = 0;
        $lengths = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += (int)$cnpj[$i] * $lengths[$i];
        }
        $rev = $sum % 11;
        $dig2 = ($rev < 2) ? 0 : 11 - $rev;
        
        return $dig1 === (int)$cnpj[12] && $dig2 === (int)$cnpj[13];
    }

    private function validateUniqueInternalCode(Church $church): void
    {
        if (!$church->getInternalCode()) {
            return;
        }

        $existingChurch = $this->em->getRepository(Church::class)->findOneBy([
            'internal_code' => $church->getInternalCode(),
        ]);

        if ($existingChurch && $existingChurch->getId() !== $church->getId()) {
            throw new BadRequestHttpException(sprintf(
                'O código interno "%s" já está em uso por outra igreja.',
                $church->getInternalCode()
            ));
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