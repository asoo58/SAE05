<?php

namespace App\Repository;

use App\Entity\LivraisonTournee;
use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LivraisonTournee>
 */
class LivraisonTourneeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LivraisonTournee::class);
    }

    /**
     * Livraisons (une par tournée) avec tournée, véhicule, chauffeur et étapes.
     *
     * @return LivraisonTournee[]
     */
    public function findPourListe(?Structure $structure = null, ?\DateTimeInterface $date = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->addSelect('t', 'v', 'c', 'e', 'et', 'd')
            ->join('l.tournee', 't')
            ->leftJoin('l.vehicule', 'v')
            ->leftJoin('l.chauffeur', 'c')
            ->leftJoin('c.employe', 'e')
            ->leftJoin('t.etapeTournees', 'et')
            ->leftJoin('et.depot', 'd')
            ->orderBy('l.date_livraison', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        if ($structure !== null) {
            $qb->andWhere('t.structure = :structure')
                ->setParameter('structure', $structure);
        }

        if ($date !== null) {
            $qb->andWhere('l.date_livraison = :date')
                ->setParameter('date', $date->format('Y-m-d'));
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return LivraisonTournee[] Returns an array of LivraisonTournee objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LivraisonTournee
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
