<?php

namespace App\Services\Validator;

use App\Entity\MemberTransfer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MemberTransferValidator
{
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
    }
}