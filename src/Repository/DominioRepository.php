<?php

namespace App\Repository;

use App\Entity\Dominio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Dominio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dominio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dominio[]    findAll()
 * @method Dominio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DominioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dominio::class);
    }

    public function findNombreLike($nombre)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.nombre LIKE :nombre')
            ->setParameter('nombre', '%' . $nombre . '%')
            ->getQuery()
            ->getResult();
    }
    // /**
    //  * @return Dominio[] Returns an array of Dominio objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Dominio
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
