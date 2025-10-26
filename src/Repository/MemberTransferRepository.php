<?php

namespace App\Repository;

use App\Entity\MemberTransfer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MemberTransfer>
 */
class MemberTransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberTransfer::class);
    }

       /**
        * @return MemberTransfer[] Returns an array of MemberTransfer objects
        */
       public function findByExampleField($value): array
       {
           return $this->createQueryBuilder('m')
               ->andWhere('m.exampleField = :val')
               ->setParameter('val', $value)
               ->orderBy('m.id', 'ASC')
               ->setMaxResults(10)
               ->getQuery()
               ->getResult()
           ;
       }

       public function findOneBySomeField($value): ?MemberTransfer
       {
           return $this->createQueryBuilder('m')
               ->andWhere('m.exampleField = :val')
               ->setParameter('val', $value)
               ->getQuery()
               ->getOneOrNullResult()
           ;
       }
}
