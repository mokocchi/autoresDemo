<?php

namespace App\Repository;

use App\Entity\Planificacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Planificacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Planificacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Planificacion[]    findAll()
 * @method Planificacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanificacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planificacion::class);
    }

    // /**
    //  * @return Planificacion[] Returns an array of Planificacion objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Planificacion
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
