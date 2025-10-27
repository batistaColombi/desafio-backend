<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\MemberTransfer;
use App\Entity\Member;
use App\Entity\Church;
use App\Services\Validator\MemberTransferValidator;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/member-transfer')]
class MemberTransferController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private MemberTransferValidator $validator)
    {
    }

    #[Route('/create', name: 'member_transfer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $member = $this->em->getRepository(Member::class)->find($data['member_id'] ?? null);
        $fromChurch = $this->em->getRepository(Church::class)->find($data['from_church_id'] ?? null);
        $toChurch = $this->em->getRepository(Church::class)->find($data['to_church_id'] ?? null);
        
        if (!$member) {
            return $this->json(['error' => 'Membro não encontrado'], 404);
        }
        if (!$fromChurch) {
            return $this->json(['error' => 'Igreja origem não encontrada'], 404);
        }
        if (!$toChurch) {
            return $this->json(['error' => 'Igreja destino não encontrada'], 404);
        }

        $transfer = new MemberTransfer();
        $transfer->setMember($member);
        $transfer->setFromChurch($fromChurch);
        $transfer->setToChurch($toChurch);
        $transfer->setTransferDate(new \DateTime($data['transfer_date'] ?? date('Y-m-d')));
        $transfer->setCreatedBy($data['created_by'] ?? 'system');

        $this->validator->validateTransfer($transfer);
        
        $member->setChurch($toChurch);
        
        $this->em->persist($transfer);
        $this->em->flush();

        return $this->json(['id' => $transfer->getId(), 'message' => 'Transferência criada'], 201);
    }

    #[Route('/{id}', name: 'member_transfer_show', methods: ['GET'])]
    public function show(MemberTransfer $transfer): JsonResponse
    {
        return $this->json($this->toArray($transfer));
    }

    #[Route('/{id}/update', name: 'member_transfer_update', methods: ['PUT'])]
    public function update(Request $request, MemberTransfer $transfer): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        if (isset($data['member_id'])) {
            $member = $this->em->getRepository(Member::class)->find($data['member_id']);
            if ($member) $transfer->setMember($member);
        }
        
        if (isset($data['from_church_id'])) {
            $fromChurch = $this->em->getRepository(Church::class)->find($data['from_church_id']);
            if ($fromChurch) $transfer->setFromChurch($fromChurch);
        }
        
        if (isset($data['to_church_id'])) {
            $toChurch = $this->em->getRepository(Church::class)->find($data['to_church_id']);
            if ($toChurch) $transfer->setToChurch($toChurch);
        }

        if (isset($data['transfer_date'])) {
            $transfer->setTransferDate(new \DateTime($data['transfer_date']));
        }

        if (isset($data['created_by'])) {
            $transfer->setCreatedBy($data['created_by']);
        }

        $this->validator->validateTransfer($transfer);
        
        if ($transfer->getMember() && $transfer->getToChurch()) {
            $transfer->getMember()->setChurch($transfer->getToChurch());
        }
        
        $this->em->flush();

        return $this->json(['message' => 'Transferência atualizada']);
    }

    #[Route('/{id}/delete', name: 'member_transfer_delete', methods: ['DELETE'])]
    public function delete(MemberTransfer $transfer): JsonResponse
    {
        if ($transfer->getMember() && $transfer->getFromChurch()) {
            $transfer->getMember()->setChurch($transfer->getFromChurch());
        }
        
        $this->em->remove($transfer);
        $this->em->flush();

        return $this->json(['message' => 'Transferência deletada']);
    }

    #[Route('/', name: 'member_transfer_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $memberId = $request->query->get('member_id');
        $churchId = $request->query->get('church_id');
        $type = $request->query->get('type');
        
        $qb = $this->em->getRepository(MemberTransfer::class)->createQueryBuilder('mt');
        
        if ($memberId) {
            $qb->andWhere('mt.member = :memberId')->setParameter('memberId', $memberId);
        }
        
        if ($churchId) {
            if ($type === 'incoming') {
                $qb->andWhere('mt.to_church = :churchId');
            } elseif ($type === 'outgoing') {
                $qb->andWhere('mt.from_church = :churchId');
            } else {
                $qb->andWhere('mt.from_church = :churchId OR mt.to_church = :churchId');
            }
            $qb->setParameter('churchId', $churchId);
        }
        
        $qb->orderBy('mt.transfer_date', 'DESC');
        
        $transfers = $qb->getQuery()->getResult();
        $result = array_map(fn(MemberTransfer $t) => $this->toArray($t), $transfers);
        
        return $this->json($result);
    }

    #[Route('/member/{memberId}/history', name: 'member_transfer_history', methods: ['GET'])]
    public function history(int $memberId): JsonResponse
    {
        $member = $this->em->getRepository(Member::class)->find($memberId);
        
        if (!$member) {
            return $this->json(['error' => 'Membro não encontrado'], 404);
        }
        
        $transfers = $this->em->getRepository(MemberTransfer::class)
            ->createQueryBuilder('mt')
            ->where('mt.member = :member')
            ->setParameter('member', $member)
            ->orderBy('mt.transfer_date', 'DESC')
            ->getQuery()
            ->getResult();
        
        $result = array_map(fn(MemberTransfer $t) => $this->toArray($t), $transfers);
        
        return $this->json([
            'member' => [
                'id' => $member->getId(),
                'name' => $member->getName(),
                'current_church' => $member->getChurch() ? [
                    'id' => $member->getChurch()->getId(),
                    'name' => $member->getChurch()->getName()
                ] : null
            ],
            'transfers' => $result
        ]);
    }

    private function toArray(MemberTransfer $transfer): array
    {
        return [
            'id' => $transfer->getId(),
            'member' => [
                'id' => $transfer->getMember()->getId(),
                'name' => $transfer->getMember()->getName(),
                'email' => $transfer->getMember()->getEmail(),
                'document_number' => $transfer->getMember()->getDocumentNumber()
            ],
            'from_church' => [
                'id' => $transfer->getFromChurch()->getId(),
                'name' => $transfer->getFromChurch()->getName()
            ],
            'to_church' => [
                'id' => $transfer->getToChurch()->getId(),
                'name' => $transfer->getToChurch()->getName()
            ],
            'transfer_date' => $transfer->getTransferDate()->format('Y-m-d'),
            'created_by' => $transfer->getCreatedBy(),
            'created_at' => $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $transfer->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }
}