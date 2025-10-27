<?php

namespace App\Validator;

use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MemberValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate(Member $member): void
    {
        $this->validateDocument($member);
        $this->validateEmailUnique($member);
        $this->validateChurchMemberLimit($member);
        $this->validateBirthDate($member);
        $this->validateChurchRequired($member);
    }

    private function validateChurchRequired(Member $member): void
    {
        if (!$member->getChurch()) {
            throw new BadRequestHttpException("É necessário informar uma igreja para o membro.");
        }
    }

    private function validateBirthDate(Member $member): void
    {
        $birthDate = $member->getBirthDate();
        if ($birthDate && $birthDate > new \DateTime()) {
            throw new BadRequestHttpException("A data de nascimento não pode ser uma data futura.");
        }
    }

    private function validateDocument(Member $member): void
    {
        $type = $member->getDocumentType();
        $number = preg_replace('/\D/', '', $member->getDocumentNumber());

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
        $lengths = [5, 6];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$cnpj[$i] * $lengths[$i < 4 ? 0 : 1];
            if ($i >= 4) $lengths[1]--;
        }
        $rev = $sum % 11;
        $dig1 = ($rev < 2) ? 0 : 11 - $rev;
        $sum = 0;
        $lengths = [6, 5];
        for ($i = 0; $i < 13; $i++) {
            $sum += (int)$cnpj[$i] * $lengths[$i < 5 ? 0 : 1];
            if ($i >= 5) $lengths[1]--;
        }
        $rev = $sum % 11;
        $dig2 = ($rev < 2) ? 0 : 11 - $rev;
        return $dig1 === (int)$cnpj[12] && $dig2 === (int)$cnpj[13];
    }

    private function validateEmailUnique(Member $member): void
    {
        $repo = $this->em->getRepository(Member::class);
        $existing = $repo->findOneBy([
            'email' => $member->getEmail(),
            'church' => $member->getChurch(),
        ]);
        if ($existing && $existing->getId() !== $member->getId()) {
            throw new BadRequestHttpException("Email já existe nessa igreja.");
        }
    }

    private function validateChurchMemberLimit(Member $member): void
    {
        $church = $member->getChurch();
        if ($church && $church->getMembersLimit() !== null) {
            $membersCount = $church->getMembers()->count();
            if ($membersCount >= $church->getMembersLimit()) {
                throw new BadRequestHttpException("Limite máximo de membros da igreja atingido.");
            }
        }
    }
}