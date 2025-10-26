<?php

namespace App\Controller;

use App\Entity\Church;
use App\Services\Validator\ChurchValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/church')]
class ChurchController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private ChurchValidator $validator)
    {
    }

    #[Route('/create', name: 'church_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $church = new Church();
        $church->setName($request->request->get('name'));
        $church->setDocumentType($request->request->get('document_type'));
        $church->setDocumentNumber($request->request->get('document_number'));
        $church->setInternalCode($request->request->get('internal_code'));
        $church->setPhone($request->request->get('phone'));
        $church->setAddressStreet($request->request->get('address_street'));
        $church->setAddressNumber($request->request->get('address_number'));
        $church->setAddressComplement($request->request->get('address_complement'));
        $church->setCity($request->request->get('city'));
        $church->setState($request->request->get('state'));
        $church->setCep($request->request->get('cep'));
        $church->setWebsite($request->request->get('website'));
        $church->setMembersLimit((int)$request->request->get('members_limit'));

        $this->validator->validate($church);

        $this->em->persist($church);
        $this->em->flush();

        return $this->json(['status' => 'ok', 'id' => $church->getId()]);
    }

    #[Route('/{id}', name: 'church_show', methods: ['GET'])]
    public function show(Church $church): Response
    {
        return $this->json($this->churchToArray($church, true));
    }

    #[Route('/{id}/update', name: 'church_update', methods: ['POST'])]
    public function update(Request $request, Church $church): Response
    {
        $church->setName($request->request->get('name', $church->getName()));
        $church->setPhone($request->request->get('phone', $church->getPhone()));
        $church->setMembersLimit((int)$request->request->get('members_limit', $church->getMembersLimit()));

        $church->setAddressStreet($request->request->get('address_street', $church->getAddressStreet()));
        $church->setAddressNumber($request->request->get('address_number', $church->getAddressNumber()));
        $church->setAddressComplement($request->request->get('address_complement', $church->getAddressComplement()));
        $church->setCity($request->request->get('city', $church->getCity()));
        $church->setState($request->request->get('state', $church->getState()));
        $church->setCep($request->request->get('cep', $church->getCep()));
        $church->setWebsite($request->request->get('website', $church->getWebsite()));

        $this->validator->validate($church);
        $this->em->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/{id}/delete', name: 'church_delete', methods: ['DELETE'])]
    public function delete(Church $church): Response
    {
        $this->em->remove($church);
        $this->em->flush();

        return $this->json(['status' => 'deleted']);
    }

    #[Route('/', name: 'church_list', methods: ['GET'])]
    public function list(): Response
    {
        $churches = $this->em->getRepository(Church::class)->findAll();

        $result = array_map(fn(Church $c) => $this->churchToArray($c), $churches);

        return $this->json($result);
    }

    #[Route('/{id}/members', name: 'church_members', methods: ['GET'])]
    public function members(Church $church): Response
    {
        $members = $this->em->getRepository(Church::class)->findMembersByChurch($church->getId());

        if (empty($members)) {
            return $this->json([]);
        }
        $churchEntity = $members[0];

        $membersArray = $churchEntity->getMembers()->map(fn($member) => [
            'id' => $member->getId(),
            'name' => $member->getName(),
            'document_type' => $member->getDocumentType(),
            'document_number' => $member->getDocumentNumber(),
            'email' => $member->getEmail(),
            'phone' => $member->getPhone(),
            'birth_date' => $member->getBirthDate()?->format('Y-m-d'),
        ])->toArray();

        return $this->json($membersArray);
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

    private function churchToArray(Church $church, bool $withMembers = false): array
    {
        $data = [
            'id' => $church->getId(),
            'name' => $church->getName(),
            'document_type' => $church->getDocumentType(),
            'document_number' => $church->getDocumentNumber(),
            'internal_code' => $church->getInternalCode(),
            'phone' => $church->getPhone(),
            'address' => $this->formatAddress(
                $church->getAddressStreet(),
                $church->getAddressNumber(),
                $church->getAddressComplement(),
                $church->getCity(),
                $church->getState(),
                $church->getCep()
            ),
            'website' => $church->getWebsite(),
            'members_limit' => $church->getMembersLimit(),
            'created_at' => $church->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $church->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($withMembers) {
            $data['members'] = array_map(fn($m) => $m->getName(), $church->getMembers()->toArray());
        }

        return $data;
    }

}