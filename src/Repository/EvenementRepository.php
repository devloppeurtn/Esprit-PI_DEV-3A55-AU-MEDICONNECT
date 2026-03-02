<?php

namespace App\Repository;

use App\Entity\Evenement;
<<<<<<< HEAD
use App\Entity\Utilisateur;
use App\Enum\StatutEvenement;
=======
<<<<<<< HEAD
=======
use App\Entity\Utilisateur;
use App\Enum\StatutEvenement;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
<<<<<<< HEAD
     * Événements actifs et validés (visibles par le public)
=======
<<<<<<< HEAD
=======
     * Événements actifs et validés (visibles par le public)
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
     * @return Evenement[]
     */
    public function findAllActive(): array
    {
<<<<<<< HEAD
=======
<<<<<<< HEAD
        return $this->createQueryBuilder('m')
            ->andWhere('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
}
