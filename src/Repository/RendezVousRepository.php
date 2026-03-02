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

    /**
     * Récupère tous les RDV CONFIRME ou EN_ATTENTE pour un médecin sur une date donnée.
     * Utilisé par DisponibiliteService pour calculer les créneaux libres.
     *
     * @return RendezVous[]
     */
    public function findByMedecinAndDate(Medecin $medecin, \DateTimeInterface $date): array
    {
        $debut = (new \DateTime($date->format('Y-m-d') . ' 00:00:00'));
        $fin = (new \DateTime($date->format('Y-m-d') . ' 23:59:59'));

        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.dateDebut >= :debut')
            ->andWhere('r.dateDebut <= :fin')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('medecin', $medecin)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statuts', [StatutRendezVous::EN_ATTENTE, StatutRendezVous::CONFIRME])
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return RendezVous[] */
    public function findByMedecinAndStatut(Medecin $medecin, StatutRendezVous $statut): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', $statut)
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasOverlappingForMedecin(
        Medecin $medecin,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeRdvId = null
    ): bool {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.statut IN (:statuts)')
            ->andWhere('r.dateDebut < :end')
            ->andWhere('r.dateFin > :start')
            ->setParameter('medecin', $medecin)
            ->setParameter('statuts', [StatutRendezVous::EN_ATTENTE, StatutRendezVous::CONFIRME])
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($excludeRdvId !== null) {
            $qb->andWhere('r.id != :excludeId')
                ->setParameter('excludeId', $excludeRdvId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return RendezVous[]
     */
    public function findConfirmedStartingBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateDebut >= :from')
            ->andWhere('r.dateDebut < :to')
            ->setParameter('statut', StatutRendezVous::CONFIRME)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
