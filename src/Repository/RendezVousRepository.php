<?php

namespace App\Repository;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Secretaire;
use App\Entity\StatutRendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /** @return RendezVous[] */
    public function findByPatient(Patient $patient): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return RendezVous[] */
    public function findByMedecin(Medecin $medecin): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return RendezVous[] */
    public function findEnAttenteByMedecin(Medecin $medecin): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', StatutRendezVous::EN_ATTENTE)
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
