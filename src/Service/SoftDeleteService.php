<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use App\Entity\Admin;
use App\Service\AuditService;

class SoftDeleteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private Security $security,
        private AuditService $auditService
    ) {}

    public function softDelete(object $entity, ?string $deletedBy = null): void
    {
        if (!method_exists($entity, 'softDelete')) {
            throw new \InvalidArgumentException('Entity must use SoftDeleteableTrait');
        }

        $user = $deletedBy ?? $this->getCurrentUser();
        $entity->softDelete($user);
        
        $admin = $this->security->getUser();
        $entityClass = get_class($entity);
        $entityName = substr($entityClass, strrpos($entityClass, '\\') + 1);
        
        $this->auditService->logDelete(
            $entityName,
            $entity->getId(),
            $this->getEntityData($entity),
            $admin instanceof Admin ? $admin : null
        );
        
        $this->em->flush();
    }

    public function restore(object $entity): void
    {
        if (!method_exists($entity, 'restore')) {
            throw new \InvalidArgumentException('Entity must use SoftDeleteableTrait');
        }

        $entity->restore();
        
        $admin = $this->security->getUser();
        $entityClass = get_class($entity);
        $entityName = substr($entityClass, strrpos($entityClass, '\\') + 1);
        
        $this->auditService->logRestore(
            $entityName,
            $entity->getId(),
            $admin instanceof Admin ? $admin : null
        );
        
        $this->em->flush();
    }

    private function getCurrentUser(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return 'Sistema';
        }

        $user = $request->headers->get('X-User-Name');
        if ($user) {
            return $user;
        }

        $ip = $request->getClientIp();
        return $ip ? "Usuário ($ip)" : 'Sistema';
    }

    private function getEntityData(object $entity): array
    {
        $data = [];
        
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);
            
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            
            $data[$property->getName()] = $value;
        }
        
        return $data;
    }
}
