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
use OpenApi\Attributes as OA;

#[Route('/member')]
class MemberController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private MemberValidator $validator)
    {
    }

    #[Route('/create', name: 'member_create', methods: ['POST'])]
    #[OA\Post(
        path: "/member/create",
        summary: "Criar membro",
        description: "Cria um novo membro com validações de documento e email único por igreja",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "João Silva", description: "Nome do membro"),
                    new OA\Property(property: "document_type", type: "string", enum: ["CPF", "CNPJ"], example: "CPF", description: "Tipo do documento"),
                    new OA\Property(property: "document_number", type: "string", example: "11144477735", description: "Número do documento"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao@email.com", description: "Email único na igreja"),
                    new OA\Property(property: "phone", type: "string", example: "(11) 99999-3333", description: "Telefone"),
                    new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15", description: "Data de nascimento"),
                    new OA\Property(property: "address_street", type: "string", example: "Rua A, 100", description: "Logradouro"),
                    new OA\Property(property: "address_number", type: "string", example: "100", description: "Número"),
                    new OA\Property(property: "address_complement", type: "string", example: "Apto 1", description: "Complemento"),
                    new OA\Property(property: "city", type: "string", example: "São Paulo", description: "Cidade"),
                    new OA\Property(property: "state", type: "string", example: "SP", description: "Estado"),
                    new OA\Property(property: "cep", type: "string", example: "01234-567", description: "CEP"),
                    new OA\Property(property: "church_id", type: "integer", example: 1, description: "ID da igreja")
                ],
                required: ["name", "document_type", "document_number", "church_id"]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Membro criado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "message", type: "string", example: "Membro criado")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Email já existe nessa igreja.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Igreja não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Igreja não encontrada")
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
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
    #[OA\Get(
        path: "/member/{id}",
        summary: "Visualizar membro",
        description: "Retorna os dados completos de um membro específico",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID do membro",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dados do membro",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "João Silva"),
                        new OA\Property(property: "document_type", type: "string", example: "CPF"),
                        new OA\Property(property: "document_number", type: "string", example: "11144477735"),
                        new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                        new OA\Property(property: "phone", type: "string", example: "(11) 99999-3333"),
                        new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15"),
                        new OA\Property(property: "church", type: "object"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Membro não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Membro não encontrado")
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
    public function show(Member $member): JsonResponse
    {
        return $this->json($this->toArray($member));
    }

    #[Route('/{id}', name: 'member_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/member/{id}",
        summary: "Atualizar membro",
        description: "Atualiza os dados de um membro existente",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID do membro",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "João Silva Atualizado"),
                    new OA\Property(property: "document_type", type: "string", enum: ["CPF", "CNPJ"]),
                    new OA\Property(property: "document_number", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "phone", type: "string"),
                    new OA\Property(property: "birth_date", type: "string", format: "date"),
                    new OA\Property(property: "church_id", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Membro atualizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Membro atualizado")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Email já existe nessa igreja.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Membro não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Membro não encontrado")
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
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
    #[OA\Delete(
        path: "/member/{id}/delete",
        summary: "Deletar membro",
        description: "Remove um membro do sistema",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID do membro",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Membro deletado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Membro deletado")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Membro não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Membro não encontrado")
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
    public function delete(Member $member): JsonResponse
    {
        $this->em->remove($member);
        $this->em->flush();

        return $this->json(['message' => 'Membro deletado']);
    }

    #[Route('/', name: 'member_list', methods: ['GET'])]
    #[OA\Get(
        path: "/member/",
        summary: "Listar membros",
        description: "Retorna lista de todos os membros com filtro opcional por igreja",
        parameters: [
            new OA\Parameter(
                name: "church_id",
                in: "query",
                description: "Filtrar por ID da igreja",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de membros",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "João Silva"),
                            new OA\Property(property: "document_type", type: "string", example: "CPF"),
                            new OA\Property(property: "document_number", type: "string", example: "11144477735"),
                            new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                            new OA\Property(property: "phone", type: "string", example: "(11) 99999-3333"),
                            new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15"),
                            new OA\Property(property: "church", type: "object"),
                            new OA\Property(property: "created_at", type: "string", format: "date-time"),
                            new OA\Property(property: "updated_at", type: "string", format: "date-time")
                        ]
                    )
                )
            )
        ],
        tags: ["Membros"]
    )]
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