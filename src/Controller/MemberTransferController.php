<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\MemberTransfer;
use App\Services\Validator\MemberTransferValidator;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/member-transfer')]
class MemberTransferController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private MemberTransferValidator $validator)
    {
    }

    #[Route('/create', name: 'member_transfer_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $transfer = new MemberTransfer();
        $transfer->setMember($this->em->getRepository('App:Member')->find($request->request->get('member_id')));
        $transfer->setFromChurch($this->em->getRepository('App:Church')->find($request->request->get('from_church_id')));
        $transfer->setToChurch($this->em->getRepository('App:Church')->find($request->request->get('to_church_id')));
        $transfer->setTransferDate(new \DateTime($request->request->get('transfer_date')));
        $transfer->setCreatedBy($request->request->get('created_by'));

        $this->validator->validateTransfer($transfer);
        $this->em->persist($transfer);
        $this->em->flush();

        return $this->json(['status' => 'ok', 'id' => $transfer->getId()]);
    }

    #[Route('/{id}', name: 'member_transfer_show', methods: ['GET'])]
    public function show(MemberTransfer $transfer): Response
    {
        return $this->json($transfer);
    }

    #[Route('/{id}/update', name: 'member_transfer_update', methods: ['POST'])]
    public function update(Request $request, MemberTransfer $transfer): Response
    {
        $member = $this->em->getRepository('App:Member')->find($request->request->get('member_id'));
        $fromChurch = $this->em->getRepository('App:Church')->find($request->request->get('from_church_id'));
        $toChurch = $this->em->getRepository('App:Church')->find($request->request->get('to_church_id'));

        if ($member) $transfer->setMember($member);
        if ($fromChurch) $transfer->setFromChurch($fromChurch);
        if ($toChurch) $transfer->setToChurch($toChurch);

        $transferDate = $request->request->get('transfer_date');
        if ($transferDate) $transfer->setTransferDate(new \DateTime($transferDate));

        $createdBy = $request->request->get('created_by');
        if ($createdBy) $transfer->setCreatedBy($createdBy);

        $this->validator->validateTransfer($transfer);
        $this->em->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/{id}/delete', name: 'member_transfer_delete', methods: ['DELETE'])]
    public function delete(MemberTransfer $transfer): Response
    {
        $this->em->remove($transfer);
        $this->em->flush();

        return $this->json(['status' => 'deleted']);
    }

    #[Route('/', name: 'member_transfer_list', methods: ['GET'])]
    public function list(): Response
    {
        $transfers = $this->em->getRepository(MemberTransfer::class)->findAll();
        return $this->json($transfers);
    }
}