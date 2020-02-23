<?php

namespace App\Repository;

use App\Entity\Idioma;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Idioma|null find($id, $lockMode = null, $lockVersion = null)
 * @method Idioma|null findOneBy(array $criteria, array $orderBy = null)
 * @method Idioma[]    findAll()
 * @method Idioma[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IdiomaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Idioma::class);
    }

    public function deleteLike($nombre)
    {
        $this->createQueryBuilder('i')
            ->delete()
            ->where('i.nombre LIKE :nombre')
            ->setParameter("nombre", $nombre . '%')
            ->getQuery()
            ->execute();
    }
    // /**
    //  * @return Idioma[] Returns an array of Idioma objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Idioma
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
