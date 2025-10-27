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
use OpenApi\Attributes as OA;

#[Route('/member-transfer')]
class MemberTransferController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private MemberTransferValidator $validator)
    {
    }

    #[Route('/create', name: 'member_transfer_create', methods: ['POST'])]
    #[OA\Post(
        path: "/member-transfer/create",
        summary: "Criar transferência de membro",
        description: "Transfere um membro de uma igreja para outra com validações de negócio",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "member_id", type: "integer", example: 1, description: "ID do membro a ser transferido"),
                    new OA\Property(property: "from_church_id", type: "integer", example: 1, description: "ID da igreja origem"),
                    new OA\Property(property: "to_church_id", type: "integer", example: 2, description: "ID da igreja destino"),
                    new OA\Property(property: "transfer_date", type: "string", format: "date", example: "2024-01-15", description: "Data da transferência (opcional, padrão: hoje)"),
                    new OA\Property(property: "created_by", type: "string", example: "admin", description: "Usuário que criou a transferência (opcional)")
                ],
                required: ["member_id", "from_church_id", "to_church_id"]
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
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "member", type: "object"),
                        new OA\Property(property: "from_church", type: "object"),
                        new OA\Property(property: "to_church", type: "object"),
                        new OA\Property(property: "transfer_date", type: "string", format: "date", example: "2024-01-15"),
                        new OA\Property(property: "created_by", type: "string", example: "admin"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
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
    public function show(MemberTransfer $transfer): JsonResponse
    {
        return $this->json($this->toArray($transfer));
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
                properties: [
                    new OA\Property(property: "member_id", type: "integer", example: 1),
                    new OA\Property(property: "from_church_id", type: "integer", example: 1),
                    new OA\Property(property: "to_church_id", type: "integer", example: 2),
                    new OA\Property(property: "transfer_date", type: "string", format: "date", example: "2024-01-15"),
                    new OA\Property(property: "created_by", type: "string", example: "admin")
                ]
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
        description: "Lista todas as transferências com filtros opcionais",
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de transferências",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "member", type: "object"),
                            new OA\Property(property: "from_church", type: "object"),
                            new OA\Property(property: "to_church", type: "object"),
                            new OA\Property(property: "transfer_date", type: "string", format: "date", example: "2024-01-15"),
                            new OA\Property(property: "created_by", type: "string", example: "admin"),
                            new OA\Property(property: "created_at", type: "string", format: "date-time"),
                            new OA\Property(property: "updated_at", type: "string", format: "date-time")
                        ]
                    )
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
                        new OA\Property(property: "member", type: "object"),
                        new OA\Property(
                            property: "transfers",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "member", type: "object"),
                                    new OA\Property(property: "from_church", type: "object"),
                                    new OA\Property(property: "to_church", type: "object"),
                                    new OA\Property(property: "transfer_date", type: "string", format: "date"),
                                    new OA\Property(property: "created_by", type: "string"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
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