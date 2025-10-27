<?php

namespace App\Repository;

use App\Entity\Church;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChurchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Church::class);
    }

    public function findMembersByChurch(int $churchId): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'm')
            ->leftJoin('c.members', 'm')
            ->where('c.id = :churchId')
            ->setParameter('churchId', $churchId)
            ->getQuery()
            ->getResult();
    }

       public function findByExampleField($value): array
       {
           return $this->createQueryBuilder('c')
               ->andWhere('c.exampleField = :val')
               ->setParameter('val', $value)
               ->orderBy('c.id', 'ASC')
               ->setMaxResults(10)
               ->getQuery()
               ->getResult()
           ;
       }

       public function findOneBySomeField($value): ?Church
       {
           return $this->createQueryBuilder('c')
               ->andWhere('c.exampleField = :val')
               ->setParameter('val', $value)
               ->getQuery()
               ->getOneOrNullResult()
           ;
       }
}
