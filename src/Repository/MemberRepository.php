<?php

namespace App\Repository;

use App\Entity\Member;
use App\Entity\Church;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
    }

    public function findByChurchActive(Church $church): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.church = :church')
            ->andWhere('m.isDeleted = false')
            ->setParameter('church', $church)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByChurchWithDeleted(Church $church): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.church = :church')
            ->setParameter('church', $church)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveBySearch(string $search): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isDeleted = false')
            ->andWhere('m.name LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByChurchActiveWithSearch(Church $church, string $search): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.church = :church')
            ->andWhere('m.isDeleted = false')
            ->andWhere('m.name LIKE :search')
            ->setParameter('church', $church)
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
