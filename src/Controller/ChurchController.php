<?php

namespace App\Controller;

use App\Entity\Church;
use App\Validator\ChurchValidator;
use App\DTO\CreateChurchDTO;
use App\DTO\UpdateChurchDTO;
use App\Service\ChurchDTOService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/church')]
class ChurchController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private ChurchValidator $validator,
        private PaginatorInterface $paginator,
        private ChurchDTOService $dtoService
    )
    {
    }

    #[Route('/create', name: 'church_create', methods: ['POST'])]
    #[OA\Post(
        path: "/church/create",
        summary: "Criar igreja",
        description: "Cria uma nova igreja com validações de documento e código interno",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Igreja Central", description: "Nome da igreja"),
                    new OA\Property(property: "document_type", type: "string", enum: ["CPF", "CNPJ"], example: "CNPJ", description: "Tipo do documento"),
                    new OA\Property(property: "document_number", type: "string", example: "11222333000181", description: "Número do documento"),
                    new OA\Property(property: "internal_code", type: "string", example: "IC001", description: "Código interno único"),
                    new OA\Property(property: "phone", type: "string", example: "(11) 99999-1111", description: "Telefone"),
                    new OA\Property(property: "address_street", type: "string", example: "Rua das Flores, 123", description: "Logradouro"),
                    new OA\Property(property: "address_number", type: "string", example: "123", description: "Número"),
                    new OA\Property(property: "address_complement", type: "string", example: "Sala 1", description: "Complemento"),
                    new OA\Property(property: "city", type: "string", example: "São Paulo", description: "Cidade"),
                    new OA\Property(property: "state", type: "string", example: "SP", description: "Estado"),
                    new OA\Property(property: "cep", type: "string", example: "01234-567", description: "CEP"),
                    new OA\Property(property: "website", type: "string", example: "https://igrejacentral.com", description: "Website"),
                    new OA\Property(property: "members_limit", type: "integer", example: 100, description: "Limite de membros")
                ],
                required: ["name", "document_type", "document_number"]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Igreja criada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "message", type: "string", example: "Igreja criada")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Nome da igreja obrigatório.")
                    ]
                )
            )
        ],
        tags: ["Igrejas"]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $createDTO = CreateChurchDTO::fromArray($data);
        $validationErrors = $this->dtoService->validateDTO($createDTO);
        
        if (!empty($validationErrors)) {
            return $this->json(['errors' => $validationErrors], 400);
        }

        $church = $this->dtoService->createChurchFromDTO($createDTO);

        $this->validator->validate($church);

        $this->em->persist($church);
        $this->em->flush();

        $responseDTO = $this->dtoService->toChurchDTO($church);
        
        return $this->json([
            'id' => $church->getId(), 
            'message' => 'Igreja criada',
            'data' => $responseDTO->toArray()
        ], 201);
    }

    #[Route('/{id}', name: 'church_show', methods: ['GET'])]
    #[OA\Get(
        path: "/church/{id}",
        summary: "Visualizar igreja",
        description: "Retorna os dados completos de uma igreja específica",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da igreja",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dados da igreja",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Igreja Central"),
                        new OA\Property(property: "document_type", type: "string", example: "CNPJ"),
                        new OA\Property(property: "document_number", type: "string", example: "11222333000181"),
                        new OA\Property(property: "internal_code", type: "string", example: "IC001"),
                        new OA\Property(property: "phone", type: "string", example: "(11) 99999-1111"),
                        new OA\Property(property: "members_limit", type: "integer", example: 100),
                        new OA\Property(property: "current_members_count", type: "integer", example: 5),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
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
        tags: ["Igrejas"]
    )]
    public function show(Church $church): JsonResponse
    {
        $responseDTO = $this->dtoService->toChurchDTO($church);
        return $this->json($responseDTO->toArray());
    }

    #[Route('/{id}/update', name: 'church_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/church/{id}/update",
        summary: "Atualizar igreja",
        description: "Atualiza os dados de uma igreja existente",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da igreja",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Igreja Central Atualizada"),
                    new OA\Property(property: "document_type", type: "string", enum: ["CPF", "CNPJ"]),
                    new OA\Property(property: "document_number", type: "string"),
                    new OA\Property(property: "internal_code", type: "string"),
                    new OA\Property(property: "phone", type: "string"),
                    new OA\Property(property: "members_limit", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Igreja atualizada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Igreja atualizada")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Nome da igreja obrigatório.")
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
        tags: ["Igrejas"]
    )]
    public function update(Request $request, Church $church): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        $updateDTO = UpdateChurchDTO::fromArray($data);
        $validationErrors = $this->dtoService->validateDTO($updateDTO);
        
        if (!empty($validationErrors)) {
            return $this->json(['errors' => $validationErrors], 400);
        }

        if (!$updateDTO->hasUpdates()) {
            return $this->json(['message' => 'Nenhuma atualização fornecida'], 400);
        }

        $this->dtoService->updateChurchFromDTO($church, $updateDTO);

        $this->validator->validate($church);
        
        $this->em->flush();

        $responseDTO = $this->dtoService->toChurchDTO($church);
        
        return $this->json([
            'message' => 'Igreja atualizada',
            'data' => $responseDTO->toArray()
        ]);
    }

    #[Route('/{id}/delete', name: 'church_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/church/{id}/delete",
        summary: "Deletar igreja",
        description: "Remove uma igreja do sistema (apenas se não tiver membros)",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da igreja",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Igreja deletada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Igreja deletada")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Não é possível deletar igreja com membros",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Não é possível deletar uma igreja com membros associados.")
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
        tags: ["Igrejas"]
    )]
    public function delete(Church $church): JsonResponse
    {
        $this->em->remove($church);
        $this->em->flush();

        return $this->json(['message' => 'Igreja deletada']);
    }

    #[Route('/', name: 'church_list', methods: ['GET'])]
    #[OA\Get(
        path: "/church/",
        summary: "Listar igrejas",
        description: "Lista paginada de todas as igrejas cadastradas",
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Número da página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1, default: 1)
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Número de itens por página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10, default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista paginada de igrejas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Igreja Central"),
                                    new OA\Property(property: "document_type", type: "string", example: "CNPJ"),
                                    new OA\Property(property: "document_number", type: "string", example: "11222333000181"),
                                    new OA\Property(property: "internal_code", type: "string", example: "IC001"),
                                    new OA\Property(property: "phone", type: "string", example: "(11) 99999-1111"),
                                    new OA\Property(property: "members_limit", type: "integer", example: 100),
                                    new OA\Property(property: "current_members_count", type: "integer", example: 5),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        ),
                        new OA\Property(property: "pagination", type: "object", properties: [
                            new OA\Property(property: "current_page", type: "integer", example: 1),
                            new OA\Property(property: "total_pages", type: "integer", example: 3),
                            new OA\Property(property: "total_items", type: "integer", example: 25),
                            new OA\Property(property: "items_per_page", type: "integer", example: 10),
                            new OA\Property(property: "has_next", type: "boolean", example: true),
                            new OA\Property(property: "has_previous", type: "boolean", example: false)
                        ])
                    ]
                )
            )
        ],
        tags: ["Igrejas"]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        
        $query = $this->em->getRepository(Church::class)->createQueryBuilder('c')
            ->orderBy('c.created_at', 'DESC')
            ->getQuery();
        
        $pagination = $this->paginator->paginate(
            $query,
            $page,
            $limit
        );
        
        $churchesData = array_map(fn(Church $c) => $this->dtoService->toChurchListDTO($c)->toArray(), $pagination->getItems());
        
        $response = [
            'data' => $churchesData,
            'pagination' => [
                'current_page' => $pagination->getCurrentPageNumber(),
                'total_pages' => $pagination->getPageCount(),
                'total_items' => $pagination->getTotalItemCount(),
                'items_per_page' => $pagination->getItemNumberPerPage(),
                'has_next' => $pagination->getCurrentPageNumber() < $pagination->getPageCount(),
                'has_previous' => $pagination->getCurrentPageNumber() > 1
            ]
        ];
        
        return $this->json($response);
    }

    #[Route('/{id}/members', name: 'church_members', methods: ['GET'])]
    #[OA\Get(
        path: "/church/{id}/members",
        summary: "Membros da igreja",
        description: "Retorna lista de todos os membros de uma igreja específica",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da igreja",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de membros da igreja",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "church_id", type: "integer", example: 1),
                        new OA\Property(property: "church_name", type: "string", example: "Igreja Central"),
                        new OA\Property(
                            property: "members",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "João Silva"),
                                    new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                                    new OA\Property(property: "document_number", type: "string", example: "11144477735"),
                                    new OA\Property(property: "phone", type: "string", example: "(11) 99999-3333"),
                                    new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15")
                                ]
                            )
                        )
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
        tags: ["Igrejas"]
    )]
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