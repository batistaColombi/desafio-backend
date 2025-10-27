<?php

namespace App\Controller;

use App\Entity\Church;
use App\Services\Validator\ChurchValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/church')]
class ChurchController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private ChurchValidator $validator)
    {
    }

    #[Route('/create', name: 'church_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $church = new Church();
        $church->setName($data['name'] ?? null);
        $church->setDocumentType($data['document_type'] ?? null);
        $church->setDocumentNumber($data['document_number'] ?? null);
        $church->setInternalCode($data['internal_code'] ?? null);
        $church->setPhone($data['phone'] ?? null);
        $church->setAddressStreet($data['address_street'] ?? null);
        $church->setAddressNumber($data['address_number'] ?? null);
        $church->setAddressComplement($data['address_complement'] ?? null);
        $church->setCity($data['city'] ?? null);
        $church->setState($data['state'] ?? null);
        $church->setCep($data['cep'] ?? null);
        $church->setWebsite($data['website'] ?? null);
        $church->setMembersLimit(isset($data['members_limit']) ? (int)$data['members_limit'] : null);

        $this->validator->validate($church);

        $this->em->persist($church);
        $this->em->flush();

        return $this->json(['id' => $church->getId(), 'message' => 'Igreja criada'], 201);
    }

    #[Route('/{id}', name: 'church_show', methods: ['GET'])]
    public function show(Church $church): JsonResponse
    {
        return $this->json($this->toArray($church, true));
    }

    #[Route('/{id}/update', name: 'church_update', methods: ['PUT'])]
    public function update(Request $request, Church $church): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        if (isset($data['name'])) $church->setName($data['name']);
        if (isset($data['phone'])) $church->setPhone($data['phone']);
        if (isset($data['members_limit'])) $church->setMembersLimit((int)$data['members_limit']);
        if (isset($data['internal_code'])) $church->setInternalCode($data['internal_code']);
        if (isset($data['document_type'])) $church->setDocumentType($data['document_type']);
        if (isset($data['document_number'])) $church->setDocumentNumber($data['document_number']);

        if (isset($data['address_street'])) $church->setAddressStreet($data['address_street']);
        if (isset($data['address_number'])) $church->setAddressNumber($data['address_number']);
        if (isset($data['address_complement'])) $church->setAddressComplement($data['address_complement']);
        if (isset($data['city'])) $church->setCity($data['city']);
        if (isset($data['state'])) $church->setState($data['state']);
        if (isset($data['cep'])) $church->setCep($data['cep']);
        if (isset($data['website'])) $church->setWebsite($data['website']);

        $this->validator->validate($church);
        $this->em->flush();

        return $this->json(['message' => 'Igreja atualizada']);
    }

    #[Route('/{id}/delete', name: 'church_delete', methods: ['DELETE'])]
    public function delete(Church $church): JsonResponse
    {
        $this->em->remove($church);
        $this->em->flush();

        return $this->json(['message' => 'Igreja deletada']);
    }

    #[Route('/', name: 'church_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $churches = $this->em->getRepository(Church::class)->findAll();
        $result = array_map(fn(Church $c) => $this->toArray($c), $churches);
        return $this->json($result);
    }

    #[Route('/{id}/members', name: 'church_members', methods: ['GET'])]
    public function members(Church $church): JsonResponse
    {
        $members = $church->getMembers();

        $membersArray = $members->map(fn($member) => [
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
            'created_at' => $member->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $member->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ])->toArray();

        return $this->json([
            'church' => [
                'id' => $church->getId(),
                'name' => $church->getName(),
                'members_limit' => $church->getMembersLimit()
            ],
            'members' => $membersArray
        ]);
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

    private function toArray(Church $church, bool $withMembers = false): array
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