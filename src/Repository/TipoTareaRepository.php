<?php

namespace App\Repository;

use App\Entity\TipoTarea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TipoTarea|null find($id, $lockMode = null, $lockVersion = null)
 * @method TipoTarea|null findOneBy(array $criteria, array $orderBy = null)
 * @method TipoTarea[]    findAll()
 * @method TipoTarea[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoTareaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoTarea::class);
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
    //  * @return TipoTarea[] Returns an array of TipoTarea objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TipoTarea
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
