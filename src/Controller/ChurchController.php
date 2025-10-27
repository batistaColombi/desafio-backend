<?php

namespace App\Controller;

use App\Entity\Church;
use App\Entity\Member;
use App\Validator\ChurchValidator;
use App\DTO\CreateChurchDTO;
use App\DTO\UpdateChurchDTO;
use App\Service\ChurchDTOService;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/church')]
#[IsGranted('ROLE_ADMIN')]
class ChurchController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private ChurchValidator $validator,
        private PaginatorInterface $paginator,
        private ChurchDTOService $dtoService,
        private AuditService $auditService
    )
    {
    }

    #[Route('/create', name: 'church_create', methods: ['POST'])]
    #[OA\Post(
        path: "/church/create",
        summary: "Criar igreja",
        description: "Cria uma nova igreja com validações de documento e código interno

**Exemplo de curl:**
```bash
curl -X POST 'http://localhost:8000/church/create' \\
  -H 'Authorization: Bearer SEU_TOKEN' \\
  -H 'Content-Type: application/x-www-form-urlencoded' \\
  -d 'name=Igreja Nova&document_type=CNPJ&document_number=11222333000181&internal_code=IC001&phone=(11) 99999-1111&address_street=Rua das Flores&address_number=123&city=São Paulo&state=SP&cep=01234-567&members_limit=100'
```",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "name", type: "string", description: "Nome da igreja (ex: Igreja Central)"),
                        new OA\Property(property: "document_type", type: "string", description: "Tipo do documento", enum: ["CPF", "CNPJ"]),
                        new OA\Property(property: "document_number", type: "string", description: "Número do documento (ex: 11222333000181)"),
                        new OA\Property(property: "internal_code", type: "string", description: "Código interno único (ex: IC001)"),
                        new OA\Property(property: "phone", type: "string", description: "Telefone (ex: (11) 99999-1111)"),
                        new OA\Property(property: "address_street", type: "string", description: "Logradouro (ex: Rua das Flores)"),
                        new OA\Property(property: "address_number", type: "string", description: "Número (ex: 123)"),
                        new OA\Property(property: "address_complement", type: "string", description: "Complemento (ex: Sala 1)"),
                        new OA\Property(property: "city", type: "string", description: "Cidade (ex: São Paulo)"),
                        new OA\Property(property: "state", type: "string", description: "Estado (ex: SP)"),
                        new OA\Property(property: "cep", type: "string", description: "CEP (ex: 01234-567)"),
                        new OA\Property(property: "website", type: "string", description: "Website (ex: https://igrejacentral.com)"),
                        new OA\Property(property: "members_limit", type: "integer", description: "Limite de membros (ex: 100)")
                    ],
                    required: ["name", "document_type", "document_number", "internal_code", "phone", "address_street", "address_number", "city", "state", "cep", "members_limit"]
                )
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
                    type: ChurchDTO::class
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
        description: "Atualiza os dados de uma igreja existente

