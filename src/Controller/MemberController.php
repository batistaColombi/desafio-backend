<?php

namespace App\Controller;

use App\Entity\Member;
use App\Entity\Church;
use App\Validator\MemberValidator;
use App\DTO\CreateMemberDTO;
use App\DTO\UpdateMemberDTO;
use App\DTO\MemberDTO;
use App\DTO\MemberListDTO;
use App\Service\MemberDTOService;
use App\Service\SoftDeleteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/member')]
#[IsGranted('ROLE_ADMIN')]
class MemberController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private MemberValidator $validator,
        private MemberDTOService $dtoService,
        private SoftDeleteService $softDeleteService
    )
    {
    }

    #[Route('/create', name: 'member_create', methods: ['POST'])]
    #[OA\Post(
        path: "/member/create",
        summary: "Criar membro",
        description: "Cria um novo membro com validações de documento e email único por igreja

**Exemplo de curl:**
```bash
curl -X POST 'http://localhost:8000/member/create' \\
  -H 'Authorization: Bearer SEU_TOKEN' \\
  -H 'Content-Type: application/x-www-form-urlencoded' \\
  -d 'name=João Silva&document_type=CPF&document_number=11144477735&email=joao@email.com&phone=(11) 99999-3333&birth_date=1990-05-15&address_street=Rua das Palmeiras&address_number=456&city=São Paulo&state=SP&cep=01234-567&church_id=1'
```",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nome do membro (ex: João Silva)"),
                        new OA\Property(property: "document_type", type: "string", description: "Tipo do documento", enum: ["CPF", "CNPJ"]),
                        new OA\Property(property: "document_number", type: "string", description: "Número do documento (ex: 11144477735)"),
                        new OA\Property(property: "email", type: "string", description: "Email único na igreja (ex: joao@email.com)"),
                        new OA\Property(property: "phone", type: "string", description: "Telefone (ex: (11) 99999-3333)"),
                        new OA\Property(property: "birth_date", type: "string", description: "Data de nascimento (ex: 1990-05-15)"),
                        new OA\Property(property: "address_street", type: "string", description: "Logradouro (ex: Rua das Palmeiras)"),
                        new OA\Property(property: "address_number", type: "string", description: "Número (ex: 456)"),
                        new OA\Property(property: "address_complement", type: "string", description: "Complemento (ex: Apt 2)"),
                        new OA\Property(property: "city", type: "string", description: "Cidade (ex: São Paulo)"),
                        new OA\Property(property: "state", type: "string", description: "Estado (ex: SP)"),
                        new OA\Property(property: "cep", type: "string", description: "CEP (ex: 01234-567)"),
                        new OA\Property(property: "church_id", type: "integer", description: "ID da igreja (ex: 1)")
                    ],
                    required: ["name", "document_type", "document_number", "email", "phone", "birth_date", "address_street", "address_number", "city", "state", "cep", "church_id"]
                )
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
        
        $createDTO = CreateMemberDTO::fromArray($data);
        $validationErrors = $this->dtoService->validateDTO($createDTO);
        
        if (!empty($validationErrors)) {
            return $this->json(['errors' => $validationErrors], 400);
        }

        $member = $this->dtoService->createMemberFromDTO($createDTO);

        $this->validator->validate($member);

        $this->em->persist($member);
        $this->em->flush();

        $responseDTO = $this->dtoService->toMemberDTO($member);
        
        return $this->json([
            'id' => $member->getId(), 
            'message' => 'Membro criado',
            'data' => $responseDTO->toArray()
        ], 201);
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
                    type: MemberDTO::class
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
        $responseDTO = $this->dtoService->toMemberDTO($member);
        return $this->json($responseDTO->toArray());
    }

    #[Route('/{id}', name: 'member_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/member/{id}",
        summary: "Atualizar membro",
        description: "Atualiza os dados de um membro existente

