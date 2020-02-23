<?php

namespace App\Repository;

use App\Entity\TipoPlanificacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Planificacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Planificacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Planificacion[]    findAll()
 * @method Planificacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoPlanificacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoPlanificacion::class);
    }

    public function deleteLike($nombre)
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.nombre LIKE :nombre')
            ->setParameter("nombre", $nombre . '%')
            ->getQuery()
            ->execute();
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