**Exemplo de curl:**
```bash
curl -X PUT 'http://localhost:8000/church/1/update' \\
  -H 'Authorization: Bearer SEU_TOKEN' \\
  -H 'Content-Type: application/x-www-form-urlencoded' \\
  -d 'name=Igreja Atualizada&phone=(11) 88888-8888&members_limit=200'
```",
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
            content: [
                new OA\MediaType(
                    mediaType: "application/x-www-form-urlencoded",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "name", type: "string", description: "Nome da igreja"),
                            new OA\Property(property: "document_type", type: "string", description: "Tipo do documento", enum: ["CPF", "CNPJ"]),
                            new OA\Property(property: "document_number", type: "string", description: "Número do documento"),
                            new OA\Property(property: "internal_code", type: "string", description: "Código interno único"),
                            new OA\Property(property: "phone", type: "string", description: "Telefone"),
                            new OA\Property(property: "address_street", type: "string", description: "Logradouro"),
                            new OA\Property(property: "address_number", type: "string", description: "Número"),
                            new OA\Property(property: "address_complement", type: "string", description: "Complemento"),
                            new OA\Property(property: "city", type: "string", description: "Cidade"),
                            new OA\Property(property: "state", type: "string", description: "Estado"),
                            new OA\Property(property: "cep", type: "string", description: "CEP"),
                            new OA\Property(property: "website", type: "string", description: "Website"),
                            new OA\Property(property: "members_limit", type: "integer", description: "Limite de membros")
                        ]
                    )
                ),
                new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: UpdateChurchDTO::class
                    )
                )
            ]
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
        description: "Lista paginada de todas as igrejas cadastradas com busca por nome",
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
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Buscar por nome da igreja",
                required: false,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista paginada de igrejas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "pagination", type: "object", properties: [
                            new OA\Property(property: "current_page", type: "integer", example: 1),
                            new OA\Property(property: "total_pages", type: "integer", example: 1),
                            new OA\Property(property: "total_items", type: "integer", example: 4),
                            new OA\Property(property: "items_per_page", type: "integer", example: 10),
                            new OA\Property(property: "has_next", type: "boolean", example: false),
                            new OA\Property(property: "has_previous", type: "boolean", example: false)
                        ]),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: ChurchListDTO::class
                            )
                        )
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
        $search = $request->query->get('search', '');

        $qb = $this->em->getRepository(Church::class)->createQueryBuilder('c');
        
        if (!empty($search)) {
            $qb->andWhere('c.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $query = $qb->orderBy('c.id', 'ASC')->getQuery();
        
        $pagination = $this->paginator->paginate(
            $query,
            $page,
            $limit
        );
        
        $churchesData = array_map(fn(Church $c) => $this->dtoService->toChurchListDTO($c)->toArray(), $pagination->getItems());
        
        $response = [
            'pagination' => [
                'current_page' => $pagination->getCurrentPageNumber(),
                'total_pages' => $pagination->getPageCount(),
                'total_items' => $pagination->getTotalItemCount(),
                'items_per_page' => $pagination->getItemNumberPerPage(),
                'has_next' => $pagination->getCurrentPageNumber() < $pagination->getPageCount(),
                'has_previous' => $pagination->getCurrentPageNumber() > 1
            ],
            'data' => $churchesData
        ];
        
        return $this->json($response);
    }

    #[Route('/{id}/members', name: 'church_members', methods: ['GET'])]
    #[OA\Get(
        path: "/church/{id}/members",
        summary: "Membros da igreja",
        description: "Retorna lista paginada de membros de uma igreja específica",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID da igreja",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Número da página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Quantidade de itens por página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de membros da igreja",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "pagination", type: "object", properties: [
                            new OA\Property(property: "current_page", type: "integer", example: 1),
                            new OA\Property(property: "total_pages", type: "integer", example: 2),
                            new OA\Property(property: "total_items", type: "integer", example: 15),
                            new OA\Property(property: "items_per_page", type: "integer", example: 10),
                            new OA\Property(property: "has_next", type: "boolean", example: true),
                            new OA\Property(property: "has_previous", type: "boolean", example: false)
                        ]),
                        new OA\Property(property: "church", type: "object", properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Igreja Central"),
                            new OA\Property(property: "members_limit", type: "integer", example: 100)
                        ]),
                        new OA\Property(
                            property: "data",
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
    public function members(Church $church, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 10)));
        
        $qb = $this->em->getRepository(Member::class)->createQueryBuilder('m')
            ->where('m.church = :church')
            ->andWhere('m.isDeleted = false') // Filtrar apenas membros ativos
            ->setParameter('church', $church)
            ->orderBy('m.id', 'ASC');
        
        $pagination = $this->paginator->paginate(
            $qb,
            $page,
            $limit
        );
        
        $membersArray = $pagination->getItems();
        $membersData = array_map(fn($member) => [
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
        ], $membersArray);

        $response = [
            'pagination' => [
                'current_page' => $pagination->getCurrentPageNumber(),
                'total_pages' => $pagination->getPageCount(),
                'total_items' => $pagination->getTotalItemCount(),
                'items_per_page' => $pagination->getItemNumberPerPage(),
                'has_next' => $pagination->getCurrentPageNumber() < $pagination->getPageCount(),
                'has_previous' => $pagination->getCurrentPageNumber() > 1
            ],
            'church' => [
                'id' => $church->getId(),
                'name' => $church->getName(),
                'members_limit' => $church->getMembersLimit()
            ],
            'data' => $membersData
        ];

        return $this->json($response);
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