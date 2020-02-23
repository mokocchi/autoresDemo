<?php

namespace App\Repository;

use App\Entity\Dominio;
use App\Entity\Tarea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Tarea|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tarea|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tarea[]    findAll()
 * @method Tarea[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TareaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tarea::class);
    }

    /**
     * @return bool Devuelve si hay tareas con un dominio
     */
    
    public function isThereWithDominio(Dominio $value)
    {
        return $this->createQueryBuilder('t')
            ->select('count(t)')
            ->where('t.dominio = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findAllUserQueryBuilder($nombre = '', $user = '')
    {
        $qb = $this->createQueryBuilder('tarea');
        if ($nombre) {
            $qb->andWhere('tarea.nombre LIKE :nombre')
                ->setParameter('nombre', '%' . $nombre . '%');
        }

        if ($user) {
            $qb->andWhere('tarea.autor = :user')
                ->setParameter('user', $user);
        }
        return $qb;
    }

    public function findAllPublicQueryBuilder($nombre = '')
    {
        $qb = $this->createQueryBuilder('tarea');
        if ($nombre) {
            $qb
                ->join("tarea.estado", "e")
                ->where("e.nombre = :estado")
                ->andWhere('tarea.nombre LIKE :nombre')
                ->setParameter("estado","Público")
                ->setParameter('nombre', '%' . $nombre . '%');
        }
        return $qb;
    }
   

    /*
    public function findOneBySomeField($value): ?Tarea
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
