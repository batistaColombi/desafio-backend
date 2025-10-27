<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class SoftDeleteableRepository extends EntityRepository
{
    protected function addNotDeletedCondition(QueryBuilder $qb, string $alias = 'e'): QueryBuilder
    {
        return $qb->andWhere($alias . '.isDeleted = false');
    }

    protected function addDeletedCondition(QueryBuilder $qb, string $alias = 'e'): QueryBuilder
    {
        return $qb->andWhere($alias . '.isDeleted = true');
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isDeleted = false')
            ->getQuery()
            ->getResult();
    }

    public function findDeleted(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isDeleted = true')
            ->getQuery()
            ->getResult();
    }

    public function findWithDeleted(): array
    {
        return $this->findAll();
    }
}