**Exemplo de curl:**
```bash
curl -X PUT 'http://localhost:8000/member/1' \\
  -H 'Authorization: Bearer SEU_TOKEN' \\
  -H 'Content-Type: application/x-www-form-urlencoded' \\
  -d 'name=João Silva Atualizado&phone=(11) 88888-8888&email=joao.novo@email.com'
```",
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
            content: [
                new OA\MediaType(
                    mediaType: "application/x-www-form-urlencoded",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "name", type: "string", description: "Nome do membro"),
                            new OA\Property(property: "document_type", type: "string", description: "Tipo do documento", enum: ["CPF", "CNPJ"]),
                            new OA\Property(property: "document_number", type: "string", description: "Número do documento"),
                            new OA\Property(property: "email", type: "string", description: "Email único na igreja"),
                            new OA\Property(property: "phone", type: "string", description: "Telefone"),
                            new OA\Property(property: "birth_date", type: "string", description: "Data de nascimento"),
                            new OA\Property(property: "address_street", type: "string", description: "Logradouro"),
                            new OA\Property(property: "address_number", type: "string", description: "Número"),
                            new OA\Property(property: "address_complement", type: "string", description: "Complemento"),
                            new OA\Property(property: "city", type: "string", description: "Cidade"),
                            new OA\Property(property: "state", type: "string", description: "Estado"),
                            new OA\Property(property: "cep", type: "string", description: "CEP"),
                            new OA\Property(property: "church_id", type: "integer", description: "ID da igreja")
                        ]
                    )
                ),
                new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: UpdateMemberDTO::class
                    )
                )
            ]
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
        
        $updateDTO = UpdateMemberDTO::fromArray($data);
        $validationErrors = $this->dtoService->validateDTO($updateDTO);
        
        if (!empty($validationErrors)) {
            return $this->json(['errors' => $validationErrors], 400);
        }

        if (!$updateDTO->hasUpdates()) {
            return $this->json(['message' => 'Nenhuma alteração detectada'], 200);
        }

        $member = $this->dtoService->updateMemberFromDTO($member, $updateDTO);

        $this->validator->validate($member);
        $this->em->flush();

        $responseDTO = $this->dtoService->toMemberDTO($member);
        
        return $this->json([
            'message' => 'Membro atualizado',
            'data' => $responseDTO->toArray()
        ]);
    }

    #[Route('/{id}/delete', name: 'member_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/member/{id}/delete",
        summary: "Excluir membro (soft-delete)",
        description: "Marca membro como excluído sem remover do banco de dados",
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
                description: "Membro excluído com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Membro excluído com sucesso")
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
            ),
            new OA\Response(
                response: 400,
                description: "Membro já foi excluído",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Membro já foi excluído")
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
    public function delete(int $id): JsonResponse
    {
        $member = $this->em->getRepository(Member::class)->find($id);
        
        if (!$member) {
            return $this->json(['error' => 'Membro não encontrado'], 404);
        }
        
        if ($member->isDeleted()) {
            return $this->json(['error' => 'Membro já foi excluído'], 400);
        }
        
        $this->softDeleteService->softDelete($member);
        
        return $this->json(['message' => 'Membro excluído com sucesso']);
    }

    #[Route('/{id}/restore', name: 'member_restore', methods: ['POST'])]
    #[OA\Post(
        path: "/member/{id}/restore",
        summary: "Restaurar membro",
        description: "Restaura membro que foi excluído",
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
                description: "Membro restaurado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Membro restaurado com sucesso")
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
            ),
            new OA\Response(
                response: 400,
                description: "Membro não está excluído",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Membro não está excluído")
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
    public function restore(int $id): JsonResponse
    {
        $member = $this->em->getRepository(Member::class)->find($id);
        
        if (!$member) {
            return $this->json(['error' => 'Membro não encontrado'], 404);
        }
        
        if (!$member->isDeleted()) {
            return $this->json(['error' => 'Membro não está excluído'], 400);
        }
        
        $this->softDeleteService->restore($member);
        
        return $this->json(['message' => 'Membro restaurado com sucesso']);
    }

    #[Route('/', name: 'member_list', methods: ['GET'])]
    #[OA\Get(
        path: "/member/",
        summary: "Listar membros",
        description: "Retorna lista de todos os membros com filtro opcional por igreja e busca por nome",
        parameters: [
            new OA\Parameter(
                name: "church_id",
                in: "query",
                description: "Filtrar por ID da igreja",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Buscar por nome do membro",
                required: false,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de membros",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "pagination", type: "object", properties: [
                            new OA\Property(property: "current_page", type: "integer", example: 1),
                            new OA\Property(property: "total_pages", type: "integer", example: 1),
                            new OA\Property(property: "total_items", type: "integer", example: 4),
                            new OA\Property(property: "items_per_page", type: "integer", example: 4),
                            new OA\Property(property: "has_next", type: "boolean", example: false),
                            new OA\Property(property: "has_previous", type: "boolean", example: false)
                        ]),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: MemberListDTO::class
                            )
                        )
                    ]
                )
            )
        ],
        tags: ["Membros"]
    )]
    public function list(Request $request): JsonResponse
    {
        $churchId = $request->query->get('church_id');
        $search = $request->query->get('search', '');
        
        $qb = $this->em->getRepository(Member::class)->createQueryBuilder('m')
            ->where('m.isDeleted = false'); // Filtrar apenas membros ativos
        
        if ($churchId) {
            $qb->andWhere('m.church = :churchId')->setParameter('churchId', $churchId);
        }
        
        if (!empty($search)) {
            $qb->andWhere('m.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $qb->orderBy('m.id', 'ASC');
        
        $members = $qb->getQuery()->getResult();
        $result = array_map(fn(Member $m) => $this->dtoService->toMemberListDTO($m)->toArray(), $members);
        
        $response = [
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_items' => count($members),
                'items_per_page' => count($members),
                'has_next' => false,
                'has_previous' => false
            ],
            'data' => $result
        ];
        
        return $this->json($response);
    }

}