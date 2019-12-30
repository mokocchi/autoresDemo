<?php

namespace App\Repository;

use App\Entity\Salto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Salto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Salto|null findOneBy(array $criteria, array $orderBy = null)
 * @method Salto[]    findAll()
 * @method Salto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SaltoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salto::class);
    }

    // /**
    //  * @return Salto[] Returns an array of Salto objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Salto
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
