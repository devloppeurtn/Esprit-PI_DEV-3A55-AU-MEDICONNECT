<?php

namespace App\Repository;

use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medecin>
 */
class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }

    /** @return Medecin[] */
    public function findMedecinsActifs(): array
    {
        return $this->findMedecinsActifsAvecFiltres(null, null);
    }

    /** @return Medecin[] */
    public function findMedecinsActifsAvecFiltres(?string $nom, ?string $specialite, string $tri = 'az'): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.statut = :actif')
            ->setParameter('actif', \App\Entity\StatutCompte::ACTIF);

        if ($nom !== null && $nom !== '') {
            $qb->andWhere('m.nomComplet LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($specialite !== null && $specialite !== '') {
            $qb->andWhere('m.specialite = :specialite')
                ->setParameter('specialite', $specialite);
        }

        $order = ($tri === 'za') ? 'DESC' : 'ASC';
        return $qb->orderBy('m.nomComplet', $order)
            ->getQuery()
            ->getResult();
    }

    /** @return string[] */
    public function findSpecialitesDistinctes(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.specialite')
            ->andWhere('m.statut = :actif')
            ->andWhere('m.specialite IS NOT NULL')
            ->andWhere("m.specialite != ''")
            ->setParameter('actif', \App\Entity\StatutCompte::ACTIF)
            ->groupBy('m.specialite')
            ->orderBy('m.specialite', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_map(fn ($r) => $r['specialite'], $rows);
    }
}
