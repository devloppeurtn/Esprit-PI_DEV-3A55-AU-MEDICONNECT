<?php

namespace App\Repository;

use App\Entity\CommandeProduit;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeProduit>
 *
 * @method CommandeProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommandeProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommandeProduit[]    findAll()
 * @method CommandeProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduit::class);
    }

    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByUtilisateur(Utilisateur $utilisateur, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
