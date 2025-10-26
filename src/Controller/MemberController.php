<?php

namespace App\Controller;

use App\Entity\Member;
use App\Entity\Church;
use App\Services\Validator\MemberValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/member')]
class MemberController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private MemberValidator $validator)
    {
    }

    #[Route('/create', name: 'member_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $member = new Member();
        $member->setName($request->request->get('name'));
        $member->setDocumentType($request->request->get('document_type'));
        $member->setDocumentNumber($request->request->get('document_number'));
        $member->setEmail($request->request->get('email'));
        $member->setPhone($request->request->get('phone'));
        
        $birthDate = $request->request->get('birth_date');
        if ($birthDate) {
            $member->setBirthDate(new \DateTime($birthDate));
        }
        
        $member->setAddressStreet($request->request->get('address_street'));
        $member->setAddressNumber($request->request->get('address_number'));
        $member->setAddressComplement($request->request->get('address_complement'));
        $member->setCity($request->request->get('city'));
        $member->setState($request->request->get('state'));
        $member->setCep($request->request->get('cep'));

        $churchId = $request->request->get('church_id');
        if ($churchId) {
            $church = $this->em->getRepository(Church::class)->find($churchId);
            $member->setChurch($church);
        }

        $this->validator->validate($member);
        $this->em->persist($member);
        $this->em->flush();

        return $this->json(['status' => 'ok', 'id' => $member->getId()]);
    }

    #[Route('/{id}', name: 'member_show', methods: ['GET'])]
    public function show(Member $member): Response
    {
        return $this->json($this->memberToArray($member));
    }

    #[Route('/{id}', name: 'member_update', methods: ['PUT'])]
    public function update(Request $request, Member $member): Response
    {
        $member->setName($request->request->get('name', $member->getName()));
        $member->setEmail($request->request->get('email', $member->getEmail()));
        $member->setDocumentType($request->request->get('document_type', $member->getDocumentType()));
        $member->setDocumentNumber($request->request->get('document_number', $member->getDocumentNumber()));
        $member->setPhone($request->request->get('phone', $member->getPhone()));
        
        $birthDate = $request->request->get('birth_date');
        if ($birthDate) {
            $member->setBirthDate(new \DateTime($birthDate));
        }
        
        $member->setAddressStreet($request->request->get('address_street', $member->getAddressStreet()));
        $member->setAddressNumber($request->request->get('address_number', $member->getAddressNumber()));
        $member->setAddressComplement($request->request->get('address_complement', $member->getAddressComplement()));
        $member->setCity($request->request->get('city', $member->getCity()));
        $member->setState($request->request->get('state', $member->getState()));
        $member->setCep($request->request->get('cep', $member->getCep()));

        $churchId = $request->request->get('church_id');
        $church = $churchId ? $this->em->getRepository(Church::class)->find($churchId) : $member->getChurch();
        $member->setChurch($church);

        $this->validator->validate($member);
        $this->em->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/{id}/delete', name: 'member_delete', methods: ['DELETE'])]
    public function delete(Member $member): Response
    {
        $this->em->remove($member);
        $this->em->flush();

        return $this->json(['status' => 'deleted']);
    }

    #[Route('/', name: 'member_list', methods: ['GET'])]
    public function list(): Response
    {
        $members = $this->em->getRepository(Member::class)->findAll();
        $result = array_map(fn(Member $m) => $this->memberToArray($m), $members);
        return $this->json($result);
    }

    private function formatAddress(?string $street, ?string $number, ?string $complement, ?string $city, ?string $state, ?string $cep): ?string
    {
        if (!$street && !$number && !$city && !$state && !$cep) {
            return null;
        }

        $parts = [];
        if ($street) $parts[] = $street;
        if ($number) $parts[] = $number;
        if ($complement) $parts[] = $complement;
        if ($city) $parts[] = $city;
        if ($state) $parts[] = $state;
        if ($cep) $parts[] = $cep;

        return implode(', ', $parts);
    }

    private function memberToArray(Member $member): array
    {
        return [
            'id' => $member->getId(),
            'name' => $member->getName(),
            'document_type' => $member->getDocumentType(),
            'document_number' => $member->getDocumentNumber(),
            'email' => $member->getEmail(),
            'phone' => $member->getPhone(),
            'birth_date' => $member->getBirthDate()?->format('Y-m-d'),
            'address' => $this->formatAddress(
                $member->getAddressStreet(),
                $member->getAddressNumber(),
                $member->getAddressComplement(),
                $member->getCity(),
                $member->getState(),
                $member->getCep()
            ),
            'church' => $member->getChurch() ? [
                'id' => $member->getChurch()->getId(),
                'name' => $member->getChurch()->getName(),
            ] : null,
            'created_at' => $member->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $member->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}