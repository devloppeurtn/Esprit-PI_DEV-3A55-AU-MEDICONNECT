<?php

namespace App\Repository;

use App\Entity\ProgressionUtilisateur;
use App\Entity\Utilisateur;
use App\Entity\CategorieSante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgressionUtilisateur>
 */
class ProgressionUtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgressionUtilisateur::class);
    }

    /**
     * Find progression by user and category
     */
    public function findByUtilisateurAndCategorie(Utilisateur $utilisateur, CategorieSante $categorie): ?ProgressionUtilisateur
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.utilisateur = :utilisateur')
            ->andWhere('p.categorieSante = :categorie')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('categorie', $categorie)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all progressions for a user (only where category still exists)
     */
    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.categorieSante', 'c')
            ->addSelect('c')
            ->andWhere('p.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('p.dateObtention', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find completed progressions for a user
     */
    public function findCompletedByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.utilisateur = :utilisateur')
            ->andWhere('p.estComplete = :complete')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('complete', true)
            ->orderBy('p.dateObtention', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user statistics for a category
     */
    public function getUserStatsForCategorie(Utilisateur $utilisateur, CategorieSante $categorie): ?array
    {
        $progression = $this->findByUtilisateurAndCategorie($utilisateur, $categorie);
        
        if (!$progression) {
            return null;
        }

        return [
            'scoreMax' => $progression->getScoreMax(),
            'nbTentatives' => $progression->getNbTentatives(),
            'badgeNom' => $progression->getBadgeNom(),
            'estComplete' => $progression->isEstComplete(),
            'dateObtention' => $progression->getDateObtention(),
        ];
    }
}
