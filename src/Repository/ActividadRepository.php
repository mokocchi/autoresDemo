<?php

namespace App\Repository;

use App\Entity\Actividad;
use App\Entity\Dominio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Actividad|null find($id, $lockMode = null, $lockVersion = null)
 * @method Actividad|null findOneBy(array $criteria, array $orderBy = null)
 * @method Actividad[]    findAll()
 * @method Actividad[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActividadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actividad::class);
    }

    public function isThereWithDominio(Dominio $value)
    {
        return $this->createQueryBuilder('t')
            ->select('count(t)')
            ->where('t.dominio = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllQueryBuilder($filter = '')
    {
        $qb = $this->createQueryBuilder('actividad');
        if ($filter) {
            $qb->andWhere('actividad.nombre LIKE :filter')
                ->setParameter('filter', '%' . $filter . '%');
        }
        return $qb;
    }

    /*
    public function findOneBySomeField($value): ?Actividad
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
