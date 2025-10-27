<?php

namespace App\Controller;

use App\Entity\Member;
use App\Entity\Church;
use App\Services\Validator\MemberValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/member')]
class MemberController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private MemberValidator $validator)
    {
    }

    #[Route('/create', name: 'member_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $member = new Member();
        $member->setName($data['name'] ?? null);
        $member->setDocumentType($data['document_type'] ?? null);
        $member->setDocumentNumber($data['document_number'] ?? null);
        $member->setEmail($data['email'] ?? null);
        $member->setPhone($data['phone'] ?? null);
        
        if ($data['birth_date'] ?? null) {
            $member->setBirthDate(new \DateTime($data['birth_date']));
        }
        
        $member->setAddressStreet($data['address_street'] ?? null);
        $member->setAddressNumber($data['address_number'] ?? null);
        $member->setAddressComplement($data['address_complement'] ?? null);
        $member->setCity($data['city'] ?? null);
        $member->setState($data['state'] ?? null);
        $member->setCep($data['cep'] ?? null);

        if ($data['church_id'] ?? null) {
            $church = $this->em->getRepository(Church::class)->find($data['church_id']);
            if (!$church) {
                return $this->json(['error' => 'Igreja não encontrada'], 404);
            }
            $member->setChurch($church);
        }

        $this->validator->validate($member);
        $this->em->persist($member);
        $this->em->flush();

        return $this->json(['id' => $member->getId(), 'message' => 'Membro criado'], 201);
    }

    #[Route('/{id}', name: 'member_show', methods: ['GET'])]
    public function show(Member $member): JsonResponse
    {
        return $this->json($this->toArray($member));
    }

    #[Route('/{id}', name: 'member_update', methods: ['PUT'])]
    public function update(Request $request, Member $member): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        if (isset($data['name'])) $member->setName($data['name']);
        if (isset($data['email'])) $member->setEmail($data['email']);
        if (isset($data['document_type'])) $member->setDocumentType($data['document_type']);
        if (isset($data['document_number'])) $member->setDocumentNumber($data['document_number']);
        if (isset($data['phone'])) $member->setPhone($data['phone']);
        
        if (isset($data['birth_date'])) {
            $member->setBirthDate(new \DateTime($data['birth_date']));
        }
        
        if (isset($data['address_street'])) $member->setAddressStreet($data['address_street']);
        if (isset($data['address_number'])) $member->setAddressNumber($data['address_number']);
        if (isset($data['address_complement'])) $member->setAddressComplement($data['address_complement']);
        if (isset($data['city'])) $member->setCity($data['city']);
        if (isset($data['state'])) $member->setState($data['state']);
        if (isset($data['cep'])) $member->setCep($data['cep']);

        if (isset($data['church_id'])) {
            $church = $this->em->getRepository(Church::class)->find($data['church_id']);
            if (!$church) {
                return $this->json(['error' => 'Igreja não encontrada'], 404);
            }
            $member->setChurch($church);
        }

        $this->validator->validate($member);
        $this->em->flush();

        return $this->json(['message' => 'Membro atualizado']);
    }

    #[Route('/{id}/delete', name: 'member_delete', methods: ['DELETE'])]
    public function delete(Member $member): JsonResponse
    {
        $this->em->remove($member);
        $this->em->flush();

        return $this->json(['message' => 'Membro deletado']);
    }

    #[Route('/', name: 'member_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $churchId = $request->query->get('church_id');
        
        $qb = $this->em->getRepository(Member::class)->createQueryBuilder('m');
        
        if ($churchId) {
            $qb->andWhere('m.church = :churchId')->setParameter('churchId', $churchId);
        }
        
        $qb->orderBy('m.name', 'ASC');
        
        $members = $qb->getQuery()->getResult();
        $result = array_map(fn(Member $m) => $this->toArray($m), $members);
        
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

    private function toArray(Member $member): array
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