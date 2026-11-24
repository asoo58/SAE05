<?php

namespace App\Repository;

use App\Entity\PanierLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PanierLivraison>
 */
class PanierLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PanierLivraison::class);
    }

    public function countPaniersAPreparerPourDepot(\App\Entity\Depot $depot): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.depot = :depot')
            ->andWhere('p.livraisonTournee IS NULL')
            ->andWhere('p.statut = :statut')
            ->setParameter('depot', $depot)
            ->setParameter('statut', 'a_preparer')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return PanierLivraison[] */
    public function findPaniersAPreparerPourDepot(\App\Entity\Depot $depot): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.depot = :depot')
            ->andWhere('p.livraisonTournee IS NULL')
            ->andWhere('p.statut = :statut')
            ->setParameter('depot', $depot)
            ->setParameter('statut', 'a_preparer')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return PanierLivraison[] Returns an array of PanierLivraison objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PanierLivraison
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
