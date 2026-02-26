<?php

namespace App\Repository;

use App\Entity\PlanningMedecin;
use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlanningMedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningMedecin::class);
    }
    public function findByMedecin(Medecin $medecin): ?PlanningMedecin
    {
        return $this->findOneBy(['medecin' => $medecin]);
    }
}