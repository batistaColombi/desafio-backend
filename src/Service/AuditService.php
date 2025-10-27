<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {}

    public function logAction(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?Admin $admin = null,
        ?string $description = null
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        
        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setOldData($oldData);
        $auditLog->setNewData($newData);
        $auditLog->setDescription($description);
        
        if ($admin) {
            $auditLog->setAdminUsername($admin->getUsername());
            $auditLog->setAdminEmail($admin->getEmail());
        } else {
            $auditLog->setAdminUsername('Sistema');
        }
        
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }
        
        $this->em->persist($auditLog);
        $this->em->flush();
    }

    public function logCreate(string $entityType, int $entityId, array $data, ?Admin $admin = null): void
    {
        $this->logAction(
            'CREATE',
            $entityType,
            $entityId,
            null,
            $data,
            $admin,
            "Criado {$entityType} ID {$entityId}"
        );
    }

    public function logUpdate(string $entityType, int $entityId, array $oldData, array $newData, ?Admin $admin = null): void
    {
        $this->logAction(
            'UPDATE',
            $entityType,
            $entityId,
            $oldData,
            $newData,
            $admin,
            "Atualizado {$entityType} ID {$entityId}"
        );
    }

    public function logDelete(string $entityType, int $entityId, array $data, ?Admin $admin = null): void
    {
        $this->logAction(
            'DELETE',
            $entityType,
            $entityId,
            $data,
            null,
            $admin,
            "Excluído {$entityType} ID {$entityId}"
        );
    }

    public function logRestore(string $entityType, int $entityId, ?Admin $admin = null): void
    {
        $this->logAction(
            'RESTORE',
            $entityType,
            $entityId,
            null,
            null,
            $admin,
            "Restaurado {$entityType} ID {$entityId}"
        );
    }

    public function logLogin(Admin $admin): void
    {
        $this->logAction(
            'LOGIN',
            'Admin',
            $admin->getId(),
            null,
            null,
            $admin,
            "Login realizado"
        );
    }

    public function logLogout(Admin $admin): void
    {
        $this->logAction(
            'LOGOUT',
            'Admin',
            $admin->getId(),
            null,
            null,
            $admin,
            "Logout realizado"
        );
    }
}
