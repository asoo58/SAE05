<?php

namespace App\Repository;

use App\Entity\Structure;
use App\Entity\Tournee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournee>
 */
class TourneeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournee::class);
    }

    /**
     * Tournées avec leurs étapes et dépôts, pour le module de visualisation.
     *
     * @return Tournee[]
     */
    public function findAvecEtapes(?Structure $structure = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('et', 'd', 's')
            ->leftJoin('t.etapeTournees', 'et')
            ->leftJoin('et.depot', 'd')
            ->join('t.structure', 's')
            ->orderBy('s.id', 'ASC')
            ->addOrderBy('t.code_tournee', 'ASC')
            ->addOrderBy('et.ordrePassage', 'ASC');

        if ($structure !== null) {
            $qb->andWhere('t.structure = :structure')
                ->setParameter('structure', $structure);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Tournee[] Returns an array of Tournee objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Tournee
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
