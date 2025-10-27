<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private AuditService $auditService,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/register', name: 'admin_register', methods: ['POST'])]
    #[OA\Post(
        path: "/admin/register",
        summary: "Registrar administrador",
        description: "Cria um novo administrador no sistema",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "username", type: "string", description: "Nome de usuário único", example: "admin"),
                        new OA\Property(property: "email", type: "string", description: "Email único", example: "admin@igreja.com"),
                        new OA\Property(property: "password", type: "string", description: "Senha", example: "123456"),
                        new OA\Property(property: "fullName", type: "string", description: "Nome completo", example: "Administrador Sistema")
                    ],
                    required: ["username", "email", "password", "fullName"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Administrador criado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Administrador criado com sucesso"),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "username", type: "string", example: "admin"),
                            new OA\Property(property: "email", type: "string", example: "admin@igreja.com"),
                            new OA\Property(property: "fullName", type: "string", example: "Administrador Sistema")
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Dados inválidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Dados inválidos")
                    ]
                )
            )
        ],
        tags: ["Administradores"]
    )]
    public function register(Request $request): JsonResponse
    {
        $data = $request->request->all();
        
        $admin = new Admin();
        $admin->setUsername($data['username'] ?? '');
        $admin->setEmail($data['email'] ?? '');
        $admin->setFullName($data['fullName'] ?? '');
        
        $plainPassword = $data['password'] ?? '';
        $hashedPassword = $this->passwordHasher->hashPassword($admin, $plainPassword);
        $admin->setPassword($hashedPassword);
        
        $errors = $this->validator->validate($admin);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => implode(', ', $errorMessages)], 400);
        }
        
        $existingAdmin = $this->em->getRepository(Admin::class)->findByUsername($admin->getUsername());
        if ($existingAdmin) {
            return $this->json(['error' => 'Nome de usuário já existe'], 400);
        }
        $existingEmail = $this->em->getRepository(Admin::class)->findByEmail($admin->getEmail());
        if ($existingEmail) {
            return $this->json(['error' => 'Email já existe'], 400);
        }
        
        $this->em->persist($admin);
        $this->em->flush();
        
        $this->auditService->logCreate('Admin', $admin->getId(), [
            'username' => $admin->getUsername(),
            'email' => $admin->getEmail(),
            'fullName' => $admin->getFullName()
        ]);
        
        return $this->json([
            'message' => 'Administrador criado com sucesso',
            'data' => [
                'id' => $admin->getId(),
                'username' => $admin->getUsername(),
                'email' => $admin->getEmail(),
                'fullName' => $admin->getFullName(),
                'createdAt' => $admin->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], 201);
    }

    #[Route('/login', name: 'admin_login', methods: ['POST'])]
    #[OA\Post(
        path: "/admin/login",
        summary: "Login de administrador",
        description: "Autentica um administrador e retorna JWT token",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "username", type: "string", description: "Nome de usuário", example: "admin"),
                        new OA\Property(property: "password", type: "string", description: "Senha", example: "123456")
                    ],
                    required: ["username", "password"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login realizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
                        new OA\Property(property: "admin", type: "object", properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "username", type: "string", example: "admin"),
                            new OA\Property(property: "email", type: "string", example: "admin@igreja.com"),
                            new OA\Property(property: "fullName", type: "string", example: "Administrador Sistema")
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Credenciais inválidas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Credenciais inválidas")
                    ]
                )
            )
        ],
        tags: ["Administradores"]
    )]
    public function login(Request $request): JsonResponse
    {
        $data = $request->request->all();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        $admin = $this->em->getRepository(Admin::class)->findByUsername($username);
        
        if (!$admin || !$this->passwordHasher->isPasswordValid($admin, $password)) {
            return $this->json(['error' => 'Credenciais inválidas'], 401);
        }
        
        if (!$admin->isActive()) {
            return $this->json(['error' => 'Conta desativada'], 401);
        }
        
        $admin->setLastLoginAt(new \DateTime());
        $this->em->flush();
        
        $this->auditService->logLogin($admin);
        
        $token = $this->jwtManager->create($admin);
        
        return $this->json([
            'message' => 'Login realizado com sucesso',
            'token' => $token,
            'admin' => [
                'id' => $admin->getId(),
                'username' => $admin->getUsername(),
                'email' => $admin->getEmail(),
                'fullName' => $admin->getFullName(),
                'lastLoginAt' => $admin->getLastLoginAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/list', name: 'admin_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: "/admin/list",
        summary: "Listar administradores",
        description: "Retorna lista de todos os administradores",
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de administradores",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "username", type: "string", example: "admin"),
                                    new OA\Property(property: "email", type: "string", example: "admin@igreja.com"),
                                    new OA\Property(property: "fullName", type: "string", example: "Administrador Sistema"),
                                    new OA\Property(property: "isActive", type: "boolean", example: true),
                                    new OA\Property(property: "createdAt", type: "string", example: "2024-01-15 10:30:00"),
                                    new OA\Property(property: "lastLoginAt", type: "string", example: "2024-01-15 10:30:00")
                                ]
                            )
                        )
                    ]
                )
            )
        ],
        tags: ["Administradores"]
    )]
    public function list(): JsonResponse
    {
        $admins = $this->em->getRepository(Admin::class)->findActive();
        
        $data = array_map(function(Admin $admin) {
            return [
                'id' => $admin->getId(),
                'username' => $admin->getUsername(),
                'email' => $admin->getEmail(),
                'fullName' => $admin->getFullName(),
                'isActive' => $admin->isActive(),
                'createdAt' => $admin->getCreatedAt()->format('Y-m-d H:i:s'),
                'lastLoginAt' => $admin->getLastLoginAt()?->format('Y-m-d H:i:s')
            ];
        }, $admins);
        
        return $this->json(['data' => $data]);
    }

    #[Route('/audit-logs', name: 'admin_audit_logs', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: "/admin/audit-logs",
        summary: "Logs de auditoria",
        description: "Retorna logs de auditoria do sistema",
        parameters: [
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Quantidade de logs por página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 50)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logs de auditoria",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "action", type: "string", example: "CREATE"),
                                    new OA\Property(property: "entityType", type: "string", example: "Admin"),
                                    new OA\Property(property: "entityId", type: "integer", example: 1),
                                    new OA\Property(property: "adminUsername", type: "string", example: "admin"),
                                    new OA\Property(property: "adminEmail", type: "string", example: "admin@igreja.com"),
                                    new OA\Property(property: "ipAddress", type: "string", example: "127.0.0.1"),
                                    new OA\Property(property: "description", type: "string", example: "Criado Admin ID 1"),
                                    new OA\Property(property: "createdAt", type: "string", example: "2024-01-15 10:30:00")
                                ]
                            )
                        )
                    ]
                )
            )
        ],
        tags: ["Administradores"]
    )]
    public function auditLogs(Request $request): JsonResponse
    {
        $limit = max(1, min(100, (int) $request->query->get('limit', 50)));
        
        $auditLogs = $this->em->getRepository(\App\Entity\AuditLog::class)->findRecent($limit);
        
        $data = array_map(function($log) {
            return [
                'id' => $log->getId(),
                'action' => $log->getAction(),
                'entityType' => $log->getEntityType(),
                'entityId' => $log->getEntityId(),
                'adminUsername' => $log->getAdminUsername(),
                'adminEmail' => $log->getAdminEmail(),
                'ipAddress' => $log->getIpAddress(),
                'description' => $log->getDescription(),
                'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $auditLogs);
        
        return $this->json(['data' => $data]);
    }
}
