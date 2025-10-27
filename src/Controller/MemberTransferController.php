<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\MemberTransfer;
use App\Entity\Member;
use App\Entity\Church;
use App\Validator\MemberTransferValidator;
use App\DTO\CreateMemberTransferDTO;
use App\DTO\UpdateMemberTransferDTO;
use App\DTO\MemberTransferDTO;
use App\DTO\MemberTransferListDTO;
use App\Service\MemberTransferDTOService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

#[Route('/member-transfer')]
class MemberTransferController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private MemberTransferValidator $validator,
        private MemberTransferDTOService $dtoService
    )
    {
    }

    #[Route('/create', name: 'member_transfer_create', methods: ['POST'])]
    #[OA\Post(
        path: "/member-transfer/create",
        summary: "Criar transferência de membro",
        description: "Transfere um membro de uma igreja para outra com validações de negócio",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "memberId", type: "integer", description: "ID do membro (ex: 1)"),
                        new OA\Property(property: "fromChurchId", type: "integer", description: "ID da igreja origem (ex: 1)"),
                        new OA\Property(property: "toChurchId", type: "integer", description: "ID da igreja destino (ex: 2)"),
                        new OA\Property(property: "transferDate", type: "string", description: "Data da transferência (ex: 2024-01-15)"),
                        new OA\Property(property: "createdBy", type: "string", description: "Responsável pela transferência (ex: Pastor João)")
                    ],
                    required: ["memberId", "fromChurchId", "toChurchId"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Transferência criada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "message", type: "string", example: "Transferência criada")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Não é possível transferir para a mesma igreja.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Recurso não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Membro não encontrado")
                    ]
                )
            )
        ],
        tags: ["Transferências"]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $createDTO = CreateMemberTransferDTO::fromArray($data);
        $validationErrors = $this->dtoService->validateDTO($createDTO);
        
        if (!empty($validationErrors)) {
            return $this->json(['errors' => $validationErrors], 400);
        }

        $transfer = $this->dtoService->createMemberTransferFromDTO($createDTO);

        $this->validator->validateTransfer($transfer);
        
        $transfer->getMember()->setChurch($transfer->getToChurch());
        
        $this->em->persist($transfer);
        $this->em->flush();

        $responseDTO = $this->dtoService->toMemberTransferDTO($transfer);
        
        return $this->json([
            'id' => $transfer->getId(), 
            'message' => 'Transferência criada',
            'data' => $responseDTO->toArray()
        ], 201);
    }

    #[Route('/{id}', name: 'member_transfer_show', methods: ['GET'])]
    #[OA\Get(
        path: "/member-transfer/{id}",
        summary: "Visualizar transferência",
        description: "Retorna os dados completos de uma transferência específica",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da transferência",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dados da transferência",
                content: new OA\JsonContent(
                    type: MemberTransferDTO::class
                )
            ),
            new OA\Response(
                response: 404,
                description: "Transferência não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Transferência não encontrada")
                    ]
                )
            )
        ],
        tags: ["Transferências"]
    )]
    public function show(MemberTransfer $transfer): JsonResponse
    {
        $responseDTO = $this->dtoService->toMemberTransferDTO($transfer);
        return $this->json($responseDTO->toArray());
    }

    #[Route('/{id}/update', name: 'member_transfer_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/member-transfer/{id}/update",
        summary: "Atualizar transferência",
        description: "Atualiza os dados de uma transferência existente",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da transferência",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: UpdateMemberTransferDTO::class
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Transferência atualizada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Transferência atualizada")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Não é possível transferir para a mesma igreja.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Transferência não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Transferência não encontrada")
                    ]
                )
            )
        ],
        tags: ["Transferências"]
    )]
    public function update(Request $request, MemberTransfer $transfer): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $updateDTO = UpdateMemberTransferDTO::fromArray($data);
        $validationErrors = $this->dtoService->validateDTO($updateDTO);
        
        if (!empty($validationErrors)) {
            return $this->json(['errors' => $validationErrors], 400);
        }

        if (!$updateDTO->hasUpdates()) {
            return $this->json(['message' => 'Nenhuma alteração detectada'], 200);
        }

        $transfer = $this->dtoService->updateMemberTransferFromDTO($transfer, $updateDTO);

        $this->validator->validateTransfer($transfer);
        
        if ($transfer->getMember() && $transfer->getToChurch()) {
            $transfer->getMember()->setChurch($transfer->getToChurch());
        }
        
        $this->em->flush();

        $responseDTO = $this->dtoService->toMemberTransferDTO($transfer);
        
        return $this->json([
            'message' => 'Transferência atualizada',
            'data' => $responseDTO->toArray()
        ]);
    }

    #[Route('/{id}/delete', name: 'member_transfer_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/member-transfer/{id}/delete",
        summary: "Deletar transferência",
        description: "Remove uma transferência do sistema e reverte o membro para a igreja origem",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da transferência",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Transferência deletada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Transferência deletada")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Transferência não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Transferência não encontrada")
                    ]
                )
            )
        ],
        tags: ["Transferências"]
    )]
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
    #[OA\Get(
        path: "/member-transfer/",
        summary: "Listar transferências",
        description: "Lista todas as transferências com filtros opcionais e busca por nome do membro",
        parameters: [
            new OA\Parameter(
                name: "member_id",
                in: "query",
                description: "Filtrar por ID do membro",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "church_id",
                in: "query",
                description: "Filtrar por ID da igreja",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "type",
                in: "query",
                description: "Tipo de filtro: 'incoming' (entrada), 'outgoing' (saída) ou omitir para ambos",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["incoming", "outgoing"])
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
                description: "Lista de transferências",
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
                                type: MemberTransferListDTO::class
                            )
                        )
                    ]
                )
            )
        ],
        tags: ["Transferências"]
    )]
    public function list(Request $request): JsonResponse
    {
        $memberId = $request->query->get('member_id');
        $churchId = $request->query->get('church_id');
        $type = $request->query->get('type');
        $search = $request->query->get('search', '');
        
        $qb = $this->em->getRepository(MemberTransfer::class)->createQueryBuilder('mt')
            ->leftJoin('mt.member', 'm');
        
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
        
        if (!empty($search)) {
            $qb->andWhere('m.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $qb->orderBy('mt.id', 'ASC');
        
        $transfers = $qb->getQuery()->getResult();
        $result = array_map(fn(MemberTransfer $t) => $this->dtoService->toMemberTransferListDTO($t)->toArray(), $transfers);
        
        $response = [
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_items' => count($transfers),
                'items_per_page' => count($transfers),
                'has_next' => false,
                'has_previous' => false
            ],
            'data' => $result
        ];
        
        return $this->json($response);
    }

    #[Route('/member/{memberId}/history', name: 'member_transfer_history', methods: ['GET'])]
    #[OA\Get(
        path: "/member-transfer/member/{memberId}/history",
        summary: "Histórico de transferências do membro",
        description: "Retorna o histórico completo de transferências de um membro específico",
        parameters: [
            new OA\Parameter(
                name: "memberId",
                in: "path",
                description: "ID do membro",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Histórico de transferências",
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
                        new OA\Property(property: "member", type: "object"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: MemberTransferListDTO::class
                            )
                        )
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
        tags: ["Transferências"]
    )]
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
            ->orderBy('mt.id', 'ASC')
            ->getQuery()
            ->getResult();
        
        $result = array_map(fn(MemberTransfer $t) => $this->dtoService->toMemberTransferListDTO($t)->toArray(), $transfers);
        
        return $this->json([
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_items' => count($transfers),
                'items_per_page' => count($transfers),
                'has_next' => false,
                'has_previous' => false
            ],
            'member' => [
                'id' => $member->getId(),
                'name' => $member->getName(),
                'current_church' => $member->getChurch() ? [
                    'id' => $member->getChurch()->getId(),
                    'name' => $member->getChurch()->getName()
                ] : null
            ],
            'data' => $result
        ]);
    }

}