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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/member')]
class MemberController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private MemberValidator $validator,
        private MemberDTOService $dtoService
    )
    {
    }

    #[Route('/create', name: 'member_create', methods: ['POST'])]
    #[OA\Post(
        path: "/member/create",
        summary: "Criar membro",
        description: "Cria um novo membro com validações de documento e email único por igreja",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nome do membro (ex: João Silva)"),
                        new OA\Property(property: "documentType", type: "string", description: "Tipo do documento", enum: ["CPF", "CNPJ"]),
                        new OA\Property(property: "documentNumber", type: "string", description: "Número do documento (ex: 11144477735)"),
                        new OA\Property(property: "email", type: "string", description: "Email único na igreja (ex: joao@email.com)"),
                        new OA\Property(property: "phone", type: "string", description: "Telefone (ex: (11) 99999-3333)"),
                        new OA\Property(property: "birthDate", type: "string", description: "Data de nascimento (ex: 1990-05-15)"),
                        new OA\Property(property: "addressStreet", type: "string", description: "Logradouro (ex: Rua das Palmeiras)"),
                        new OA\Property(property: "addressNumber", type: "string", description: "Número (ex: 456)"),
                        new OA\Property(property: "addressComplement", type: "string", description: "Complemento (ex: Apt 2)"),
                        new OA\Property(property: "city", type: "string", description: "Cidade (ex: São Paulo)"),
                        new OA\Property(property: "state", type: "string", description: "Estado (ex: SP)"),
                        new OA\Property(property: "cep", type: "string", description: "CEP (ex: 01234-567)"),
                        new OA\Property(property: "churchId", type: "integer", description: "ID da igreja (ex: 1)")
                    ],
                    required: ["name", "documentType", "documentNumber", "email", "phone", "birthDate", "addressStreet", "addressNumber", "city", "state", "cep", "churchId"]
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
                type: UpdateMemberDTO::class
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
        
        $qb = $this->em->getRepository(Member::class)->createQueryBuilder('m');
        
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