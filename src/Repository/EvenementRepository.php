<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Enum\StatutEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Événements actifs et validés (visibles par le public)
     * @return Evenement[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.statut = :statut')
            ->setParameter('active', true)
            ->setParameter('statut', StatutEvenement::VALIDE)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Événements en attente de validation admin
     * @return Evenement[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o')
            ->andWhere('e.statut = :statut')
            ->setParameter('statut', StatutEvenement::EN_ATTENTE)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Événements validés (pour affichage public)
     * @return Evenement[]
     */
    public function findValides(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.statut = :statut')
            ->andWhere('e.isActive = :active')
            ->setParameter('statut', StatutEvenement::VALIDE)
            ->setParameter('active', true)
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Événements créés par un organisateur
     * @return Evenement[]
     */
    public function findByOrganisateur(Utilisateur $organisateur): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organisateur = :organisateur')
            ->setParameter('organisateur', $organisateur)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les événements pour l'admin (avec organisateur), optionnellement filtrés par statut
     * @return Evenement[]
     */
    public function findAllForAdmin(?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o')
            ->orderBy('e.createdAt', 'DESC');
        if ($statut !== null && $statut !== '') {
            $enum = \App\Enum\StatutEvenement::tryFrom($statut);
            if ($enum !== null) {
                $qb->andWhere('e.statut = :statut')->setParameter('statut', $enum);
            }
        }
        return $qb->getQuery()->getResult();
    }
}
